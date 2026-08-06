@extends('layouts.app')

@section('title', __('data_transfer.preview_title'))

@section('breadcrumbs')
    @if ($definition->indexRoute())
        <li class="breadcrumb-item"><a href="{{ route($definition->indexRoute()) }}">{{ $definition->label() }}</a></li>
    @endif
    <li class="breadcrumb-item active" aria-current="page">{{ __('data_transfer.import') }}</li>
@endsection

@section('content')
{{--
    The point of no return, and the last screen before anything is written.

    The numbers come first because they answer the only question that matters
    here — "what will this do to my data?" — and the errors are listed with
    their spreadsheet row numbers so the file can be corrected directly.
--}}
<x-page-header :title="__('data_transfer.preview_title')"
               :subtitle="$definition->label().' · '.$log->file_name"
               :icon="$definition->icon()">
    <x-slot:actions>
        @if ($definition->indexRoute())
            <a href="{{ route($definition->indexRoute()) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span>{{ __('data_transfer.back') }}</span>
            </a>
        @endif
    </x-slot:actions>
</x-page-header>

{{-- ── Blocking problems ───────────────────────────────────────────── --}}
@if ($analysis->fatal)
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-octagon" aria-hidden="true"></i>
        <span>{{ $analysis->fatal }}</span>
    </div>
@endif

@if ($analysis->missingColumns)
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-octagon" aria-hidden="true"></i>
        <span>{{ __('data_transfer.column_missing', ['columns' => implode(', ', $analysis->missingColumns)]) }}</span>
    </div>
@endif

@if ($analysis->unknownColumns)
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <span>{{ __('data_transfer.column_unknown', ['columns' => implode(', ', $analysis->unknownColumns)]) }}</span>
    </div>
@endif

{{-- ── Summary ─────────────────────────────────────────────────────── --}}
<div class="auto-grid auto-grid--sm mb-3">
    <x-stat-card :label="__('data_transfer.summary_total')"
                 :value="number_format($analysis->totalRows)"
                 icon="bi-list-ol" tone="primary" />

    <x-stat-card :label="__('data_transfer.summary_valid')"
                 :value="number_format($analysis->validRows)"
                 icon="bi-check-circle" tone="success" />

    <x-stat-card :label="__('data_transfer.summary_duplicates')"
                 :value="number_format($analysis->duplicateRows)"
                 icon="bi-files" tone="warning" />

    <x-stat-card :label="__('data_transfer.summary_invalid')"
                 :value="number_format($analysis->invalidRows)"
                 icon="bi-exclamation-triangle"
                 :tone="$analysis->invalidRows > 0 ? 'danger' : 'neutral'" />
</div>

@if ($willQueue)
    <div class="alert alert-info" role="alert">
        <i class="bi bi-hourglass-split" aria-hidden="true"></i>
        <span>{{ __('data_transfer.result_queued', ['count' => number_format($analysis->totalRows)]) }}</span>
    </div>
@endif

{{-- ── Errors ──────────────────────────────────────────────────────── --}}
@if ($analysis->errors)
    <x-card :title="__('data_transfer.errors_heading')" icon="bi-exclamation-triangle" flush>
        @if ($analysis->errorCount > count($analysis->errors))
            <p class="px-3 pt-3 mb-0 fs-sm text-soft">
                {{ __('data_transfer.errors_showing', [
                    'shown' => count($analysis->errors),
                    'total' => number_format($analysis->errorCount),
                ]) }}
            </p>
        @endif

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
                @foreach ($analysis->errors as $error)
                    <tr>
                        <td class="col-num mono" data-label="{{ __('data_transfer.error_row') }}">{{ $error->row }}</td>
                        <td data-label="{{ __('data_transfer.col_column') }}">
                            {{ $error->column ?? '—' }}
                        </td>
                        <td data-label="{{ __('data_transfer.error_detail') }}">{{ $error->message }}</td>
                        <td class="text-soft" data-label="{{ __('data_transfer.error_fix') }}">{{ $error->fix }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
@endif

{{-- ── Preview ─────────────────────────────────────────────────────── --}}
@if ($analysis->preview)
    <x-card :title="__('data_transfer.preview_title')" icon="bi-eye" flush class="mt-3">
        <p class="px-3 pt-3 mb-0 fs-sm text-soft">
            {{ __('data_transfer.preview_showing', [
                'shown' => count($analysis->preview),
                'total' => number_format($analysis->writableRows()),
            ]) }}
        </p>

        <x-table sticky :stack="false" :label="__('data_transfer.preview_title')">
            <thead>
                <tr>
                    <th scope="col" class="col-num">#</th>
                    @foreach (array_keys($analysis->preview[0]) as $heading)
                        <th scope="col">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($analysis->preview as $index => $row)
                    <tr>
                        <td class="col-num">{{ $index + 1 }}</td>
                        @foreach ($row as $value)
                            <td>{{ $value !== null && $value !== '' ? $value : '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>
@elseif (! $analysis->hasFatal())
    <x-card flush class="mt-3">
        <x-empty-state icon="bi-file-earmark-x"
                       :title="__('data_transfer.no_valid_rows')"
                       :text="__('data_transfer.errors_heading')" />
    </x-card>
@endif

{{-- ── Confirm ─────────────────────────────────────────────────────── --}}
<div class="preview-actions">
    @if ($definition->indexRoute())
        <a href="{{ route($definition->indexRoute()) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
            <span>{{ __('data_transfer.cancel') }}</span>
        </a>
    @endif

    @if ($analysis->canProceed())
        <form method="POST" action="{{ route('data.import', ['resource' => $definition->key()]) }}">
            @csrf
            <input type="hidden" name="import_log_id" value="{{ $log->id }}">

            <button type="submit" class="btn btn-primary btn-sm" data-submit-text="{{ __('data_transfer.importing') }}">
                <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i>
                <span>{{ __('data_transfer.confirm_import') }}</span>
            </button>
        </form>
    @endif
</div>
@endsection
