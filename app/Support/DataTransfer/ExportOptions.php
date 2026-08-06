<?php

namespace App\Support\DataTransfer;

/**
 * What the user asked an export to cover.
 *
 * Kept separate from the HTTP request so the same options object can be
 * handed to a queued job, replayed from an export log, or built in a test
 * without a request in scope.
 */
class ExportOptions
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, int>  $ids
     */
    public function __construct(
        public readonly ExportFormat $format,
        public readonly ExportScope $scope,
        public readonly array $filters = [],
        public readonly array $ids = [],
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {}

    /**
     * Build options from validated request input.
     *
     * Only the filter keys the module declares are carried through, so a
     * caller cannot smuggle an arbitrary column into the export query.
     *
     * @param  array<string, mixed>  $input
     * @param  array<int, string>  $filterKeys
     */
    public static function fromInput(array $input, array $filterKeys): self
    {
        $scope = ExportScope::from($input['scope'] ?? ExportScope::Filtered->value);

        $filters = [];
        foreach ($filterKeys as $key) {
            if (array_key_exists($key, $input) && $input[$key] !== null && $input[$key] !== '') {
                $filters[$key] = $input[$key];
            }
        }

        $ids = array_values(array_filter(array_map(
            static fn (mixed $id): int => (int) $id,
            (array) ($input['ids'] ?? []),
        )));

        return new self(
            format: ExportFormat::from($input['format'] ?? ExportFormat::Xlsx->value),
            scope: $scope,
            filters: $scope->honoursFilters() ? $filters : [],
            ids: $ids,
            page: max(1, (int) ($input['page'] ?? 1)),
            perPage: max(1, min(100, (int) ($input['per_page'] ?? 25))),
        );
    }

    /** Filters worth recording in the export log. */
    public function loggableFilters(): array
    {
        return array_filter(
            $this->filters,
            static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [],
        );
    }
}
