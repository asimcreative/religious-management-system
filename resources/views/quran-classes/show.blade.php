@extends('layouts.app')

@section('title', $quranClass->class_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ $quranClass->class_name }} <small class="text-muted">({{ $quranClass->class_code }})</small></h4>
    <div class="d-flex gap-2">
        @can('update', $quranClass)
            <a href="{{ route('quran-classes.members.index', $quranClass) }}" class="btn btn-success btn-sm">
                <i class="bi bi-people"></i> {{ __('quran_classes.manage_members') }}
            </a>
            <a href="{{ route('quran-classes.edit', $quranClass) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil"></i> {{ __('quran_classes.edit') }}
            </a>
        @endcan
        <a href="{{ route('quran-classes.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> {{ __('quran_classes.back_to_list') }}
        </a>
    </div>
</div>

<div class="row g-3">
    {{-- Class Information --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">{{ __('quran_classes.class_info') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">{{ __('quran_classes.class_code') }}</td>
                        <td>{{ $quranClass->class_code }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_classes.class_name') }}</td>
                        <td>{{ $quranClass->class_name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_classes.status') }}</td>
                        <td>
                            <span class="badge {{ $quranClass->status->badgeClass() }}">
                                {{ $quranClass->status->label() }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_classes.strength') }}</td>
                        <td>
                            <span class="{{ $quranClass->active_members_count >= $quranClass->max_strength ? 'text-danger fw-bold' : '' }}">
                                {{ $quranClass->active_members_count }}/{{ $quranClass->max_strength }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Teacher & Branch --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">{{ __('quran_classes.assignment') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">{{ __('quran_classes.teacher') }}</td>
                        <td>
                            @if($quranClass->teacher)
                                <a href="{{ route('teachers.show', $quranClass->teacher) }}">
                                    {{ $quranClass->teacher->employee?->employee_name ?? $quranClass->teacher->teacher_code }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_classes.branch') }}</td>
                        <td>{{ $quranClass->branch?->branch_name ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Schedule --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">{{ __('quran_classes.schedule') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">{{ __('quran_classes.start_time') }}</td>
                        <td>{{ $quranClass->start_time ? \Carbon\Carbon::parse($quranClass->start_time)->format('h:i A') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_classes.end_time') }}</td>
                        <td>{{ $quranClass->end_time ? \Carbon\Carbon::parse($quranClass->end_time)->format('h:i A') : '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Active Members --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ __('quran_classes.active_members') }} ({{ $quranClass->active_members_count }})</h6>
            </div>
            <div class="card-body">
                @if($quranClass->activeMembers->isNotEmpty())
                    <ul class="list-unstyled mb-0">
                        @foreach($quranClass->activeMembers as $member)
                            <li class="mb-1">
                                <i class="bi bi-person me-1 text-muted"></i>
                                <a href="{{ route('employees.show', $member) }}">{{ $member->employee_name }}</a>
                                <small class="text-muted">({{ $member->employee_code }})</small>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">{{ __('quran_classes.no_members') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Audit Information --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">{{ __('quran_classes.audit_info') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">{{ __('quran_classes.created_by') }}</td>
                        <td>{{ $quranClass->creator?->name ?? '-' }} <small class="text-muted">{{ $quranClass->created_at?->format('d M Y H:i') }}</small></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_classes.updated_by') }}</td>
                        <td>{{ $quranClass->updater?->name ?? '-' }} <small class="text-muted">{{ $quranClass->updated_at?->format('d M Y H:i') }}</small></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
