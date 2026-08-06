@extends('layouts.app')

@section('title', __('teachers.teachers'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('teachers.teachers') }}</li>
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
        $activeFilters += $chip('branch_id', __('teachers.branches').': '.$branches[request('branch_id')]);
    }
    if (filled(request('status'))) {
        $case = App\Enums\Status::tryFrom((int) request('status'));
        if ($case) {
            $activeFilters += $chip('status', __('teachers.status').': '.$case->label());
        }
    }
@endphp

<x-page-header :title="__('teachers.teachers')"
               :subtitle="__('teachers.subtitle')"
               icon="bi-mortarboard"
               :badge="number_format($teachers->total())">
    <x-slot:actions>
        @can('create', App\Models\Teacher::class)
            <a href="{{ route('teachers.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>{{ __('teachers.add_new') }}</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('teachers.index')">
        <div class="flex-grow-1" style="min-width: 15rem;">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('teachers.search_placeholder') }}" value="{{ request('search') }}">
            </div>
        </div>

        <div style="min-width: 11rem;">
            <label for="filter_branch" class="form-label">{{ __('teachers.branches') }}</label>
            <select name="branch_id" id="filter_branch" class="form-select form-select-sm">
                <option value="">{{ __('teachers.all_branches') }}</option>
                @foreach ($branches as $id => $name)
                    <option value="{{ $id }}" @selected(request('branch_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 9rem;">
            <label for="filter_status" class="form-label">{{ __('teachers.status') }}</label>
            <select name="status" id="filter_status" class="form-select form-select-sm">
                <option value="">{{ __('teachers.all_statuses') }}</option>
                @foreach (App\Enums\Status::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === (string) $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </x-filters>

    <x-table sticky :label="__('teachers.teachers')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('teachers.teacher_name') }}</th>
                <th scope="col">{{ __('teachers.branches') }}</th>
                <th scope="col">{{ __('teachers.status') }}</th>
                <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('teachers.actions') }}</span></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($teachers as $teacher)
                @php($teacherName = $teacher->employee?->employee_name ?? $teacher->teacher_code)
                <tr>
                    <td class="col-num" data-label="#">{{ $teachers->firstItem() + $loop->index }}</td>

                    <td data-label="{{ __('teachers.teacher_name') }}">
                        <div class="cell-primary">
                            <x-avatar :name="$teacherName" />
                            <span class="cell-primary__text">
                                @can('view', $teacher)
                                    <a href="{{ route('teachers.show', $teacher) }}" class="cell-primary__title">{{ $teacherName }}</a>
                                @else
                                    <span class="cell-primary__title">{{ $teacherName }}</span>
                                @endcan
                                <span class="cell-primary__sub code-cell">{{ $teacher->teacher_code }}</span>
                            </span>
                        </div>
                    </td>

                    <td data-label="{{ __('teachers.branches') }}">
                        @forelse ($teacher->branches as $branch)
                            <span class="chip"><i class="bi bi-building" aria-hidden="true"></i><span>{{ $branch->branch_name }}</span></span>
                        @empty
                            <span class="dash">—</span>
                        @endforelse
                    </td>

                    <td data-label="{{ __('teachers.status') }}">
                        <x-status-badge :status="$teacher->status" />
                    </td>

                    <td class="col-actions" data-label="{{ __('teachers.actions') }}">
                        <div class="table-actions">
                            @can('view', $teacher)
                                <a href="{{ route('teachers.show', $teacher) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('teachers.view') }}"
                                   aria-label="{{ __('teachers.view') }} — {{ $teacherName }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                            @endcan
                            @can('update', $teacher)
                                <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('teachers.edit') }}"
                                   aria-label="{{ __('teachers.edit') }} — {{ $teacherName }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                            @endcan
                            @can('delete', $teacher)
                                <x-delete-button :action="route('teachers.destroy', $teacher)"
                                                 :record="$teacherName"
                                                 :title="__('teachers.delete')" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        @if ($activeFilters)
                            <x-empty-state icon="bi-search" :title="__('ui.no_results_title')" :text="__('ui.no_results_text')">
                                <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                    <span>{{ __('ui.clear_filters') }}</span>
                                </a>
                            </x-empty-state>
                        @else
                            <x-empty-state icon="bi-mortarboard" :title="__('teachers.empty_title')" :text="__('teachers.empty_text')">
                                @can('create', App\Models\Teacher::class)
                                    <a href="{{ route('teachers.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        <span>{{ __('teachers.add_new') }}</span>
                                    </a>
                                @endcan
                            </x-empty-state>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$teachers" />
</x-card>

@can('create', App\Models\Teacher::class)
    <a href="{{ route('teachers.create') }}" class="btn btn-primary btn-fab" aria-label="{{ __('teachers.add_new') }}">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
    </a>
@endcan
@endsection
