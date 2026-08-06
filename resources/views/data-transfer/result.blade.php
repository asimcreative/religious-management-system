@extends('layouts.app')

@section('title', $queued ? __('data_transfer.result_queued_title') : __('data_transfer.result_title'))

@section('breadcrumbs')
    @if ($definition->indexRoute())
        <li class="breadcrumb-item"><a href="{{ route($definition->indexRoute()) }}">{{ $definition->label() }}</a></li>
    @endif
    <li class="breadcrumb-item active" aria-current="page">{{ __('data_transfer.import') }}</li>
@endsection

@section('content')
{{--
    What the import actually did.

    A queued run renders the same screen with a live progress bar, so the user
    never has to learn two different result pages.
--}}
@php
    $tone = match ($log->status) {
        App\Enums\TransferStatus::Completed => 'success',
        App\Enums\TransferStatus::CompletedWithErrors => 'warning',
        App\Enums\TransferStatus::Failed, App\Enums\TransferStatus::Cancelled => 'danger',
        default => 'info',
    };
@endphp

<x-page-header :title="$queued ? __('data_transfer.result_queued_title') : __('data_transfer.result_title')"
               :subtitle="$definition->label().' · '.$log->file_name"
               :icon="$definition->icon()" />

<x-card>
    <div class="result-headline result-headline--{{ $tone }}">
        <span class="result-headline__icon" aria-hidden="true">
            <i class="bi {{ $log->status->icon() }}"></i>
        </span>

        <div class="min-w-0">
            <p class="result-headline__status mb-1" data-import-status-label>{{ $log->status->label() }}</p>

            <p class="result-headline__summary mb-0" data-import-summary>
                @if ($queued)
                    {{ __('data_transfer.result_queued', ['count' => number_format($log->total_rows)]) }}
                @elseif ($log->status === App\Enums\TransferStatus::Cancelled)
                    {{ __('data_transfer.import_cancelled_atomic', ['failed' => number_format($log->failed_rows)]) }}
                @elseif ($log->failed_rows > 0)
                    {{ __('data_transfer.import_partial', [
                        'imported' => number_format($log->writtenRows()),
                        'total' => number_format($log->total_rows),
                        'failed' => number_format($log->failed_rows),
                    ]) }}
                @elseif ($log->writtenRows() > 0)
                    {{ __('data_transfer.import_success', [
                        'count' => number_format($log->writtenRows()),
                        'module' => $definition->label(),
                    ]) }}
                @else
                    {{ __('data_transfer.import_none') }}
                @endif
            </p>
        </div>
    </div>

    @if ($queued || $log->status->isInProgress())
        <div class="progress mt-3" role="progressbar"
             aria-label="{{ __('data_transfer.importing') }}"
             aria-valuenow="{{ $log->progressPercentage() }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar progress-bar-striped progress-bar-animated"
                 style="width: {{ $log->progressPercentage() }}%"
                 data-import-progress></div>
        </div>
    @endif

    <dl class="result-figures">
        <div>
            <dt>{{ __('data_transfer.summary_total') }}</dt>
            <dd data-import-total>{{ number_format($log->total_rows) }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.created') }}</dt>
            <dd class="text-success" data-import-created>{{ number_format($log->imported_rows) }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.updated') }}</dt>
            <dd data-import-updated>{{ number_format($log->updated_rows) }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.skipped') }}</dt>
            <dd class="text-soft" data-import-skipped>{{ number_format($log->skipped_rows) }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.failed') }}</dt>
            <dd class="text-danger" data-import-failed>{{ number_format($log->failed_rows) }}</dd>
        </div>
        @if ($log->duration_ms)
            <div>
                <dt>{{ __('data_transfer.duration') }}</dt>
                <dd>{{ number_format($log->duration_ms / 1000, 1) }}s</dd>
            </div>
        @endif
    </dl>

    <x-slot:footer>
        <div class="d-flex flex-wrap gap-2">
            @if ($definition->indexRoute())
                <a href="{{ route($definition->indexRoute()) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    <span>{{ $definition->label() }}</span>
                </a>
            @endif

            @if ($log->hasErrorFile())
                <a href="{{ route('data.imports.errors', $log) }}" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>
                    <span>{{ __('data_transfer.download_errors') }}</span>
                </a>
            @endif

            <a href="{{ route('data.imports.show', $log) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                <span>{{ __('data_transfer.view_details') }}</span>
            </a>
        </div>
    </x-slot:footer>
</x-card>

@if ($log->error_summary && ! $queued && ! empty($log->error_summary))
    <x-card :title="__('data_transfer.errors_heading')" icon="bi-exclamation-triangle" flush class="mt-3">
        <x-table sticky :label="__('data_transfer.errors_heading')">
            <thead>
                <tr>
                    <th scope="col" class="col-num">{{ __('data_transfer.error_row') }}</th>
                    <th scope="col">{{ __('data_transfer.col_column') }}</th>
                    <th scope="col">{{ __('data_transfer.error_detail') }}</th>
                    <th scope="col">{{ __('data_transfer.error_fix') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($log->error_summary as $error)
                    <tr>
                        <td class="col-num mono" data-label="{{ __('data_transfer.error_row') }}">{{ $error['row'] ?? '—' }}</td>
                        <td data-label="{{ __('data_transfer.col_column') }}">{{ $error['column'] ?? '—' }}</td>
                        <td data-label="{{ __('data_transfer.error_detail') }}">{{ $error['message'] ?? '' }}</td>
                        <td class="text-soft" data-label="{{ __('data_transfer.error_fix') }}">{{ $error['fix'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
@endif

@if ($queued || $log->status->isInProgress())
    @php
        // Built here rather than inline: Blade's @json directive stops at the
        // first closing parenthesis, so a route() call nested in an array
        // literal truncates the compiled argument.
        $pollConfig = ['url' => route('data.imports.status', $log), 'interval' => 2500];
    @endphp

    @push('scripts')
        <script>window.ramsImportPoll = @json($pollConfig);</script>
    @endpush
@endif
@endsection
