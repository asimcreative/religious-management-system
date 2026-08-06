@extends('layouts.app')

@section('title', __('data_transfer.detail_title', ['id' => $log->id]))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('data.imports.index') }}">{{ __('data_transfer.imports_title') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $log->file_name }}</li>
@endsection

@section('content')
<x-page-header :title="$log->file_name"
               :subtitle="$log->module_label"
               icon="bi-file-earmark-spreadsheet"
               :badge="$log->status->label()">
    <x-slot:actions>
        @if ($log->hasErrorFile())
            <a href="{{ route('data.imports.errors', $log) }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>
                <span>{{ __('data_transfer.download_errors') }}</span>
            </a>
        @endif

        @if ($definition?->indexRoute())
            <a href="{{ route($definition->indexRoute()) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                <span>{{ $definition->label() }}</span>
            </a>
        @endif
    </x-slot:actions>
</x-page-header>

<x-card :title="__('data_transfer.result_title')" icon="bi-clipboard-data">
    <dl class="result-figures">
        <div>
            <dt>{{ __('data_transfer.summary_total') }}</dt>
            <dd>{{ number_format($log->total_rows) }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.created') }}</dt>
            <dd class="text-success">{{ number_format($log->imported_rows) }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.updated') }}</dt>
            <dd>{{ number_format($log->updated_rows) }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.skipped') }}</dt>
            <dd class="text-soft">{{ number_format($log->skipped_rows) }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.failed') }}</dt>
            <dd class="text-danger">{{ number_format($log->failed_rows) }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.user') }}</dt>
            <dd>{{ $log->user?->name ?? '—' }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.started') }}</dt>
            <dd class="mono">{{ App\Helpers\TimezoneHelper::formatForDisplay($log->created_at, 'Y-m-d H:i') }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.duration') }}</dt>
            <dd>{{ $log->duration_ms ? number_format($log->duration_ms / 1000, 1).'s' : '—' }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.mode') }}</dt>
            <dd>{{ App\Support\DataTransfer\ImportMode::from($log->mode)->label() }}</dd>
        </div>
        <div>
            <dt>{{ __('data_transfer.duplicate_handling') }}</dt>
            <dd>{{ App\Support\DataTransfer\DuplicateStrategy::from($log->duplicate_strategy)->label() }}</dd>
        </div>
    </dl>

    @if ($log->exception)
        <div class="alert alert-danger mt-3 mb-0" role="alert">
            <i class="bi bi-exclamation-octagon" aria-hidden="true"></i>
            <span>{{ $log->exception }}</span>
        </div>
    @endif
</x-card>

@php
    // The stored summary is either the dry-run report or the committed error
    // list, depending on how far the run got.
    $errors = collect($log->error_summary ?? [])->filter(fn ($row) => is_array($row) && isset($row['message']))->all();
@endphp

@if ($errors)
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
                @foreach ($errors as $error)
                    <tr>
                        <td class="col-num mono" data-label="{{ __('data_transfer.error_row') }}">{{ $error['row'] ?? '—' }}</td>
                        <td data-label="{{ __('data_transfer.col_column') }}">{{ $error['column'] ?? '—' }}</td>
                        <td data-label="{{ __('data_transfer.error_detail') }}">{{ $error['message'] }}</td>
                        <td class="text-soft" data-label="{{ __('data_transfer.error_fix') }}">{{ $error['fix'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
@endif
@endsection
