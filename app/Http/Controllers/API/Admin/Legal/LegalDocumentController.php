<?php

namespace App\Http\Controllers\API\Admin\Legal;

use App\Http\Controllers\BaseController;
use App\Models\LegalConsent;
use App\Models\LegalDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The platform admin edits the legal pages here */
class LegalDocumentController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can edit the legal pages.');

        $docs = LegalDocument::with('editor:id,name')->orderBy('id')->get()->map(fn (LegalDocument $d) => $this->present($d, false));

        return $this->sendSuccess($docs);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can edit the legal pages.');

        return $this->sendSuccess($this->present(LegalDocument::with('editor:id,name')->where('slug', $slug)->firstOrFail()));
    }

    /** Saving a change makes a new version, so what each owner agreed to stays on record */
    public function update(Request $request, string $slug): JsonResponse
    {
        abort_unless($request->user()->is_admin, 403, 'Only the platform admin can edit the legal pages.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body'  => ['required', 'string', 'max:100000'],
        ]);

        $doc     = LegalDocument::where('slug', $slug)->firstOrFail();
        $changed = $doc->title !== $data['title'] || $doc->body !== $data['body'];

        if ($changed) {
            $doc->update([
                'title'      => $data['title'],
                'body'       => $data['body'],
                'version'    => $doc->version + 1,
                'updated_by' => $request->user()->id,
            ]);
        }

        return $this->sendSuccess(
            $this->present($doc->fresh('editor:id,name')),
            $changed ? "Saved as version {$doc->version}." : 'Nothing changed.'
        );
    }

    private function present(LegalDocument $d, bool $withBody = true): array
    {
        return array_filter([
            'slug'       => $d->slug,
            'title'      => $d->title,
            'body'       => $withBody ? $d->body : null,
            'version'    => $d->version,
            'is_sample'  => $d->isSample(),
            'for_hosts'  => in_array($d->slug, LegalDocument::FOR_HOSTS, true),
            'updated_at' => $d->updated_at,
            'updated_by' => $d->editor?->name,
            'agreed'     => LegalConsent::where('document_slug', $d->slug)->where('document_version', $d->version)->count(),
        ], fn ($v) => $v !== null);
    }
}
