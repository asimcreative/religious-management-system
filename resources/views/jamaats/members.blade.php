@extends('layouts.app')

@section('title', __('jamaats.manage_members') . ' - ' . $jamaat->jamaat_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('jamaats.manage_members') }} — {{ $jamaat->jamaat_name }}</h4>
    <a href="{{ route('jamaats.show', $jamaat) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('jamaats.back_to_list') }}
    </a>
</div>

<div class="row">
    {{-- Add Member --}}
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">{{ __('jamaats.add_member') }}</h6>
            </div>
            <div class="card-body">
                @if($availableEmployees->count() > 0)
                    <form method="POST" action="{{ route('jamaats.members.store', $jamaat) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">{{ __('jamaats.employee') }}</label>
                            <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                                <option value="">{{ __('jamaats.select_employee') }}</option>
                                @foreach($availableEmployees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->employee_name }} ({{ $employee->employee_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> {{ __('jamaats.add_member') }}
                        </button>
                    </form>
                @else
                    <p class="text-muted mb-0">{{ __('jamaats.no_employees_available') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Active Members --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ __('jamaats.active_members') }}</h6>
                <span class="badge bg-primary">{{ $members->count() }}</span>
            </div>
            <div class="card-body">
                @if($members->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('jamaats.employee_code') }}</th>
                                    <th>{{ __('jamaats.employee_name') }}</th>
                                    <th>{{ __('jamaats.joined_at') }}</th>
                                    <th>{{ __('jamaats.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $member)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $member->employee?->employee_code }}</td>
                                        <td>{{ $member->employee?->employee_name }}</td>
                                        <td>{{ $member->joined_at?->format('d M Y') }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('jamaats.members.destroy', [$jamaat, $member->employee_id]) }}"
                                                  onsubmit="return confirm('{{ __('jamaats.confirm_remove_member') }}')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('jamaats.remove_member') }}">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        </td>
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
    </div>
</div>
@endsection
