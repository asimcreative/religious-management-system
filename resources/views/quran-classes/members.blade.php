@extends('layouts.app')

@section('title', __('quran_classes.manage_members') . ' - ' . $quranClass->class_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        {{ $quranClass->class_name }}
        <small class="text-muted">- {{ __('quran_classes.manage_members') }}</small>
    </h4>
    <a href="{{ route('quran-classes.show', $quranClass) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> {{ __('quran_classes.back_to_detail') }}
    </a>
</div>

<div class="row g-3">
    {{-- Add Member --}}
    @can('update', $quranClass)
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">{{ __('quran_classes.add_member') }}</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    {{ __('quran_classes.strength') }}: <strong>{{ $activeMembers->count() }}/{{ $quranClass->max_strength }}</strong>
                    @if($quranClass->isFull())
                        <span class="badge bg-danger ms-1">{{ __('quran_classes.full') }}</span>
                    @endif
                </p>

                @if(!$quranClass->isFull())
                    <form method="POST" action="{{ route('quran-classes.members.store', $quranClass) }}">
                        @csrf
                        <div class="mb-2">
                            <select name="employee_id" class="form-select form-select-sm @error('employee_id') is-invalid @enderror" required>
                                <option value="">{{ __('quran_classes.select_employee') }}</option>
                                @foreach($availableEmployees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->employee_name }} ({{ $employee->employee_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-plus-lg"></i> {{ __('quran_classes.add_member') }}
                        </button>
                    </form>
                @else
                    <p class="text-danger mb-0">{{ __('quran_classes.class_full') }}</p>
                @endif
            </div>
        </div>
    </div>
    @endcan

    {{-- Active Members --}}
    <div class="{{ auth()->user()->can('update', $quranClass) ? 'col-md-8' : 'col-12' }}">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">{{ __('quran_classes.active_members') }} ({{ $activeMembers->count() }})</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('employees.employee_code') }}</th>
                                <th>{{ __('employees.employee_name') }}</th>
                                <th>{{ __('quran_classes.joined_at') }}</th>
                                @can('update', $quranClass)
                                    <th>{{ __('quran_classes.actions') }}</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeMembers as $member)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $member->employee_code }}</td>
                                    <td>
                                        <a href="{{ route('employees.show', $member) }}">{{ $member->employee_name }}</a>
                                    </td>
                                    <td>{{ $member->pivot->joined_at ? \Carbon\Carbon::parse($member->pivot->joined_at)->format('d M Y') : '-' }}</td>
                                    @can('update', $quranClass)
                                        <td>
                                            <form method="POST" action="{{ route('quran-classes.members.destroy', [$quranClass, $member]) }}"
                                                  onsubmit="return confirm('{{ __('quran_classes.confirm_remove_member') }}')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('quran_classes.remove_member') }}">
                                                    <i class="bi bi-person-dash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->can('update', $quranClass) ? 5 : 4 }}" class="text-center text-muted">
                                        {{ __('quran_classes.no_members') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
