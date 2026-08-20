<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a QuranDepartment's whole `progress_fields_schema` array in one
 * pass, since several checks are inherently cross-item (key uniqueness) or
 * cross-field (a number field's min <= max) rather than per-row.
 */
class ValidProgressFieldsSchema implements ValidationRule
{
    private const TYPES = ['number', 'select', 'text'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail(__('masters.progress_field_invalid'));

            return;
        }

        $seenKeys = [];

        foreach ($value as $field) {
            if (! is_array($field)) {
                $fail(__('masters.progress_field_invalid'));

                return;
            }

            $label = $field['label'] ?? null;
            $key = $field['key'] ?? null;
            $type = $field['type'] ?? null;

            if (! is_string($label) || trim($label) === '') {
                $fail(__('masters.progress_field_label_required'));

                return;
            }

            if (! is_string($key) || ! preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
                $fail(__('masters.progress_field_key_invalid', ['label' => $label]));

                return;
            }

            if (isset($seenKeys[$key])) {
                $fail(__('masters.progress_field_key_duplicate', ['key' => $key]));

                return;
            }

            $seenKeys[$key] = true;

            if (! in_array($type, self::TYPES, true)) {
                $fail(__('masters.progress_field_type_invalid', ['label' => $label]));

                return;
            }

            if ($type === 'number') {
                $min = $field['min'] ?? null;
                $max = $field['max'] ?? null;

                if ($min !== null && ! is_int($min)) {
                    $fail(__('masters.progress_field_min_invalid', ['label' => $label]));

                    return;
                }

                if ($max !== null && ! is_int($max)) {
                    $fail(__('masters.progress_field_max_invalid', ['label' => $label]));

                    return;
                }

                if ($min !== null && $max !== null && $min > $max) {
                    $fail(__('masters.progress_field_min_max_order', ['label' => $label]));

                    return;
                }
            }

            if ($type === 'select') {
                $options = $field['options'] ?? null;

                if (! is_array($options) || count($options) < 2) {
                    $fail(__('masters.progress_field_options_min', ['label' => $label]));

                    return;
                }

                $nonStringOptions = array_filter($options, static fn ($option) => ! is_string($option));
                $normalized = array_map(static fn ($option) => is_string($option) ? trim($option) : $option, $options);

                if ($nonStringOptions !== [] || in_array('', $normalized, true)) {
                    $fail(__('masters.progress_field_options_min', ['label' => $label]));

                    return;
                }

                if (count($normalized) !== count(array_unique($normalized))) {
                    $fail(__('masters.progress_field_options_duplicate', ['label' => $label]));

                    return;
                }
            }
        }
    }
}
