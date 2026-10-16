<?php

namespace App\Services\Verification;

use App\Models\OwnerIdentity;
use App\Models\PropertyLicense;
use App\Models\PropertyRequest;
use App\Models\User;
use App\Notifications\Property\PropertyRequestRejected;
use App\Notifications\Property\PropertyRequestReturnedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Owner verification in one place: what an owner must hand in (NID once per person, trade licence per property),
 * where it is kept (the private disk — never a public URL), and what the admin may do with it.
 */
class VerificationService
{
    public const DISK = 'local';

    /** Smart card NID has 10 digits, older cards 13 or 17 */
    private const NID_LENGTHS = [10, 13, 17];

    public const FILE_KINDS = ['nid_front', 'nid_back', 'license'];

    // ── input rules ───────────────────────────────────────

    public function cleanNid(?string $value): string
    {
        return preg_replace('/[\s-]+/', '', (string) $value);
    }

    public function nidError(string $nid): ?string
    {
        if (!ctype_digit($nid) || !in_array(strlen($nid), self::NID_LENGTHS, true)) {
            return 'Enter the NID number with digits only (10, 13 or 17 digits).';
        }

        return null;
    }

    public function hashNid(string $nid): string
    {
        return hash_hmac('sha256', $nid, (string) config('app.key'));
    }

    // ── owner side ────────────────────────────────────────

    public function identityOf(?int $userId): ?OwnerIdentity
    {
        return $userId ? OwnerIdentity::where('user_id', $userId)->first() : null;
    }

    /** Whether the owner's NID is already accepted, so the step doesn't ask for it again */
    public function identityLocked(User $owner): bool
    {
        return (bool) $this->identityOf($owner->id)?->isVerified();
    }

    public function isComplete(User $owner, PropertyRequest $req): bool
    {
        return $this->identityOf($owner->id) !== null && $req->license()->exists();
    }

    /** What the owner may see about their own documents: statuses, reasons, a masked number — never the files */
    public function ownerSummary(User $owner, PropertyRequest $req): array
    {
        $identity = $this->identityOf($owner->id);
        $license  = $req->license;

        return [
            'required' => true,
            'complete' => $identity !== null && $license !== null,
            'nid' => $identity ? [
                'on_file' => true,
                'masked'  => $identity->maskedNumber(),
                'status'  => $identity->status,
                'reason'  => $identity->status === 'rejected' ? $identity->rejection_reason : null,
                'locked'  => $identity->isVerified(),
            ] : ['on_file' => false, 'locked' => false],
            'license' => $license ? [
                'on_file'    => true,
                'number'     => $license->license_number,
                'expires_on' => $license->expires_on?->format('Y-m-d'),
                'status'     => $license->status,
                'reason'     => $license->status === 'rejected' ? $license->rejection_reason : null,
            ] : ['on_file' => false],
        ];
    }

    /**
     * Save what the owner handed in. Files are optional when the document is already on file (only the text changed).
     * Anything that changes puts that document back to "pending" for the admin; an untouched verified one stays verified.
     */
    public function save(User $owner, PropertyRequest $req, array $data, array $files): void
    {
        $oldFiles = [];

        DB::transaction(function () use ($owner, $req, $data, $files, &$oldFiles) {
            $this->saveIdentity($owner, $data, $files, $oldFiles);
            $this->saveLicense($req, $data, $files, $oldFiles);

            $req->update([
                'verification_required' => true,
                'draft_step'            => max((int) $req->draft_step, 8),
            ]);
        });

        $this->deleteFiles($oldFiles);
    }

    private function saveIdentity(User $owner, array $data, array $files, array &$oldFiles): void
    {
        $identity = $this->identityOf($owner->id);

        if ($identity?->isVerified()) {
            return;   // accepted once, stays accepted; changing it is the admin's call
        }

        $nid     = $this->cleanNid($data['nid_number'] ?? '');
        $newFile = (($files['nid_front'] ?? null) instanceof UploadedFile) || (($files['nid_back'] ?? null) instanceof UploadedFile);

        // already on file and nothing new handed in: leave it (and its status) alone
        if ($identity && !$newFile && ($nid === '' || $nid === $identity->nid_number)) {
            return;
        }

        $nid   = $nid !== '' ? $nid : $identity->nid_number;
        $attrs = [
            'nid_number'       => $nid,
            'nid_hash'         => $this->hashNid($nid),
            'status'           => 'pending',
            'rejection_reason' => null,
            'verified_by'      => null,
            'verified_at'      => null,
        ];

        foreach (['nid_front' => 'nid_front_path', 'nid_back' => 'nid_back_path'] as $input => $column) {
            if (($files[$input] ?? null) instanceof UploadedFile) {
                $attrs[$column] = $this->put($files[$input], "verification/nid/{$owner->id}");
                $oldFiles[]     = $identity?->{$column};
            }
        }

        $identity ? $identity->update($attrs) : OwnerIdentity::create($attrs + ['user_id' => $owner->id]);
    }

