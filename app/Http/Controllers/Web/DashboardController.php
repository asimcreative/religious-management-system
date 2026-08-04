<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service,
    ) {}

    public function __invoke(): View
    {
        $overview = $this->service->overviewStats();
        $todayQuran = $this->service->todayQuranAttendance();
        $todaySalah = $this->service->todaySalahAttendance();
        $quranSummary = $this->service->quranSummary();
        $salahSummary = $this->service->salahSummary();

        return view('dashboard', compact(
            'overview',
            'todayQuran',
            'todaySalah',
            'quranSummary',
            'salahSummary',
        ));
    }
}
