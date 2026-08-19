<?php

namespace App\Providers;

use App\Contracts\Repositories\AttendanceReasonRepositoryInterface;
use App\Contracts\Repositories\BranchRepositoryInterface;
use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\DepartmentRepositoryInterface;
use App\Contracts\Repositories\DesignationRepositoryInterface;
use App\Contracts\Repositories\EmployeeRepositoryInterface;
use App\Contracts\Repositories\JamaatRepositoryInterface;
use App\Contracts\Repositories\JamaatTaleemRepositoryInterface;
use App\Contracts\Repositories\LanguageRepositoryInterface;
use App\Contracts\Repositories\QuranAttendanceRepositoryInterface;
use App\Contracts\Repositories\QuranClassRepositoryInterface;
use App\Contracts\Repositories\QuranDepartmentRepositoryInterface;
use App\Contracts\Repositories\QuranProgressRepositoryInterface;
use App\Contracts\Repositories\QuranStatusRepositoryInterface;
use App\Contracts\Repositories\QuranTeacherAttendanceRepositoryInterface;
use App\Contracts\Repositories\SalahAttendanceRepositoryInterface;
use App\Contracts\Repositories\TeacherRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\AttendanceReasonRepository;
use App\Repositories\BranchRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\DesignationRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\JamaatRepository;
use App\Repositories\JamaatTaleemRepository;
use App\Repositories\LanguageRepository;
use App\Repositories\QuranAttendanceRepository;
use App\Repositories\QuranClassRepository;
use App\Repositories\QuranDepartmentRepository;
use App\Repositories\QuranProgressRepository;
use App\Repositories\QuranStatusRepository;
use App\Repositories\QuranTeacherAttendanceRepository;
use App\Repositories\SalahAttendanceRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AttendanceReasonRepositoryInterface::class, AttendanceReasonRepository::class);
        $this->app->bind(BranchRepositoryInterface::class, BranchRepository::class);
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(DesignationRepositoryInterface::class, DesignationRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(JamaatRepositoryInterface::class, JamaatRepository::class);
        $this->app->bind(JamaatTaleemRepositoryInterface::class, JamaatTaleemRepository::class);
        $this->app->bind(LanguageRepositoryInterface::class, LanguageRepository::class);
        $this->app->bind(QuranAttendanceRepositoryInterface::class, QuranAttendanceRepository::class);
        $this->app->bind(QuranClassRepositoryInterface::class, QuranClassRepository::class);
        $this->app->bind(QuranDepartmentRepositoryInterface::class, QuranDepartmentRepository::class);
        $this->app->bind(QuranProgressRepositoryInterface::class, QuranProgressRepository::class);
        $this->app->bind(QuranStatusRepositoryInterface::class, QuranStatusRepository::class);
        $this->app->bind(QuranTeacherAttendanceRepositoryInterface::class, QuranTeacherAttendanceRepository::class);
        $this->app->bind(SalahAttendanceRepositoryInterface::class, SalahAttendanceRepository::class);
        $this->app->bind(TeacherRepositoryInterface::class, TeacherRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }
}