    private function saveLicense(PropertyRequest $req, array $data, array $files, array &$oldFiles): void
    {
        $license = $req->license;
        $number  = trim((string) ($data['license_number'] ?? ''));
        $expires = $data['license_expiry'] ?? null;
        $file    = ($files['license_file'] ?? null) instanceof UploadedFile ? $files['license_file'] : null;

        $unchanged = $license?->isVerified()
            && $license->license_number === $number
            && ($license->expires_on?->format('Y-m-d')) === ($expires ?: null)
            && !$file;

        if ($unchanged) {
            return;
        }

        $attrs = [
            'license_number'   => $number,
            'expires_on'       => $expires ?: null,
            'status'           => 'pending',
            'rejection_reason' => null,
            'verified_by'      => null,
            'verified_at'      => null,
        ];

        if ($file) {
            $attrs['file_path'] = $this->put($file, "verification/license/{$req->id}");
            $oldFiles[]         = $license?->file_path;
        }

        $license ? $license->update($attrs) : PropertyLicense::create($attrs + ['property_request_id' => $req->id]);
    }

    // ── admin side ────────────────────────────────────────

    /** Short form for the review list: just where each document stands */
    public function listSummary(PropertyRequest $req): ?array
    {
        if (!$req->verification_required) {
            return null;
        }

        return [
            'nid'      => $this->identityOf($req->user_id)?->status,
            'license'  => $req->license?->status,
            'verified' => $this->isFullyVerified($req),
        ];
    }

    /** Everything the reviewer needs next to the pictures, including the full numbers */
    public function adminDetails(PropertyRequest $req): array
    {
        $identity = $this->identityOf($req->user_id);
        $license  = $req->license;

        $sameNid = $identity
            ? OwnerIdentity::where('nid_hash', $identity->nid_hash)->where('id', '!=', $identity->id)
                ->with('user:id,name,email')->get()
                ->map(fn ($o) => ['name' => $o->user?->name, 'email' => $o->user?->email])->all()
            : [];

        $sameLicense = $license
            ? PropertyLicense::where('license_number', $license->license_number)->where('id', '!=', $license->id)
                ->with('propertyRequest:id,property_title,owner_email')->get()
                ->map(fn ($l) => ['property' => $l->propertyRequest?->property_title, 'email' => $l->propertyRequest?->owner_email])->all()
            : [];

        return [
            'required' => (bool) $req->verification_required,
            'verified' => $this->isFullyVerified($req),
            'nid' => $identity ? [
                'number'      => $identity->nid_number,
                'status'      => $identity->status,
                'reason'      => $identity->rejection_reason,
                'verified_at' => $identity->verified_at,
                'verified_by' => $identity->verifier?->name,
                'also_used_by' => $sameNid,
            ] : null,
            'license' => $license ? [
                'number'      => $license->license_number,
                'expires_on'  => $license->expires_on?->format('Y-m-d'),
                'expired'     => (bool) $license->expires_on?->isPast(),
                'is_pdf'      => str_ends_with(strtolower($license->file_path), '.pdf'),
                'status'      => $license->status,
                'reason'      => $license->rejection_reason,
                'verified_at' => $license->verified_at,
                'verified_by' => $license->verifier?->name,
                'also_used_by' => $sameLicense,
            ] : null,
        ];
    }

    public function isFullyVerified(PropertyRequest $req): bool
    {
        return (bool) $this->identityOf($req->user_id)?->isVerified() && (bool) $req->license?->isVerified();
    }

    /** The stored file for one of the three pictures, or null */
    public function filePath(PropertyRequest $req, string $kind): ?string
    {
        $path = match ($kind) {
            'nid_front' => $this->identityOf($req->user_id)?->nid_front_path,
            'nid_back'  => $this->identityOf($req->user_id)?->nid_back_path,
            'license'   => $req->license?->file_path,
            default     => null,
        };

        return $path && Storage::disk(self::DISK)->exists($path) ? $path : null;
    }

