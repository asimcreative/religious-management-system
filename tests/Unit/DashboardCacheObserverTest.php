<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Observers\DashboardCacheObserver;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardCacheObserverTest extends TestCase
{
    public function test_it_clears_the_changed_company_dashboard_cache(): void
    {
        $companyId = 42;
        $key = "company:{$companyId}:dashboard:overview";
        Cache::put($key, ['total_employees' => 10]);

        $employee = new Employee(['company_id' => $companyId]);
        (new DashboardCacheObserver)->saved($employee);

        $this->assertFalse(Cache::has($key));
    }
}
