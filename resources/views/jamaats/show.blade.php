@extends('layouts.app')

@section('title', __('jamaats.view_jamaat'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('jamaats.view_jamaat') }}</h4>
    <div class="d-flex gap-2">
        @can('update', $jamaat)
            <a href="{{ route('jamaats.edit', $jamaat) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil"></i> {{ __('jamaats.edit') }}
            </a>
        @endcan
        @can('create', App\Models\Jamaat::class)
            <a href="{{ route('jamaats.members.index', $jamaat) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-people"></i> {{ __('jamaats.manage_members') }}
            </a>
        @endcan
        <a href="{{ route('jamaats.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> {{ __('jamaats.back_to_list') }}
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">{{ __('jamaats.jamaat_info') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th class="w-40">{{ __('jamaats.jamaat_number') }}</th>
                        <td>{{ $jamaat->jamaat_number }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('jamaats.jamaat_name') }}</th>
                        <td>{{ $jamaat->jamaat_name }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('jamaats.branch') }}</th>
                        <td>{{ $jamaat->branch?->branch_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('jamaats.status') }}</th>
                        <td>
                            <span class="badge {{ $jamaat->status->badgeClass() }}">
                                {{ $jamaat->status->label() }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">{{ __('jamaats.leadership') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th class="w-40">{{ __('jamaats.leader') }}</th>
                        <td>{{ $jamaat->leader?->employee_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('jamaats.vice_leader') }}</th>
                        <td>{{ $jamaat->viceLeader?->employee_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('jamaats.members_count') }}</th>
                        <td>{{ $jamaat->active_members_count }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">{{ __('jamaats.active_members') }}</h6>
        <span class="badge bg-primary">{{ $jamaat->active_members_count }}</span>
    </div>
    <div class="card-body">
        @if($jamaat->activeMembers->count() > 0)
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('jamaats.employee_code') }}</th>
                            <th>{{ __('jamaats.employee_name') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jamaat->activeMembers as $member)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $member->employee_code }}</td>
                                <td>{{ $member->employee_name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">{{ __('jamaats.no_active_members') }}</p>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">{{ __('jamaats.audit_info') }}</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">{{ __('jamaats.created_by') }}:</small>
                <span>{{ $jamaat->creator?->name ?? '-' }}</span>
                <br>
                <small class="text-muted">{{ __('jamaats.created_at') }}:</small>
                <span>{{ $jamaat->created_at?->format('d M Y H:i') }}</span>
            </div>
            <div class="col-md-6">
                <small class="text-muted">{{ __('jamaats.updated_by') }}:</small>
                <span>{{ $jamaat->updater?->name ?? '-' }}</span>
                <br>
                <small class="text-muted">{{ __('jamaats.updated_at') }}:</small>
                <span>{{ $jamaat->updated_at?->format('d M Y H:i') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
