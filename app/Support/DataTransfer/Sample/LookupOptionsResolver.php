<?php

namespace App\Support\DataTransfer\Sample;

use App\Support\DataTransfer\Column;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;

/**
 * The values a sample sheet may offer for each lookup column.
 *
 * Read through the model, so the list is exactly what the signed-in user's
 * company owns — a template handed to Company A can never suggest a branch
 * belonging to Company B.
 */
class LookupOptionsResolver
{
    /** Beyond this a dropdown stops being usable, so the list is capped. */
    private const MAX_OPTIONS = 1000;

    /**
     * @return array<string, array<int, string>> Column key => available values.
     */
    public function forDefinition(ResourceDefinitionContract $definition): array
    {
        $options = [];

        foreach ($definition->columns() as $column) {
            if (! $column->isImportable() || ! $column->isLookup()) {
                continue;
            }

            $options[$column->key] = $this->forColumn($column);
        }

        return $options;
    }

    /** @return array<int, string> */
    public function forColumn(Column $column): array
    {
        $modelClass = $column->getLookupModel();
        $lookupColumn = $column->getLookupColumn();

        if ($modelClass === null || $lookupColumn === null) {
            return [];
        }

        return $modelClass::query()
            ->orderBy($lookupColumn)
            ->limit(self::MAX_OPTIONS)
            ->pluck($lookupColumn)
            ->filter(static fn (mixed $value): bool => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all();
    }
}
