<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'RAMS') }}</title>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                {{ config('app.name', 'RAMS') }}
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                           href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>

                    {{-- Employees --}}
                    @can('employee.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}"
                               href="{{ route('employees.index') }}">
                                <i class="bi bi-people me-1"></i> {{ __('employees.employees') }}
                            </a>
                        </li>
                    @endcan

                    {{-- Teachers --}}
                    @can('teacher.view')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('teachers.*') ? 'active' : '' }}"
                               href="{{ route('teachers.index') }}">
                                <i class="bi bi-mortarboard me-1"></i> {{ __('teachers.teachers') }}
                            </a>
                        </li>
                    @endcan

                    {{-- Quran Module Dropdown --}}
                    @canany(['quran.class.view', 'quran.attendance.view', 'quran.progress.view'])
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('quran-classes.*', 'quran-attendance.*', 'quran-progress.*') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-book me-1"></i> {{ __('quran_classes.quran_classes') }}
                            </a>
                            <ul class="dropdown-menu">
                                @can('quran.class.view')
                                    <li><a class="dropdown-item" href="{{ route('quran-classes.index') }}">{{ __('quran_classes.quran_classes') }}</a></li>
                                @endcan
                                @can('quran.attendance.view')
                                    <li><a class="dropdown-item" href="{{ route('quran-attendance.index') }}">{{ __('quran_attendance.quran_attendance') }}</a></li>
                                @endcan
                                @can('quran.progress.view')
                                    <li><a class="dropdown-item" href="{{ route('quran-progress.index') }}">{{ __('quran_progress.quran_progress') }}</a></li>
                                @endcan
                            </ul>
                        </li>
                    @endcanany

                    {{-- Salah Module Dropdown --}}
                    @canany(['jamaat.view', 'salah.attendance.view'])
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('jamaats.*', 'salah-attendance.*') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-moon me-1"></i> {{ __('jamaats.salah_module') }}
                            </a>
                            <ul class="dropdown-menu">
                                @can('jamaat.view')
                                    <li><a class="dropdown-item" href="{{ route('jamaats.index') }}">{{ __('jamaats.jamaats') }}</a></li>
                                @endcan
                                @can('salah.attendance.view')
                                    <li><a class="dropdown-item" href="{{ route('salah-attendance.index') }}">{{ __('salah_attendance.salah_attendance') }}</a></li>
                                @endcan
                            </ul>
                        </li>
                    @endcanany

                    {{-- Reports Dropdown --}}
                    @canany(['report.employee', 'report.teacher', 'report.quran', 'report.salah', 'report.dashboard'])
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-bar-chart me-1"></i> {{ __('reports.reports') }}
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('reports.index') }}">{{ __('reports.report_center') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                @can('report.employee')
                                    <li><a class="dropdown-item" href="{{ route('reports.employees') }}">{{ __('reports.employee_report') }}</a></li>
                                @endcan
                                @can('report.teacher')
                                    <li><a class="dropdown-item" href="{{ route('reports.teachers') }}">{{ __('reports.teacher_report') }}</a></li>
                                @endcan
                                @can('report.quran')
                                    <li><a class="dropdown-item" href="{{ route('reports.quran-attendance') }}">{{ __('reports.quran_attendance_report') }}</a></li>
                                    <li><a class="dropdown-item" href="{{ route('reports.quran-progress') }}">{{ __('reports.quran_progress_report') }}</a></li>
                                @endcan
                                @can('report.salah')
                                    <li><a class="dropdown-item" href="{{ route('reports.salah-attendance') }}">{{ __('reports.salah_attendance_report') }}</a></li>
                                @endcan
                                @can('report.dashboard')
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('reports.dashboard') }}">{{ __('reports.dashboard_summary') }}</a></li>
                                @endcan
                            </ul>
                        </li>
                    @endcanany

                    {{-- Master Data Dropdown --}}
                    @canany(['branch.manage', 'department.manage', 'designation.manage', 'attendance_reason.manage', 'quran_department.manage', 'quran_status.manage', 'language.manage'])
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('masters.*') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-gear me-1"></i> {{ __('masters.master_data') }}
                            </a>
                            <ul class="dropdown-menu">
                                @can('branch.manage')
                                    <li><a class="dropdown-item" href="{{ route('masters.branches.index') }}">{{ __('masters.branches') }}</a></li>
                                @endcan
                                @can('department.manage')
                                    <li><a class="dropdown-item" href="{{ route('masters.departments.index') }}">{{ __('masters.departments') }}</a></li>
                                @endcan
                                @can('designation.manage')
                                    <li><a class="dropdown-item" href="{{ route('masters.designations.index') }}">{{ __('masters.designations') }}</a></li>
                                @endcan
                                @can('attendance_reason.manage')
                                    <li><a class="dropdown-item" href="{{ route('masters.attendance-reasons.index') }}">{{ __('masters.attendance_reasons') }}</a></li>
                                @endcan
                                <li><hr class="dropdown-divider"></li>
                                @can('quran_department.manage')
                                    <li><a class="dropdown-item" href="{{ route('masters.quran-departments.index') }}">{{ __('masters.quran_departments') }}</a></li>
                                @endcan
                                @can('quran_status.manage')
                                    <li><a class="dropdown-item" href="{{ route('masters.quran-statuses.index') }}">{{ __('masters.quran_statuses') }}</a></li>
                                @endcan
                                @can('language.manage')
                                    <li><a class="dropdown-item" href="{{ route('masters.languages.index') }}">{{ __('masters.languages') }}</a></li>
                                @endcan
                            </ul>
                        </li>
                    @endcanany
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            {{ Auth::user()?->name ?? '' }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ route('password.change.form') }}">
                                    <i class="bi bi-key me-1"></i>
                                    {{ __('auth.change_password') }}
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-box-arrow-right me-1"></i>
                                        {{ __('auth.logout') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="py-4">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="container-fluid">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="container-fluid">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif

        <div class="container-fluid">
            @yield('content')
        </div>
    </main>

</body>
</html>
