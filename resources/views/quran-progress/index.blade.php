@extends('layouts.app')

@section('title', __('quran_progress.quran_progress'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('quran_progress.quran_progress') }}</h4>
    @can('create', App\Models\QuranProgress::class)
        <a href="{{ route('quran-progress.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> {{ __('quran_progress.update_progress') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="{{ __('quran_progress.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="quran_department_id" class="form-select form-select-sm">
                    <option value="">{{ __('quran_progress.all_departments') }}</option>
                    @foreach($quranDepartments as $id => $name)
                        <option value="{{ $id }}" {{ request('quran_department_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="quran_status_id" class="form-select form-select-sm">
                    <option value="">{{ __('quran_progress.all_statuses') }}</option>
                    @foreach($quranStatuses as $id => $name)
                        <option value="{{ $id }}" {{ request('quran_status_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="teacher_id" class="form-select form-select-sm">
                    <option value="">{{ __('quran_progress.all_teachers') }}</option>
                    @foreach($teachers as $id => $name)
                        <option value="{{ $id }}" {{ request('teacher_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-search"></i> {{ __('quran_progress.filter') }}
                </button>
                <a href="{{ route('quran-progress.index') }}" class="btn btn-outline-light btn-sm text-dark">
                    <i class="bi bi-x-lg"></i> {{ __('quran_progress.reset') }}
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('quran_progress.employee') }}</th>
                        <th>{{ __('quran_progress.department') }}</th>
                        <th>{{ __('quran_progress.quran_status') }}</th>
                        <th>{{ __('quran_progress.lesson') }}</th>
                        <th>{{ __('quran_progress.completion') }}</th>
                        <th>{{ __('quran_progress.teacher') }}</th>
                        <th>{{ __('quran_progress.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($progress as $record)
                        <tr>
                            <td>{{ $progress->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('quran-progress.show', $record) }}">
                                    {{ $record->employee?->employee_name ?? '-' }}
                                </a>
                                <small class="text-muted d-block">{{ $record->employee?->employee_code ?? '' }}</small>
                            </td>
                            <td>{{ $record->quranDepartment?->department_name ?? '-' }}</td>
                            <td>
                                @if($record->quranStatus)
                                    <span class="badge" style="background-color: {{ $record->quranStatus->color ?? '#6c757d' }}">
                                        {{ $record->quranStatus->status_name }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $record->current_lesson ?? '-' }}</td>
                            <td>
                                <div class="progress" style="height: 18px; min-width: 80px">
                                    <div class="progress-bar {{ (float)$record->completion_percentage >= 100 ? 'bg-success' : 'bg-primary' }}"
                                         style="width: {{ $record->completion_percentage }}%">
                                        {{ number_format($record->completion_percentage, 0) }}%
                                    </div>
                                </div>
                            </td>
                            <td>{{ $record->teacher?->employee?->employee_name ?? '-' }}</td>
                            <td>
                                @can('view', $record)
                                    <a href="{{ route('quran-progress.show', $record) }}" class="btn btn-outline-info btn-sm" title="{{ __('quran_progress.view') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcan
                                @can('update', $record)
                                    <a href="{{ route('quran-progress.edit', $record) }}" class="btn btn-outline-primary btn-sm" title="{{ __('quran_progress.edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">{{ __('quran_progress.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $progress->withQueryString()->links() }}
    </div>
</div>
@endsection
