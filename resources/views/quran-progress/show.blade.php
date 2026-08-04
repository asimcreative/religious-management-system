@extends('layouts.app')

@section('title', __('quran_progress.progress_detail'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        {{ $quranProgress->employee?->employee_name ?? '-' }}
        <small class="text-muted">- {{ __('quran_progress.quran_progress') }}</small>
    </h4>
    <div class="d-flex gap-2">
        @can('update', $quranProgress)
            <a href="{{ route('quran-progress.edit', $quranProgress) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil"></i> {{ __('quran_progress.edit') }}
            </a>
        @endcan
        <a href="{{ route('quran-progress.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> {{ __('quran_progress.back_to_list') }}
        </a>
    </div>
</div>

<div class="row g-3">
    {{-- Current Progress --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">{{ __('quran_progress.current_position') }}</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%">{{ __('quran_progress.employee') }}</td>
                        <td>
                            <a href="{{ route('employees.show', $quranProgress->employee) }}">
                                {{ $quranProgress->employee?->employee_name ?? '-' }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_progress.department') }}</td>
                        <td>{{ $quranProgress->quranDepartment?->department_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_progress.quran_status') }}</td>
                        <td>
                            @if($quranProgress->quranStatus)
                                <span class="badge" style="background-color: {{ $quranProgress->quranStatus->color ?? '#6c757d' }}">
                                    {{ $quranProgress->quranStatus->status_name }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_progress.lesson') }}</td>
                        <td>{{ $quranProgress->current_lesson ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_progress.surah') }}</td>
                        <td>{{ $quranProgress->current_surah ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_progress.sipara') }}</td>
                        <td>{{ $quranProgress->current_sipara ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_progress.page') }}</td>
                        <td>{{ $quranProgress->current_page ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_progress.completion') }}</td>
                        <td>
                            <div class="progress" style="height: 20px; min-width: 120px">
                                <div class="progress-bar {{ (float)$quranProgress->completion_percentage >= 100 ? 'bg-success' : 'bg-primary' }}"
                                     style="width: {{ $quranProgress->completion_percentage }}%">
                                    {{ number_format($quranProgress->completion_percentage, 1) }}%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('quran_progress.teacher') }}</td>
                        <td>{{ $quranProgress->teacher?->employee?->employee_name ?? '-' }}</td>
                    </tr>
                    @if($quranProgress->remarks)
                    <tr>
                        <td class="text-muted">{{ __('quran_progress.remarks') }}</td>
                        <td>{{ $quranProgress->remarks }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">{{ __('quran_progress.last_updated') }}</td>
                        <td>{{ $quranProgress->updater?->name ?? '-' }} <small class="text-muted">{{ $quranProgress->updated_at?->format('d M Y H:i') }}</small></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Progress History --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">{{ __('quran_progress.history') }} ({{ $quranProgress->history->count() }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('quran_progress.date') }}</th>
                                <th>{{ __('quran_progress.department') }}</th>
                                <th>{{ __('quran_progress.lesson') }}</th>
                                <th>{{ __('quran_progress.completion') }}</th>
                                <th>{{ __('quran_progress.updated_by') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quranProgress->history as $entry)
                                <tr>
                                    <td>{{ $entry->created_at?->format('d M Y') }}</td>
                                    <td>{{ $entry->quranDepartment?->department_name ?? '-' }}</td>
                                    <td>{{ $entry->lesson ?? '-' }}</td>
                                    <td>{{ number_format($entry->percentage, 0) }}%</td>
                                    <td>{{ $entry->creator?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">{{ __('quran_progress.no_history') }}</td>
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
