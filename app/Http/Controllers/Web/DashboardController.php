<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service,
    ) {}

    public function __invoke(): View
    {
        abort_unless(Gate::any([
            'report.dashboard',
            'employee.view',
            'teacher.view',
            'quran.class.view',
            'jamaat.view',
            'quran.attendance.view',
            'salah.attendance.view',
            'quran.progress.view',
        ]), 403);

        $overview = $this->service->overviewStats();
        $todayQuran = $this->service->todayQuranAttendance();
        $todaySalah = $this->service->todaySalahAttendance();
        $quranSummary = $this->service->quranSummary();
        $salahSummary = $this->service->salahSummary();
        $trend = $this->service->attendanceTrend();

        return view('dashboard', compact(
            'overview',
            'todayQuran',
            'todaySalah',
            'quranSummary',
            'salahSummary',
            'trend',
        ));
    }
}
