@extends('layouts.app')

@section('title', __('quran_classes.manage_members').' — '.$quranClass->class_name)

@php
    $canManage = auth()->user()?->can('update', $quranClass) ?? false;
    $memberCount = $activeMembers->count();
    $fillPct = $quranClass->max_strength > 0
        ? min(100, (int) round($memberCount / $quranClass->max_strength * 100))
        : 0;
@endphp

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('quran-classes.index') }}">{{ __('quran_classes.quran_classes') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('quran-classes.show', $quranClass) }}">{{ $quranClass->class_name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('quran_classes.manage_members') }}</li>
@endsection

@section('content')
<x-page-header :title="__('quran_classes.manage_members')"
               :subtitle="$quranClass->class_name.' · '.$quranClass->class_code"
               icon="bi-people">
    <x-slot:actions>
        <a href="{{ route('quran-classes.show', $quranClass) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('quran_classes.back_to_detail') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3">

    {{-- ── Add member ───────────────────────────────────────────────────── --}}
    @if ($canManage)
        <div class="col-12 col-lg-4">
            <x-card :title="__('quran_classes.add_member')" icon="bi-person-plus" class="mb-3">
                <div class="mb-3">
                    <div class="d-flex align-items-baseline justify-content-between mb-1">
                        <span class="fs-sm text-soft">{{ __('quran_classes.strength') }}</span>
                        <span class="fw-semibold tabular {{ $quranClass->isFull() ? 'text-danger' : '' }}">
                            {{ $memberCount }} / {{ $quranClass->max_strength }}
                        </span>
                    </div>
                    <div class="progress" role="img" aria-label="{{ $fillPct }}%">
                        <div class="progress-bar {{ $quranClass->isFull() ? 'bg-danger' : 'bg-success' }}" style="width: {{ $fillPct }}%"></div>
                    </div>
                </div>

                @if ($quranClass->isFull())
                    <div class="alert alert-warning mb-0" role="alert">
                        <i class="bi bi-exclamation-triangle-fill alert__icon" aria-hidden="true"></i>
                        <div class="alert__body">
                            <strong class="alert__title">{{ __('quran_classes.full') }}</strong>
                            {{ __('quran_classes.class_full') }}
                        </div>
                    </div>
                @elseif ($availableEmployees->isEmpty())
                    <x-empty-state size="sm"
                                   icon="bi-person-x"
                                   :title="__('quran_classes.no_available_employees')"
                                   :text="__('quran_classes.no_available_employees_text')" />
                @else
                    <form method="POST" action="{{ route('quran-classes.members.store', $quranClass) }}" novalidate>
                        @csrf

                        <x-form.select name="employee_id"
                                       :label="__('quran_classes.select_employee')"
                                       :placeholder="__('quran_classes.select_employee')"
                                       :help="__('quran_classes.add_member_help')"
                                       data-searchable-select
                                       required>
                            @foreach ($availableEmployees as $employee)
                                <option value="{{ $employee->id }}" @selected((int) old('employee_id') === $employee->id)>
                                    {{ $employee->employee_name }} ({{ $employee->employee_code }})
                                </option>
                            @endforeach
                        </x-form.select>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            <span>{{ __('quran_classes.add_member') }}</span>
                        </button>
                    </form>
                @endif
            </x-card>
        </div>
    @endif

    {{-- ── Member list ──────────────────────────────────────────────────── --}}
    <div class="{{ $canManage ? 'col-12 col-lg-8' : 'col-12' }}">
        <x-card :title="__('quran_classes.active_members')" icon="bi-people" flush>
            <x-slot:actions>
                <span class="badge-soft badge-soft-primary">{{ $memberCount }}</span>
            </x-slot:actions>

            @if ($memberCount > 5)
                <div class="table-toolbar">
                    <div class="input-icon w-cap-lg">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search" class="form-control form-control-sm"
                               data-table-filter="#classMembers"
                               placeholder="{{ __('ui.quick_find') }}"
                               aria-label="{{ __('ui.quick_find') }}">
                    </div>
                    <span class="table-toolbar__actions"
                          data-filter-count="#classMembers"
                          data-template="{{ __('ui.filter_count', ['shown' => ':shown', 'total' => ':total']) }}"></span>
                </div>
            @endif

            <x-table id="classMembers" :label="__('quran_classes.active_members')">
                <thead>
                    <tr>
                        <th scope="col" class="col-num">#</th>
                        <th scope="col">{{ __('employees.employee_name') }}</th>
                        <th scope="col">{{ __('employees.employee_code') }}</th>
                        <th scope="col">{{ __('quran_classes.joined_at') }}</th>
                        <th scope="col">{{ __('quran_classes.admission_form') }}</th>
                        @if ($canManage)
                            <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('quran_classes.actions') }}</span></th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @forelse ($activeMembers as $member)
                        <tr>
                            <td class="col-num" data-label="#">{{ $loop->iteration }}</td>

                            <td data-label="{{ __('employees.employee_name') }}">
                                <div class="cell-primary">
                                    <x-avatar :name="$member->employee_name" />
                                    <span class="cell-primary__text">
                                        <a href="{{ route('employees.show', $member) }}" class="cell-primary__title">{{ $member->employee_name }}</a>
                                    </span>
                                </div>
                            </td>

                            <td data-label="{{ __('employees.employee_code') }}">
                                <span class="code-cell">{{ $member->employee_code }}</span>
                            </td>

                            <td data-label="{{ __('quran_classes.joined_at') }}">
                                {{ $member->pivot->joined_at ? \Carbon\Carbon::parse($member->pivot->joined_at)->format('d M Y') : '—' }}
                            </td>

                            <td data-label="{{ __('quran_classes.admission_form') }}">
                                @if (in_array($member->pivot->id, $admittedMemberIds, true))
                                    <span class="badge-soft badge-soft-success">{{ __('quran_classes.admission_complete') }}</span>
                                @else
                                    <span class="badge-soft badge-soft-warning">{{ __('quran_classes.admission_pending') }}</span>
                                    @if ($canManage)
                                        <a href="{{ route('quran-classes.members.admission.create', [$quranClass, $member]) }}" class="fs-xs d-block">
                                            {{ __('quran_classes.fill_admission_form') }}
                                        </a>
                                    @endif
                                @endif
                            </td>

                            @if ($canManage)
                                <td class="col-actions" data-label="{{ __('quran_classes.actions') }}">
                                    <x-delete-button
                                        :action="route('quran-classes.members.destroy', [$quranClass, $member])"
                                        :confirm="__('ui.confirm_remove_member', ['name' => $member->employee_name])"
                                        :title="__('quran_classes.remove_member')"
                                        :label="__('quran_classes.remove_member')"
                                        icon="bi-person-dash" />
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr data-filter-empty>
                            <td colspan="{{ $canManage ? 6 : 5 }}">
                                <x-empty-state icon="bi-person-plus"
                                               :title="__('quran_classes.no_members')"
                                               :text="__('quran_classes.no_members_text')" />
                            </td>
                        </tr>
                    @endforelse

                    @if ($memberCount > 0)
                        <tr data-filter-empty hidden>
                            <td colspan="{{ $canManage ? 6 : 5 }}">
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
