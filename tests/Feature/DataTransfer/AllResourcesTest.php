<?php

namespace Tests\Feature\DataTransfer;

use App\Services\DataTransfer\SampleSheetService;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;
use App\Support\DataTransfer\Contracts\ResourceDefinitionContract;
use App\Support\DataTransfer\Export\ExportQueryBuilder;
use App\Support\DataTransfer\Export\ResourceExport;
use App\Support\DataTransfer\ExportFormat;
use App\Support\DataTransfer\ExportOptions;
use App\Support\DataTransfer\ExportScope;
use App\Support\DataTransfer\ResourceRegistry;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Architectural guard over every registered module at once.
 *
 * Definitions are configuration, and configuration rots quietly: a renamed
 * relation, a column dropped from $fillable or a permission that was never
 * seeded would each break one module's import or export at the moment a user
 * tried it, not at the moment it was introduced. These tests run the same
 * checks across the whole registry, so adding a module is covered the day it
 * is added.
 */
class AllResourcesTest extends TestCase
{
    /** @return array<int, array{0: string, 1: ResourceDefinitionContract}> */
    private function definitions(): array
    {
        return array_map(
            static fn (string $key, ResourceDefinitionContract $definition): array => [$key, $definition],
            array_keys(app(ResourceRegistry::class)->all()),
            array_values(app(ResourceRegistry::class)->all()),
        );
    }

    public function test_the_registry_boots_with_every_module_declared(): void
    {
        $keys = app(ResourceRegistry::class)->keys();

        $this->assertGreaterThanOrEqual(17, count($keys), 'Every table-bearing module should be registered.');
        $this->assertSame($keys, array_unique($keys), 'Resource keys must be unique.');

        foreach ($keys as $key) {
            $this->assertSame($key, Str::slug($key), "[{$key}] must be URL-safe: it appears in every transfer route.");
        }
    }

    public function test_every_module_declares_the_permissions_it_gates_on(): void
    {
        $this->seed(PermissionSeeder::class);
        $seeded = Permission::query()->pluck('name')->all();
        $missing = [];

        foreach ($this->definitions() as [$key, $definition]) {
            $abilities = $definition->supportsImport()
                ? ['view', 'export', 'import', 'sample']
                : ['view', 'export'];

            foreach ($abilities as $ability) {
                $permission = $definition->permissionFor($ability);

                $this->assertNotNull($permission, "[{$key}] declares no permission for '{$ability}'.");

                if (! in_array($permission, $seeded, true)) {
                    $missing[] = "{$key}.{$ability} => {$permission}";
                }
            }
        }

        $this->assertSame([], $missing, sprintf(
            "Definitions gate on permissions PermissionSeeder never creates:\n%s",
            implode("\n", $missing),
        ));
    }

    public function test_every_importable_column_can_actually_be_written(): void
    {
        $problems = [];

        foreach ($this->definitions() as [$key, $definition]) {
            if (! $definition->supportsImport()) {
                continue;
            }

            $modelClass = $definition->modelClass();
            $model = new $modelClass;
            $fillable = $model->getFillable();
            $table = $model->getTable();

            foreach ($definition->importColumns() as $column) {
                // A column handled by prepareForWrite/afterWrite is not a
                // model attribute at all; it is recognised by its absence
                // from the table.
                if (! Schema::hasColumn($table, $column->key)) {
                    continue;
                }

                if (! in_array($column->key, $fillable, true)) {
                    $problems[] = "{$key}: {$column->key} is not fillable on {$modelClass}";
                }
            }
        }

        // preventSilentlyDiscardingAttributes() is on outside production, so a
        // non-fillable column would throw partway through a real import.
        $this->assertSame([], $problems, implode("\n", $problems));
    }

