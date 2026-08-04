<?php

namespace App\Http\Controllers\Api;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardApiController extends BaseApiController
{
    public function __construct(
        private readonly DashboardService $service,
    ) {}

    /**
     * GET /api/v1/dashboard
     */
    public function index(): JsonResponse
    {
        return $this->successResponse([
            'overview' => $this->service->overviewStats(),
            'today_quran_attendance' => $this->service->todayQuranAttendance(),
            'today_salah_attendance' => $this->service->todaySalahAttendance(),
            'quran_summary' => $this->service->quranSummary(),
            'salah_summary' => $this->service->salahSummary(),
        ]);
    }
}
