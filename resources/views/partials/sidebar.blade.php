{{--
    Primary navigation.

    Flat and permission-filtered: every module the user may open is one click
    away, with no nested menus to discover. Section headings group the modules
    without adding interaction cost. In the collapsed icon-rail the same links
    stay reachable and are labelled by a tooltip (added by ui.js only while
    collapsed, so expanded labels are never duplicated).

    The menu is declared as data below, so adding a module means adding one row
    — not copying markup.

    Note: no Eloquent relationships are touched here. Model::preventLazyLoading
    is enabled outside production, so the shell must never read a relation.
--}}
@php
    $navSections = [
        [
            'label' => __('ui.nav_overview'),
            'items' => [
                ['permission' => null, 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'bi-grid-1x2', 'label' => __('dashboard.dashboard'), 'scope' => 'both'],
                ['permission' => 'notification.view', 'route' => 'notifications.index', 'active' => 'notifications.*', 'icon' => 'bi-bell', 'label' => __('notifications.notifications'), 'badge' => $unreadNotificationCount ?? 0, 'scope' => 'both'],
            ],
        ],
        [
            'label' => __('ui.nav_platform'),
            'items' => [
                ['permission' => 'company.view', 'route' => 'companies.index', 'active' => 'companies.*', 'icon' => 'bi-buildings', 'label' => __('companies.companies'), 'scope' => 'platform'],
            ],
        ],
        [
            'label' => __('ui.nav_people'),
            'items' => [
                ['permission' => 'employee.view', 'route' => 'employees.index', 'active' => 'employees.*', 'icon' => 'bi-people', 'label' => __('employees.employees')],
                ['permission' => 'teacher.view', 'route' => 'teachers.index', 'active' => 'teachers.*', 'icon' => 'bi-mortarboard', 'label' => __('teachers.teachers')],
            ],
        ],
        [
            'label' => __('ui.nav_quran'),
            'items' => [
                ['permission' => 'quran.class.view', 'route' => 'quran-classes.index', 'active' => 'quran-classes.*', 'icon' => 'bi-book', 'label' => __('quran_classes.quran_classes')],
                ['permission' => 'quran.attendance.view', 'route' => 'quran-attendance.index', 'active' => 'quran-attendance.*', 'icon' => 'bi-clipboard-check', 'label' => __('quran_attendance.quran_attendance')],
                ['permission' => 'quran.progress.view', 'route' => 'quran-progress.index', 'active' => 'quran-progress.*', 'icon' => 'bi-graph-up-arrow', 'label' => __('quran_progress.quran_progress')],
            ],
        ],
        [
            'label' => __('ui.nav_salah'),
            'items' => [
                ['permission' => 'jamaat.view', 'route' => 'jamaats.index', 'active' => 'jamaats.*', 'icon' => 'bi-people-fill', 'label' => __('jamaats.jamaats')],
                ['permission' => 'salah.attendance.view', 'route' => 'salah-attendance.index', 'active' => 'salah-attendance.*', 'icon' => 'bi-moon-stars', 'label' => __('salah_attendance.salah_attendance')],
            ],
        ],
        [
            'label' => __('ui.nav_insights'),
            'items' => [
                ['permission' => ['report.employee', 'report.teacher', 'report.quran', 'report.salah', 'report.dashboard'], 'route' => 'reports.index', 'active' => 'reports.index', 'icon' => 'bi-collection', 'label' => __('reports.report_center')],
                ['permission' => 'report.dashboard', 'route' => 'reports.dashboard', 'active' => 'reports.dashboard', 'icon' => 'bi-pie-chart', 'label' => __('reports.dashboard_summary')],
                ['permission' => 'report.employee', 'route' => 'reports.employees', 'active' => 'reports.employees', 'icon' => 'bi-file-earmark-person', 'label' => __('reports.employee_report')],
                ['permission' => 'report.teacher', 'route' => 'reports.teachers', 'active' => 'reports.teachers', 'icon' => 'bi-file-earmark-text', 'label' => __('reports.teacher_report')],
                ['permission' => 'report.quran', 'route' => 'reports.quran-attendance', 'active' => 'reports.quran-attendance', 'icon' => 'bi-journal-check', 'label' => __('reports.quran_attendance_report')],
                ['permission' => 'report.quran', 'route' => 'reports.quran-progress', 'active' => 'reports.quran-progress', 'icon' => 'bi-journal-bookmark', 'label' => __('reports.quran_progress_report')],
                ['permission' => 'report.salah', 'route' => 'reports.salah-attendance', 'active' => 'reports.salah-attendance', 'icon' => 'bi-calendar-check', 'label' => __('reports.salah_attendance_report')],
            ],
        ],
        [
            'label' => __('ui.nav_configuration'),
            'items' => [
                ['permission' => 'user.view', 'route' => 'users.index', 'active' => 'users.*', 'icon' => 'bi-person-badge', 'label' => __('users.users')],
                ['permission' => 'role.view', 'route' => 'roles.index', 'active' => 'roles.*', 'icon' => 'bi-shield-lock', 'label' => __('roles.roles')],
                ['permission' => 'branch.manage', 'route' => 'masters.branches.index', 'active' => 'masters.branches.*', 'icon' => 'bi-building', 'label' => __('masters.branches')],
                ['permission' => 'department.manage', 'route' => 'masters.departments.index', 'active' => 'masters.departments.*', 'icon' => 'bi-diagram-3', 'label' => __('masters.departments')],
                ['permission' => 'designation.manage', 'route' => 'masters.designations.index', 'active' => 'masters.designations.*', 'icon' => 'bi-award', 'label' => __('masters.designations')],
                ['permission' => 'attendance_reason.manage', 'route' => 'masters.attendance-reasons.index', 'active' => 'masters.attendance-reasons.*', 'icon' => 'bi-chat-left-text', 'label' => __('masters.attendance_reasons')],
                ['permission' => 'quran_department.manage', 'route' => 'masters.quran-departments.index', 'active' => 'masters.quran-departments.*', 'icon' => 'bi-bookmarks', 'label' => __('masters.quran_departments')],
                ['permission' => 'quran_status.manage', 'route' => 'masters.quran-statuses.index', 'active' => 'masters.quran-statuses.*', 'icon' => 'bi-patch-check', 'label' => __('masters.quran_statuses')],
                ['permission' => 'language.manage', 'route' => 'masters.languages.index', 'active' => 'masters.languages.*', 'icon' => 'bi-translate', 'label' => __('masters.languages')],
                ['permission' => 'settings.view', 'route' => 'settings.index', 'active' => 'settings.*', 'icon' => 'bi-sliders', 'label' => __('settings.settings')],
            ],
        ],
    ];

    $user = auth()->user();

    // The platform account administers companies and holds no data of its own.
    // Showing it every tenant module would invite it to create records that get
    // stamped with the platform's own company_id, so the menu is split the same
    // way EnforcePlatformBoundary splits the routes behind it. While it is
    // viewing a tenant the authenticated user is that tenant's own user, so the
    // tenant menu returns without a special case.
    $isPlatformAccount = (bool) $user?->isSystemAdministrator();

    $maySee = static function (array $item) use ($user, $isPlatformAccount): bool {
        $scope = $item['scope'] ?? 'tenant';

        if ($scope !== 'both' && $isPlatformAccount !== ($scope === 'platform')) {
            return false;
        }

        $permission = $item['permission'];

        if ($permission === null) {
            return true;
        }

        foreach ((array) $permission as $ability) {
            if ($user?->can($ability)) {
                return true;
            }
        }

        return false;
    };