    public function test_every_lookup_column_names_a_relation_that_exists(): void
    {
        $problems = [];

        foreach ($this->definitions() as [$key, $definition]) {
            $modelClass = $definition->modelClass();
            $model = new $modelClass;

            foreach ($definition->columns() as $column) {
                if (! $column->isLookup()) {
                    continue;
                }

                $relation = $column->getRelation();

                if ($relation === null || ! method_exists($model, $relation)) {
                    $problems[] = "{$key}: {$column->key} points at missing relation '{$relation}' on {$modelClass}";

                    continue;
                }

                if (! $model->{$relation}() instanceof BelongsTo) {
                    $problems[] = "{$key}: {$column->key} relation '{$relation}' is not a BelongsTo";

                    continue;
                }

                $lookupModel = $column->getLookupModel();
                $lookupTable = (new $lookupModel)->getTable();

                if (! Schema::hasColumn($lookupTable, (string) $column->getLookupColumn())) {
                    $problems[] = "{$key}: {$column->key} looks up '{$column->getLookupColumn()}' which {$lookupTable} does not have";
                }
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    public function test_every_module_can_build_and_run_its_export_query(): void
    {
        $user = $this->createUserWithCompany();
        $this->actingAs($user);

        $builder = app(ExportQueryBuilder::class);

        foreach ($this->definitions() as [$key, $definition]) {
            $query = $builder->build(
                $definition,
                new ExportOptions(ExportFormat::Xlsx, ExportScope::All),
            );

            // Runs the real query, so a bad sort column or eager load fails
            // here rather than in front of a user.
            $this->assertIsInt($query->count(), "[{$key}] could not run its export query.");

            $export = new ResourceExport($definition, $query);
            $headings = $export->headings();

            $this->assertSame('#', $headings[0]);
            $this->assertGreaterThan(1, count($headings), "[{$key}] exports no columns.");
            $this->assertSame($headings, array_unique($headings), "[{$key}] has duplicate export headings.");
        }
    }

    public function test_every_importable_module_produces_a_sample_workbook(): void
    {
        $this->actingAs($this->createUserWithCompany());
        $samples = app(SampleSheetService::class);

        foreach ($this->definitions() as [$key, $definition]) {
            if (! $definition->supportsImport()) {
                continue;
            }

            $sheets = $samples->build($definition)->sheets();

            $this->assertCount(3, $sheets, "[{$key}] must offer Template, Instructions and Reference sheets.");
            $this->assertSame($definition->fileBaseName().'_sample.xlsx', $samples->fileName($definition));

            $columns = $definition->importColumns();
            $this->assertNotEmpty($columns, "[{$key}] is importable but declares no importable columns.");

            foreach ($columns as $column) {
                $this->assertNotSame(
                    '',
                    $column->getLabel(),
                    "[{$key}] column {$column->key} has no label; a template heading cannot be blank.",
                );
            }
        }
    }

    public function test_no_module_ever_exposes_a_tenant_or_audit_column(): void
    {
        $forbidden = ['company_id', 'created_by', 'updated_by', 'deleted_by', 'id'];
        $problems = [];

        foreach ($this->definitions() as [$key, $definition]) {
            foreach ($definition->columns() as $column) {
                if (in_array($column->key, $forbidden, true)) {
                    $problems[] = "{$key}: {$column->key}";
                }
            }
        }

        $this->assertSame([], $problems, sprintf(
            "These are set by the server and must never appear in a template or export:\n%s",
            implode("\n", $problems),
        ));
    }

    public function test_every_declared_index_route_exists(): void
    {
        foreach ($this->definitions() as [$key, $definition]) {
            $route = $definition->indexRoute();

            if ($route === null) {
                continue;
            }

            $this->assertTrue(
                Route::has($route),
                "[{$key}] points back to route '{$route}', which is not registered.",
            );
        }
    }

    public function test_unique_columns_and_groups_reference_real_columns(): void
    {
        $problems = [];

        foreach ($this->definitions() as [$key, $definition]) {
            $modelClass = $definition->modelClass();
            $table = (new $modelClass)->getTable();

            foreach ($definition->columns() as $column) {
                if ($column->isUnique() && ! Schema::hasColumn($table, $column->uniqueColumn())) {
                    $problems[] = "{$key}: uniqueness checked against missing column {$column->uniqueColumn()}";
                }
            }

            foreach ($definition->uniqueGroups() as $group) {
                foreach ($group as $columnKey) {
                    if (! Schema::hasColumn($table, $columnKey)) {
                        $problems[] = "{$key}: unique group names missing column {$columnKey}";
                    }
                }
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    public function test_column_labels_are_translated_rather_than_derived_from_keys(): void
    {
        $problems = [];

        foreach ($this->definitions() as [$key, $definition]) {
            foreach ($definition->columns() as $column) {
                // A label left to derive from the key reads like a database
                // column ("Employee Code Id"), which is exactly what the brief
                // asks headings never to look like.
                if (Str::contains($column->getLabel(), ['_id', 'data_transfer.', 'masters.', 'employees.'])) {
                    $problems[] = "{$key}: {$column->key} label is '{$column->getLabel()}'";
                }
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    /** Guards the promise that a new module needs a definition and nothing else. */
    public function test_no_module_needs_an_import_or_export_class_of_its_own(): void
    {
        foreach ($this->definitions() as [$key, $definition]) {
            $this->assertInstanceOf(
                AbstractResourceDefinition::class,
                $definition,
                "[{$key}] should extend the shared definition base rather than reimplement it.",
            );
        }
    }
}
