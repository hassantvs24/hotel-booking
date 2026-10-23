<?php

namespace App\Services\Property;

use App\Models\Property;
use App\Models\PropertyChangeLog;
use App\Models\User;

/**
 * Writes down what changed in a property: who did it, when, and the value before and after.
 * The admin reads this (and can undo a change); an owner's own edits and the admin's are both kept.
 */
class PropertyChangeLogger
{
    public function record(
        Property $property,
        ?User $by,
        string $section,
        string $field,
        string $label,
        mixed $old,
        mixed $new,
        string $kind = 'text',
        array $meta = [],
    ): PropertyChangeLog {
        return PropertyChangeLog::create([
            'property_id' => $property->id,
            'user_id'     => $by?->id,
            'user_name'   => $by?->name,
            'role'        => $by ? ($by->is_admin ? 'admin' : 'owner') : 'system',
            'section'     => $section,
            'field'       => $field,
            'label'       => $label,
            'old_value'   => $this->plain($old),
            'new_value'   => $this->plain($new),
            'kind'        => $kind,
            'meta'        => $meta ?: null,
        ]);
    }

    /**
     * Log every field of $labels (field => label) whose value is different now.
     *
     * @param array<string,string> $labels
     * @param array<string,mixed> $before
     * @param array<string,mixed> $after
     */
    public function diff(Property $property, ?User $by, string $section, array $labels, array $before, array $after): int
    {
        $n = 0;

        foreach ($labels as $field => $label) {
            if (!array_key_exists($field, $after)) {
                continue;
            }

            if ($this->plain($before[$field] ?? null) === $this->plain($after[$field])) {
                continue;
            }

            $this->record($property, $by, $section, $field, $label, $before[$field] ?? null, $after[$field]);
            $n++;
        }

        return $n;
    }

    /** What is stored: text as text, nothing as nothing, "14:00:00" the same as "14:00" */
    public function plain(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        if (is_array($v)) {
            return json_encode($v, JSON_UNESCAPED_UNICODE);
        }

        $s = trim((string) $v);

        // times: 14:00:00 and 14:00 are the same moment
        if (preg_match('/^(\d{2}:\d{2}):00$/', $s, $m)) {
            return $m[1];
        }

        // numbers: 24.90 and 24.9 are the same point
        if (is_numeric($s) && str_contains($s, '.')) {
            return rtrim(rtrim($s, '0'), '.');
        }

        return $s === '' ? null : $s;
    }
}
