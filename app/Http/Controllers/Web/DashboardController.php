<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\PlatformDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service,
        private readonly PlatformDashboardService $platform,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // The platform account holds every permission and the company scope
        // steps aside for it, so the tenant dashboard would answer with every
        // company's employees added together — a number that means nothing.
        // It gets the register of tenants instead.
        if ($user instanceof User && $user->isSystemAdministrator()) {
            return $this->platformDashboard();
        }

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

    private function platformDashboard(): View
    {
        return view('platform-dashboard', [
            'overview' => $this->platform->overview(),
            'companies' => $this->platform->recentCompanies(),
            'attention' => $this->platform->subscriptionsNeedingAttention(),
        ]);
    }
}
