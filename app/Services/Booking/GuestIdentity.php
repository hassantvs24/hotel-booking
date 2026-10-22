<?php

namespace App\Services\Booking;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Who will stay. A booking needs the name and the ID of the MAIN GUEST: a NID number, or a passport number for a foreign guest.
 *
 * Most guests book for themselves: their ID is saved on their profile the first time and used from then on without asking again.
 * A guest who books for somebody else types that person's name and ID for that booking only; the profile is not touched.
 */
class GuestIdentity
{
    public const NID = 'nid';
    public const PASSPORT = 'passport';

    /** Letters and digits only, no spaces or dashes; a passport number in capitals */
    public static function normalize(?string $number, ?string $type = null): string
    {
        $n = preg_replace('/[\s\-]+/', '', (string) $number);

        return $type === self::PASSPORT ? strtoupper($n) : $n;
    }

    /** A Bangladeshi NID has 10 (smart card), 13 or 17 digits; a passport number 6 to 20 letters and digits */
    public static function isValid(?string $type, ?string $number): bool
    {
        $n = self::normalize($number, $type);

        return match ($type) {
            self::NID => (bool) preg_match('/^(\d{10}|\d{13}|\d{17})$/', $n),
            self::PASSPORT => (bool) preg_match('/^[A-Z0-9]{6,20}$/', $n),
            default => false,
        };
    }

    public static function problem(?string $type, ?string $number): ?string
    {
        if (!in_array($type, [self::NID, self::PASSPORT], true)) {
            return 'Choose NID or passport.';
        }
        if (self::normalize($number) === '') {
            return $type === self::NID ? 'Enter the NID number of the person who will stay.' : 'Enter the passport number of the person who will stay.';
        }

        return self::isValid($type, $number)
            ? null
            : ($type === self::NID ? 'A NID number has 10, 13 or 17 digits.' : 'A passport number has 6 to 20 letters and digits.');
    }

    /** "•••••••1234" */
    public static function mask(?string $number): string
    {
        $n = (string) $number;

        return $n === '' ? '' : str_repeat('•', max(0, strlen($n) - 4)) . substr($n, -4);
    }

    /**
     * The main guest of the booking being made now, from the request, or else from the ID saved on the profile.
     *
     * @return array{name:string,type:string,number:string,for_other:bool}
     * @throws ValidationException
     */
    public function fromRequest(Request $request, User $user): array
    {
        $forOther = $request->boolean('guest_for_other');
        $typed = self::normalize($request->input('guest_id_number'), $request->input('guest_id_type'));

        if ($forOther || $typed !== '') {
            $type = (string) $request->input('guest_id_type');

            if ($msg = self::problem($type, $typed)) {
                throw ValidationException::withMessages(['guest_id_number' => $msg]);
            }

            $name = trim((string) $request->input('guest_name'));
            if ($forOther && $name === '') {
                throw ValidationException::withMessages(['guest_name' => 'Enter the name of the person who will stay.']);
            }

            return ['name' => $name !== '' ? $name : (string) $user->name, 'type' => $type, 'number' => $typed, 'for_other' => $forOther];
        }

        // booking for themselves, nothing typed: the ID saved on the profile
        $profile = $user->profile;
        foreach ([self::NID => $profile?->nid, self::PASSPORT => $profile?->passport] as $type => $saved) {
            if ($saved && self::isValid($type, $saved)) {
                return ['name' => (string) $user->name, 'type' => $type, 'number' => self::normalize($saved, $type), 'for_other' => false];
            }
        }

        throw ValidationException::withMessages([
            'guest_id_number' => 'Enter your NID number or your passport number. It is asked only once.',
        ]);
    }

    /** The booking columns */
    public function attributes(array $id): array
    {
        return ['main_guest_name' => $id['name'], 'main_guest_id_type' => $id['type'], 'main_guest_id_number' => $id['number']];
    }

    /** A guest who stays themselves is not asked again: their ID is saved on the profile (a booking for somebody else leaves it alone) */
    public function remember(User $user, array $id): void
    {
        if ($id['for_other']) {
            return;
        }

        $user->profile()->updateOrCreate(['user_id' => $user->id], [$id['type'] => $id['number']]);
    }
}
