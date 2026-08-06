<?php

namespace App\Support\DataTransfer\Import;

use App\Support\DataTransfer\Column;

/**
 * Matches the headings in an uploaded file to the module's columns.
 *
 * Deliberately forgiving about presentation and strict about meaning: case,
 * spacing, punctuation and a stray byte-order mark are all ignored, but a
 * heading that matches nothing is reported rather than guessed at. Files come
 * from people editing a template in Excel, and "Branch  Name" should not cost
 * anyone an afternoon.
 */
class HeaderMapper
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, Column>  $columns
     */
    public function map(array $headings, array $columns): HeaderMap
    {
        $normalizedHeadings = [];

        foreach ($headings as $index => $heading) {
            $normalized = $this->normalize($heading);

            if ($normalized === '') {
                continue;
            }

            // First occurrence wins, so a duplicated heading cannot silently
            // shadow the column the user filled in first.
            $normalizedHeadings[$normalized] ??= $index;
        }

        $positions = [];
        $claimed = [];
        $missingRequired = [];

        foreach ($columns as $column) {
            $position = null;

            foreach ($column->headingCandidates() as $candidate) {
                $normalized = $this->normalize($candidate);

                if (isset($normalizedHeadings[$normalized])) {
                    $position = $normalizedHeadings[$normalized];

                    break;
                }
            }

            if ($position === null) {
                if ($column->isRequired()) {
                    $missingRequired[] = $column->getLabel();
                }

                continue;
            }

            $positions[$column->key] = $position;
            $claimed[$position] = true;
        }

        $unknown = [];

        foreach ($headings as $index => $heading) {
            if ($this->normalize($heading) !== '' && ! isset($claimed[$index])) {
                $unknown[] = trim((string) $heading);
            }
        }

        return new HeaderMap($positions, $missingRequired, $unknown);
    }

    private function normalize(string $heading): string
    {
        $value = preg_replace('/^\x{FEFF}/u', '', $heading) ?? $heading;
        $value = mb_strtolower(trim($value));

        return preg_replace('/[^a-z0-9\p{Arabic}]/u', '', $value) ?? $value;
    }
}