@endphp

<aside class="rams-sidebar" id="ramsSidebar" data-sidebar aria-label="{{ __('ui.main_navigation') }}">

    {{-- Brand --}}
    {{-- The second line names the tenant the user is working in — essential
         context in a multi-tenant system. Falls back to the tagline. --}}
    <a class="rams-sidebar__brand" href="{{ route('dashboard') }}">
        <span class="rams-sidebar__logo" aria-hidden="true"><i class="bi bi-moon-stars-fill"></i></span>
        <span class="rams-sidebar__title">
            <strong>{{ config('app.name', 'RAMS') }}</strong>
            <span @if (! empty($currentCompanyName)) title="{{ $currentCompanyName }}" @endif>
                {{ $currentCompanyName ?: __('ui.app_short_tagline') }}
            </span>
        </span>
    </a>

    <nav class="rams-sidebar__nav">
        @foreach ($navSections as $section)
            @php($visible = array_values(array_filter($section['items'], fn ($item) => $maySee($item))))
            @continue(empty($visible))

            <div data-nav-group="{{ $section['label'] }}">
                <p class="rams-sidebar__section">{{ $section['label'] }}</p>

                @foreach ($visible as $item)
                    @php($isActive = request()->routeIs($item['active']))

                    <a href="{{ route($item['route']) }}"
                       class="rams-sidebar__link {{ $isActive ? 'is-active' : '' }}"
                       data-rail-tip="{{ $item['label'] }}"
                       @if ($isActive) aria-current="page" @endif>
                        <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span class="rams-sidebar__label">{{ $item['label'] }}</span>

                        @if (($item['badge'] ?? 0) > 0)
                            <span class="rams-sidebar__badge">
                                {{ $item['badge'] }}
                                <span class="visually-hidden">{{ __('notifications.unread') }}</span>
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    {{-- Collapse control (desktop only) --}}
    <div class="rams-sidebar__footer">
        <button type="button"
                class="rams-sidebar__collapse-btn"
                data-sidebar-collapse
                aria-controls="ramsSidebar"
                aria-expanded="true">
            <i class="bi bi-chevron-double-left" aria-hidden="true"></i>
            <span>{{ __('ui.collapse_menu') }}</span>
        </button>
    </div>
</aside>
