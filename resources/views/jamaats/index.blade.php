@extends('layouts.app')

@section('title', __('jamaats.jamaats'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('jamaats.jamaats') }}</li>
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
        $activeFilters += $chip('branch_id', __('jamaats.branch').': '.$branches[request('branch_id')]);
    }
    if (filled(request('status'))) {
        $case = App\Enums\Status::tryFrom((int) request('status'));
        if ($case) {
            $activeFilters += $chip('status', __('jamaats.status').': '.$case->label());
        }
    }
@endphp

<x-page-header :title="__('jamaats.jamaats')"
               :subtitle="__('jamaats.subtitle')"
               icon="bi-people-fill"
               :badge="number_format($jamaats->total())">
    <x-slot:actions>
        {{-- Add / Import / Export / Sample / Print — the same toolbar on every
             module, gated by this module's own permissions. --}}
        <x-data-toolbar resource="jamaats"
                        :create-route="route('jamaats.create')"
                        :create-model="App\Models\Jamaat::class"
                        :create-label="__('jamaats.add_new')"
                        :filters="request()->query()"
                        selectable />
    </x-slot:actions>
</x-page-header>

<x-card flush>
    <x-filters :active="$activeFilters" :reset-url="route('jamaats.index')">
        <div class="field field--grow">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('jamaats.search_placeholder') }}" value="{{ request('search') }}">
            </div>
        </div>

        <div class="field field--md">
            <label for="filter_branch" class="form-label">{{ __('jamaats.branch') }}</label>
            <select name="branch_id" id="filter_branch" class="form-select form-select-sm">
                <option value="">{{ __('jamaats.all_branches') }}</option>
                @foreach ($branches as $id => $name)
                    <option value="{{ $id }}" @selected(request('branch_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="field field--sm">
            <label for="filter_status" class="form-label">{{ __('jamaats.status') }}</label>
            <select name="status" id="filter_status" class="form-select form-select-sm">
                <option value="">{{ __('jamaats.all_statuses') }}</option>
                @foreach (App\Enums\Status::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === (string) $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </x-filters>

    <x-table sticky :label="__('jamaats.jamaats')">
        <thead>
            <tr>
                <th scope="col" class="col-select">
                    <x-bulk-select resource="jamaats" all />
                </th>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('jamaats.jamaat_name') }}</th>
                <th scope="col">{{ __('jamaats.leader') }}</th>
                <th scope="col">{{ __('jamaats.branch') }}</th>
                <th scope="col">{{ __('jamaats.members_count') }}</th>
                <th scope="col">{{ __('jamaats.status') }}</th>
                <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('jamaats.actions') }}</span></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($jamaats as $jamaat)
                <tr>
                    <td class="col-select" data-label="">
                        <x-bulk-select resource="jamaats" :id="$jamaat->id" :label="$jamaat->jamaat_name" />
                    </td>
                    <td class="col-num" data-label="#">{{ $jamaats->firstItem() + $loop->index }}</td>

                    <td data-label="{{ __('jamaats.jamaat_name') }}">
                        <div class="cell-primary">
                            <span class="stat-card__icon tone-accent icon-circle-sm" aria-hidden="true">
                                <i class="bi bi-people-fill"></i>
                            </span>
                            <span class="cell-primary__text">
                                @can('view', $jamaat)
                                    <a href="{{ route('jamaats.show', $jamaat) }}" class="cell-primary__title">{{ $jamaat->jamaat_name }}</a>
                                @else
                                    <span class="cell-primary__title">{{ $jamaat->jamaat_name }}</span>
                                @endcan
                                <span class="cell-primary__sub code-cell">{{ $jamaat->jamaat_number }}</span>
                            </span>
                        </div>
                    </td>

                    <td data-label="{{ __('jamaats.leader') }}">{{ $jamaat->leader?->employee_name ?? '—' }}</td>

                    <td data-label="{{ __('jamaats.branch') }}">{{ $jamaat->branch?->branch_name ?? '—' }}</td>

                    <td data-label="{{ __('jamaats.members_count') }}" class="col-fit">
                        <span class="badge-soft badge-soft-neutral badge-soft--plain tabular">
                            <i class="bi bi-person" aria-hidden="true"></i>{{ $jamaat->active_members_count }}
                        </span>
                    </td>

                    <td data-label="{{ __('jamaats.status') }}">
                        <x-status-badge :status="$jamaat->status" />
                    </td>

                    <td class="col-actions" data-label="{{ __('jamaats.actions') }}">
                        <div class="table-actions">
                            @can('view', $jamaat)
                                <a href="{{ route('jamaats.show', $jamaat) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('jamaats.view') }}"
                                   aria-label="{{ __('jamaats.view') }} — {{ $jamaat->jamaat_name }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                            @endcan
                            @can('update', $jamaat)
                                <a href="{{ route('jamaats.members.index', $jamaat) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('jamaats.manage_members') }}"
                                   aria-label="{{ __('jamaats.manage_members') }} — {{ $jamaat->jamaat_name }}">
                                    <i class="bi bi-people" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('jamaats.edit', $jamaat) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('jamaats.edit') }}"
                                   aria-label="{{ __('jamaats.edit') }} — {{ $jamaat->jamaat_name }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                            @endcan
                            @can('delete', $jamaat)
                                <x-delete-button :action="route('jamaats.destroy', $jamaat)"
                                                 :record="$jamaat->jamaat_name"
                                                 :title="__('jamaats.delete')" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        @if ($activeFilters)
                            <x-empty-state icon="bi-search" :title="__('ui.no_results_title')" :text="__('ui.no_results_text')">
                                <a href="{{ route('jamaats.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                    <span>{{ __('ui.clear_filters') }}</span>
                                </a>
                            </x-empty-state>
                        @else
                            <x-empty-state icon="bi-people-fill" :title="__('jamaats.empty_title')" :text="__('jamaats.empty_text')">
                                @can('create', App\Models\Jamaat::class)
                                    <a href="{{ route('jamaats.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        <span>{{ __('jamaats.add_new') }}</span>
                                    </a>
                                @endcan
                            </x-empty-state>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$jamaats" />
</x-card>

@can('create', App\Models\Jamaat::class)
    <a href="{{ route('jamaats.create') }}" class="btn btn-primary btn-fab" aria-label="{{ __('jamaats.add_new') }}">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
    </a>
@endcan
@endsection
