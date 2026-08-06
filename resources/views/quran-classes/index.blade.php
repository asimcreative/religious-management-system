@extends('layouts.app')

@section('title', __('quran_classes.quran_classes'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('quran_classes.quran_classes') }}</li>
@endsection

@section('content')
@php
    $chip = static fn (string $key, string $label) => [
        $label => request()->fullUrlWithQuery([$key => null, 'page' => null]),
    ];

    $activeFilters = [];

    if (filled(request('search'))) {
        $activeFilters += $chip('search', __('ui.search').': '.request('search'));
    }
    if (filled(request('branch_id')) && isset($branches[request('branch_id')])) {
        $activeFilters += $chip('branch_id', __('quran_classes.branch').': '.$branches[request('branch_id')]);
    }
    if (filled(request('teacher_id')) && isset($teachers[request('teacher_id')])) {
        $activeFilters += $chip('teacher_id', __('quran_classes.teacher').': '.$teachers[request('teacher_id')]);
    }
    if (filled(request('status'))) {
        $case = App\Enums\Status::tryFrom((int) request('status'));
        if ($case) {
            $activeFilters += $chip('status', __('quran_classes.status').': '.$case->label());
        }
    }
@endphp

<x-page-header :title="__('quran_classes.quran_classes')"
               :subtitle="__('quran_classes.subtitle')"
               icon="bi-book"
               :badge="number_format($classes->total())">
    <x-slot:actions>
        {{-- Add / Import / Export / Sample / Print — the same toolbar on every
             module, gated by this module's own permissions. --}}
        <x-data-toolbar resource="quran-classes"
                        :create-route="route('quran-classes.create')"
                        :create-model="App\Models\QuranClass::class"
                        :create-label="__('quran_classes.add_new')"
                        :filters="request()->query()"
                        selectable />
    </x-slot:actions>
</x-page-header>

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('quran-classes.index')">
        <div class="field field--grow">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('quran_classes.search_placeholder') }}" value="{{ request('search') }}">
            </div>
        </div>

        <div class="field field--md">
            <label for="filter_branch" class="form-label">{{ __('quran_classes.branch') }}</label>
            <select name="branch_id" id="filter_branch" class="form-select form-select-sm">
                <option value="">{{ __('quran_classes.all_branches') }}</option>
                @foreach ($branches as $id => $name)
                    <option value="{{ $id }}" @selected(request('branch_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--md">
            <label for="filter_teacher" class="form-label">{{ __('quran_classes.teacher') }}</label>
            <select name="teacher_id" id="filter_teacher" class="form-select form-select-sm">
                <option value="">{{ __('quran_classes.all_teachers') }}</option>
                @foreach ($teachers as $id => $name)
                    <option value="{{ $id }}" @selected(request('teacher_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--sm">
            <label for="filter_status" class="form-label">{{ __('quran_classes.status') }}</label>
            <select name="status" id="filter_status" class="form-select form-select-sm">
                <option value="">{{ __('quran_classes.all_statuses') }}</option>
                @foreach (App\Enums\Status::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === (string) $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </x-filters>

    <x-table sticky :label="__('quran_classes.quran_classes')">
        <thead>
            <tr>
                <th scope="col" class="col-select">
                    <x-bulk-select resource="quran-classes" all />
                </th>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('quran_classes.class_name') }}</th>
                <th scope="col">{{ __('quran_classes.teacher') }}</th>
                <th scope="col">{{ __('quran_classes.branch') }}</th>
                <th scope="col">{{ __('quran_classes.schedule') }}</th>
                <th scope="col">{{ __('quran_classes.strength') }}</th>
                <th scope="col">{{ __('quran_classes.status') }}</th>
                <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('quran_classes.actions') }}</span></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($classes as $class)
                @php
                    $isFull = $class->active_members_count >= $class->max_strength;
                    $fillPct = $class->max_strength > 0
                        ? min(100, (int) round($class->active_members_count / $class->max_strength * 100))
                        : 0;
                @endphp
                <tr>
                    <td class="col-select" data-label="">
                        <x-bulk-select resource="quran-classes" :id="$class->id" :label="$class->class_name" />
                    </td>
                    <td class="col-num" data-label="#">{{ $classes->firstItem() + $loop->index }}</td>

                    <td data-label="{{ __('quran_classes.class_name') }}">
                        <div class="cell-primary">
                            <span class="stat-card__icon tone-warning icon-circle-sm" aria-hidden="true">
                                <i class="bi bi-book"></i>
                            </span>
                            <span class="cell-primary__text">
                                @can('view', $class)
                                    <a href="{{ route('quran-classes.show', $class) }}" class="cell-primary__title">{{ $class->class_name }}</a>
                                @else
                                    <span class="cell-primary__title">{{ $class->class_name }}</span>
                                @endcan
                                <span class="cell-primary__sub code-cell">{{ $class->class_code }}</span>
                            </span>
                        </div>
                    </td>

                    <td data-label="{{ __('quran_classes.teacher') }}">
                        {{ $class->teacher?->employee?->employee_name ?? '—' }}
                    </td>

                    <td data-label="{{ __('quran_classes.branch') }}">
                        {{ $class->branch?->branch_name ?? '—' }}
                    </td>

                    <td data-label="{{ __('quran_classes.schedule') }}" class="col-fit">
                        @if ($class->start_time || $class->end_time)
                            <span class="tabular fs-sm">
                                {{ $class->start_time ? \Carbon\Carbon::parse($class->start_time)->format('h:i A') : '—' }}
                                –
                                {{ $class->end_time ? \Carbon\Carbon::parse($class->end_time)->format('h:i A') : '—' }}
                            </span>
                        @else
                            <span class="dash">—</span>
                        @endif
                    </td>

                    <td data-label="{{ __('quran_classes.strength') }}" class="col-fit">
                        <div class="d-flex align-items-center gap-2">
                            <span class="tabular fw-semibold {{ $isFull ? 'text-danger' : '' }}">
                                {{ $class->active_members_count }}/{{ $class->max_strength }}
                            </span>
                            <span class="progress d-none d-md-block meter-track--inline" role="img" aria-label="{{ $fillPct }}%">
                                <span class="progress-bar {{ $isFull ? 'bg-danger' : 'bg-success' }}" style="width: {{ $fillPct }}%"></span>
                            </span>
                            @if ($isFull)
                                <span class="badge-soft badge-soft-danger badge-soft--plain">{{ __('quran_classes.full') }}</span>
                            @endif
                        </div>
                    </td>

                    <td data-label="{{ __('quran_classes.status') }}">
                        <x-status-badge :status="$class->status" />
                    </td>

                    <td class="col-actions" data-label="{{ __('quran_classes.actions') }}">
                        <div class="table-actions">
                            @can('view', $class)
                                <a href="{{ route('quran-classes.show', $class) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('quran_classes.view') }}"
                                   aria-label="{{ __('quran_classes.view') }} — {{ $class->class_name }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                            @endcan
                            @can('update', $class)
                                <a href="{{ route('quran-classes.members.index', $class) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('quran_classes.manage_members') }}"
                                   aria-label="{{ __('quran_classes.manage_members') }} — {{ $class->class_name }}">
                                    <i class="bi bi-people" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('quran-classes.edit', $class) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('quran_classes.edit') }}"
                                   aria-label="{{ __('quran_classes.edit') }} — {{ $class->class_name }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                            @endcan
                            @can('delete', $class)
                                <x-delete-button :action="route('quran-classes.destroy', $class)"
                                                 :record="$class->class_name"
                                                 :title="__('quran_classes.delete')" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        @if ($activeFilters)
                            <x-empty-state icon="bi-search" :title="__('ui.no_results_title')" :text="__('ui.no_results_text')">
                                <a href="{{ route('quran-classes.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                    <span>{{ __('ui.clear_filters') }}</span>
                                </a>
                            </x-empty-state>
                        @else
                            <x-empty-state icon="bi-book" :title="__('quran_classes.empty_title')" :text="__('quran_classes.empty_text')">
                                @can('create', App\Models\QuranClass::class)
                                    <a href="{{ route('quran-classes.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        <span>{{ __('quran_classes.add_new') }}</span>
                                    </a>
                                @endcan
                            </x-empty-state>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$classes" />
</x-card>

@can('create', App\Models\QuranClass::class)
    <a href="{{ route('quran-classes.create') }}" class="btn btn-primary btn-fab" aria-label="{{ __('quran_classes.add_new') }}">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
    </a>
@endcan
@endsection
