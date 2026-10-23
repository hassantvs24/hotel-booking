<?php

namespace App\Services\Notify;

use App\Models\NotificationPreference;
use App\Models\User;

/**
 * Per person and per kind of notification: show a pop-up ("toast"), play a sound, send an email.
 * The bell always keeps every notification; these only decide how loudly it arrives.
 */
class NotificationPreferences
{
    public const CHANNELS = ['toast', 'sound', 'email'];

    /** category => {toast, sound, email}: what the person chose, and the default for anything they did not */
    public function for(User $user): array
    {
        $saved = NotificationPreference::where('user_id', $user->id)->value('settings');
        $out = NotificationCategories::defaults();

        foreach ((array) $saved as $category => $channels) {
            if (!isset($out[$category]) || !is_array($channels)) {
                continue;
            }

            foreach (self::CHANNELS as $channel) {
                if (array_key_exists($channel, $channels)) {
                    $out[$category][$channel] = (bool) $channels[$channel];
                }
            }
        }

        return $out;
    }

    public function wants(User $user, string $category, string $channel): bool
    {
        return (bool) ($this->for($user)[$category][$channel] ?? false);
    }

    /**
     * Save what the person chose (only the kinds and ways that exist) and give back the whole picture.
     *
     * @param array<string,array<string,mixed>> $input
     */
    public function save(User $user, array $input): array
    {
        $now = $this->for($user);

        foreach ($input as $category => $channels) {
            if (!isset($now[$category]) || !is_array($channels)) {
                continue;
            }

            foreach (self::CHANNELS as $channel) {
                if (array_key_exists($channel, $channels)) {
                    $now[$category][$channel] = filter_var($channels[$channel], FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        NotificationPreference::updateOrCreate(['user_id' => $user->id], ['settings' => $now]);

        return $now;
    }
}
