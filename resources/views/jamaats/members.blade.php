@extends('layouts.app')

@section('title', __('jamaats.manage_members').' — '.$jamaat->jamaat_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('jamaats.index') }}">{{ __('jamaats.jamaats') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('jamaats.show', $jamaat) }}">{{ $jamaat->jamaat_name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('jamaats.manage_members') }}</li>
@endsection

@section('content')
<x-page-header :title="__('jamaats.manage_members')"
               :subtitle="$jamaat->jamaat_name.' · '.$jamaat->jamaat_number"
               icon="bi-people">
    <x-slot:actions>
        <a href="{{ route('jamaats.show', $jamaat) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('jamaats.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3">

    {{-- ── Add member ───────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-4">
        <x-card :title="__('jamaats.add_member')" icon="bi-person-plus">
            @if ($availableEmployees->isNotEmpty())
                <form method="POST" action="{{ route('jamaats.members.store', $jamaat) }}" novalidate>
                    @csrf

                    <x-form.select name="employee_id"
                                   :label="__('jamaats.employee')"
                                   :placeholder="__('jamaats.select_employee')"
                                   :help="__('jamaats.add_member_help')"
                                   required>
                        @foreach ($availableEmployees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) old('employee_id') === $employee->id)>
                                {{ $employee->employee_name }} ({{ $employee->employee_code }})
                            </option>
                        @endforeach
                    </x-form.select>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>{{ __('jamaats.add_member') }}</span>
                    </button>
                </form>
            @else
                <x-empty-state size="sm"
                               icon="bi-person-x"
                               :title="__('jamaats.no_employees_available')"
                               :text="__('jamaats.no_employees_available_text')" />
            @endif
        </x-card>
    </div>

    {{-- ── Member list ──────────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-8">
        <x-card :title="__('jamaats.active_members')" icon="bi-people" flush>
            <x-slot:actions>
                <span class="badge-soft badge-soft-primary">{{ $members->count() }}</span>
            </x-slot:actions>

            @if ($members->count() > 5)
                <div class="table-toolbar">
                    <div class="input-icon w-cap-lg">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" class="form-control form-control-sm"
                               data-table-filter="#jamaatMembers"
                               placeholder="{{ __('ui.quick_find') }}"
                               aria-label="{{ __('ui.quick_find') }}">
                    </div>
                    <span class="table-toolbar__actions"
                          data-filter-count="#jamaatMembers"
                          data-template="{{ __('ui.filter_count', ['shown' => ':shown', 'total' => ':total']) }}"></span>
                </div>
            @endif

            <x-table id="jamaatMembers" :label="__('jamaats.active_members')">
                <thead>
                    <tr>
                        <th scope="col" class="col-num">#</th>
                        <th scope="col">{{ __('jamaats.employee_name') }}</th>
                        <th scope="col">{{ __('jamaats.employee_code') }}</th>
                        <th scope="col">{{ __('jamaats.joined_at') }}</th>
                        <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('jamaats.actions') }}</span></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td class="col-num" data-label="#">{{ $loop->iteration }}</td>

                            <td data-label="{{ __('jamaats.employee_name') }}">
                                <div class="cell-primary">
                                    <x-avatar :name="$member->employee?->employee_name ?? ''" />
                                    <span class="cell-primary__text">
                                        <span class="cell-primary__title">{{ $member->employee?->employee_name ?? '—' }}</span>
                                    </span>
                                </div>
                            </td>

                            <td data-label="{{ __('jamaats.employee_code') }}">
                                <span class="code-cell">{{ $member->employee?->employee_code }}</span>
                            </td>

                            <td data-label="{{ __('jamaats.joined_at') }}">
                                {{ $member->joined_at?->format('d M Y') ?? '—' }}
                            </td>

                            <td class="col-actions" data-label="{{ __('jamaats.actions') }}">
                                <x-delete-button
                                    :action="route('jamaats.members.destroy', [$jamaat, $member->employee_id])"
                                    :confirm="__('ui.confirm_remove_member', ['name' => $member->employee?->employee_name ?? ''])"
                                    :title="__('jamaats.remove_member')"
                                    :label="__('jamaats.remove_member')"
                                    icon="bi-person-dash" />
                            </td>
                        </tr>
                    @empty
                        <tr data-filter-empty>
                            <td colspan="5">
                                <x-empty-state icon="bi-person-plus"
                                               :title="__('jamaats.no_active_members')"
                                               :text="__('jamaats.no_active_members_text')" />
                            </td>
                        </tr>
                    @endforelse

                    @if ($members->count() > 0)
                        <tr data-filter-empty hidden>
                            <td colspan="5">
                                <x-empty-state size="sm" icon="bi-search" :title="__('ui.no_results_title')" />
                            </td>
                        </tr>
                    @endif
                </tbody>
            </x-table>
        </x-card>
    </div>
</div>
@endsection