    /** Verify or reject one document ("nid" or "license"). Rejecting needs a reason the owner will read. */
    public function decide(User $admin, PropertyRequest $req, string $target, string $status, ?string $reason): void
    {
        $doc = $target === 'nid' ? $this->identityOf($req->user_id) : $req->license;

        abort_if(!$doc, 422, 'The owner has not handed in this document yet.');

        $doc->update([
            'status'           => $status,
            'rejection_reason' => $status === 'rejected' ? $reason : null,
            'verified_by'      => $status === 'verified' ? $admin->id : null,
            'verified_at'      => $status === 'verified' ? now() : null,
        ]);
    }

    /** The owner's My Properties page (where "Edit & resubmit" is), for the button in the email */
    public function myPropertiesUrl(): string
    {
        return rtrim((string) config('app.frontend_url'), '/') . '/profile/request-property';
    }

    /** The documents that are refused right now, each with the reason the admin gave */
    public function rejectedDocuments(PropertyRequest $req): array
    {
        $identity = $this->identityOf($req->user_id);
        $license  = $req->license;
        $docs     = [];

        if ($identity?->status === 'rejected') {
            $docs[] = ['label' => 'NID', 'reason' => trim((string) $identity->rejection_reason)];
        }
        if ($license?->status === 'rejected') {
            $docs[] = ['label' => 'Trade licence', 'reason' => trim((string) $license->rejection_reason)];
        }

        return $docs;
    }

    /** "Your NID needs fixing" / "Your trade licence needs fixing" / "Your NID and trade licence both need fixing" */
    public function fixHeadline(array $docs): string
    {
        return match (count($docs)) {
            0       => 'Your application needs changes',
            1       => 'Your ' . ($docs[0]['label'] === 'NID' ? 'NID' : 'trade licence') . ' needs fixing',
            default => 'Your NID and trade licence both need fixing',
        };
    }

    /** The same words for the red message on My Properties, the bell and the form banner */
    public function fixMessage(array $docs): string
    {
        $end = fn (string $t) => rtrim($t, ". \t") . '.';

        if (count($docs) === 0) {
            return '';
        }
        if (count($docs) === 1) {
            return $this->fixHeadline($docs) . ': ' . $end($docs[0]['reason']);
        }

        return 'Both your NID and your trade licence need fixing. ' . collect($docs)->map(fn ($d) => $d['label'] . ': ' . $end($d['reason']))->implode(' ');
    }

    /**
     * A document was refused: the application goes back to the owner right away, with what is wrong in the message,
     * so they find out and can fix it (anything already accepted stays accepted). If the admin then refuses the other
     * document too, this runs again and the owner gets an updated message that names both.
     */
    public function returnToOwner(PropertyRequest $req): void
    {
        $req->update([
            'status'        => PropertyRequest::STATUS_REJECTED,
            'admin_message' => $this->fixMessage($this->rejectedDocuments($req)),
            'approved_at'   => null,
        ]);

        $this->notifyOwner($req->fresh(), 'documents');
    }

    /** The bell message and the email that say why the application came back to the owner */
    public function notifyOwner(PropertyRequest $req, string $kind): void
    {
        if ($req->user_id && ($owner = User::find($req->user_id))) {
            try {
                $owner->notify(new PropertyRequestReturnedNotification($req, $kind));
            } catch (\Throwable $e) {
                Log::warning('Owner bell notification failed: ' . $e->getMessage());
            }
        }

        $docs = $this->rejectedDocuments($req);

        PropertyRequestRejected::send($req->owner_email, [
            'name'          => $req->name,
            'email'         => $req->owner_email,
            'phone'         => $req->contact_number,
            'property'      => $req->property_title,
            'reason'        => $req->admin_message,
            'documents'     => $kind === 'documents',
            'headline'      => $docs ? $this->fixHeadline($docs) : null,
            'doc_items'     => $docs,
            'edit_url'      => $this->myPropertiesUrl(),
            'support_email' => config('mail.from.address'),
        ]);
    }
    public function log(User $admin, ?int $requestId, string $action): void
    {
        DB::table('verification_access_logs')->insert([
            'admin_id'            => $admin->id,
            'property_request_id' => $requestId,
            'action'              => $action,
            'ip'                  => request()->ip(),
            'created_at'          => now(),
        ]);
    }

    // ── files ─────────────────────────────────────────────

    private function put(UploadedFile $file, string $dir): string
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');

        return $file->storeAs($dir, Str::random(40) . '.' . $ext, self::DISK);
    }

    public function deleteFiles(array $paths): void
    {
        foreach (array_filter($paths) as $path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
