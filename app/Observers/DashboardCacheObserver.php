<?php

namespace App\Observers;

use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Model;

class DashboardCacheObserver
{
    public bool $afterCommit = true;

    public function saved(Model $model): void
    {
        $this->clearCache($model);
    }

    public function deleted(Model $model): void
    {
        $this->clearCache($model);
    }

    public function restored(Model $model): void
    {
        $this->clearCache($model);
    }

    private function clearCache(Model $model): void
    {
        $companyId = $model->getAttribute('company_id');

        if (is_numeric($companyId) && (int) $companyId > 0) {
            app(DashboardService::class)->clearCache((int) $companyId);
        }
    }
}
