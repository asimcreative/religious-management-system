<?php

namespace Tests\Feature\Security;

use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\TestCase;

/**
 * Architectural guard: every Eloquent model backed by a table with a
 * `company_id` column must apply the `BelongsToCompany` global scope.
 *
 * Tenant isolation in this application is enforced at runtime by that scope —
 * there are no database-level tenant constraints and no permission middleware
 * on the route groups. A model that carries `company_id` but forgets the trait
 * silently returns and mutates every company's rows, including through implicit
 * route-model binding.
 *
 * This is the tenancy counterpart to RouteAuthorizationCoverageTest.
 */
class TenantScopeCoverageTest extends TestCase
{
    /**
     * Models that intentionally carry `company_id` without the global scope.
     * Each entry needs a justification — this is a security decision.
     *
     * @var array<class-string<Model>, string>
     */
    private const INTENTIONALLY_UNSCOPED = [
        User::class => 'Authentication must resolve a user BEFORE a session exists, but the '.
            'scope keys off Auth::user(). Scoping User would make login impossible. '.
            'Cross-tenant ambiguity is handled instead by User::findByUniqueEmail(), '.
            'which fails closed when one email exists in more than one company, and '.
            'no route exposes user administration.',
    ];

    public function test_every_company_owned_model_applies_the_tenant_scope(): void
    {
        $missing = [];
        $checked = 0;

        foreach ($this->modelClasses() as $class) {
            $model = new $class;
            $table = $model->getTable();

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            $checked++;

            if (array_key_exists($class, self::INTENTIONALLY_UNSCOPED)) {
                continue;
            }

            if (! in_array(BelongsToCompany::class, class_uses_recursive($class), true)) {
                $missing[] = $class.' (table: '.$table.')';
            }
        }

        $this->assertGreaterThan(10, $checked, 'Model discovery failed — expected many tenant tables.');

        $this->assertSame([], $missing, sprintf(
            "Models with a company_id column that do NOT use BelongsToCompany:\n%s\n\n".
            'Add `use BelongsToCompany;` to the model, or add it to '.
            'INTENTIONALLY_UNSCOPED with a written justification.',
            implode("\n", $missing)
        ));
    }

    /**
     * @return list<class-string<Model>>
     */
    private function modelClasses(): array
    {
        $classes = [];

        foreach (File::files(app_path('Models')) as $file) {
            $class = 'App\\Models\\'.$file->getFilenameWithoutExtension();

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if (! $reflection->isSubclassOf(Model::class) || $reflection->isAbstract()) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }
}
