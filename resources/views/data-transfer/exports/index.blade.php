@extends('layouts.app')

@section('title', __('data_transfer.exports_title'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('data_transfer.exports_title') }}</li>
@endsection

@section('content')
{{--
    Every export taken out of this company, by whom, and with which filters.

    The filters column is the point: "who took what data out" is only a useful
    answer if it says which records were in the file.
--}}
<x-page-header :title="__('data_transfer.exports_title')"
               :subtitle="__('data_transfer.exports_subtitle')"
               icon="bi-box-arrow-up"
               :badge="number_format($logs->total())">
    <x-slot:actions>
        <a href="{{ route('data.imports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i>
            <span>{{ __('data_transfer.import_history') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-card flush>
    <x-filters :reset-url="route('data.exports.index')"
               :active="request()->filled('resource') && isset($resources[request('resource')])
                   ? [__('data_transfer.module').': '.$resources[request('resource')]->label() => route('data.exports.index')]
                   : []">
        <div class="field field--xl">
            <label for="resource" class="form-label">{{ __('data_transfer.module') }}</label>
            <select name="resource" id="resource" class="form-select form-select-sm">
                <option value="">{{ __('ui.all') }}</option>
                @foreach ($resources as $key => $definition)
                    <option value="{{ $key }}" @selected(request('resource') === $key)>{{ $definition->label() }}</option>
                @endforeach
            </select>
        </div>
    </x-filters>

    <x-table sticky :label="__('data_transfer.exports_title')">
        <thead>
            <tr>
                <th scope="col">{{ __('data_transfer.module') }}</th>
                <th scope="col">{{ __('data_transfer.user') }}</th>
                <th scope="col">{{ __('data_transfer.started') }}</th>
                <th scope="col">{{ __('data_transfer.format') }}</th>
                <th scope="col">{{ __('data_transfer.scope') }}</th>
                <th scope="col">{{ __('data_transfer.filters') }}</th>
                <th scope="col">{{ __('data_transfer.records') }}</th>
                <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td data-label="{{ __('data_transfer.module') }}">
                        <span class="fw-semibold text-strong">{{ $log->module_label }}</span>
                    </td>

                    <td data-label="{{ __('data_transfer.user') }}">{{ $log->user?->name ?? '—' }}</td>

                    <td data-label="{{ __('data_transfer.started') }}" class="mono">
                        {{ App\Helpers\TimezoneHelper::formatForDisplay($log->created_at, 'Y-m-d H:i') }}
                    </td>

                    <td data-label="{{ __('data_transfer.format') }}">
                        {{ App\Support\DataTransfer\ExportFormat::from($log->format)->label() }}
                    </td>

                    <td data-label="{{ __('data_transfer.scope') }}">
                        {{ App\Support\DataTransfer\ExportScope::from($log->scope)->label() }}
                    </td>

                    <td data-label="{{ __('data_transfer.filters') }}" class="fs-xs">
                        @forelse ($log->appliedFilters() as $key => $value)
                            <span class="chip">
                                {{ Str::headline($key) }}: {{ is_array($value) ? implode(', ', $value) : $value }}
                            </span>
                        @empty
                            <span class="dash">{{ __('data_transfer.no_filters') }}</span>
                        @endforelse
                    </td>

                    <td data-label="{{ __('data_transfer.records') }}" class="mono">
                        {{ number_format($log->record_count) }}
                        @if ($log->was_truncated)
                            <i class="bi bi-exclamation-triangle text-warning" aria-hidden="true"
                               data-bs-toggle="tooltip" title="{{ __('data_transfer.pdf_truncated', ['count' => number_format($log->record_count)]) }}"></i>
                        @endif
                    </td>

                    <td class="col-actions" data-label="{{ __('ui.actions') }}">
                        <div class="table-actions">
                            @if ($log->isDownloadable())
                                <a href="{{ route('data.exports.download', $log) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('data_transfer.download') }}"
                                   aria-label="{{ __('data_transfer.download') }} — {{ $log->file_name }}">
                                    <i class="bi bi-download" aria-hidden="true"></i>
                                </a>
                            @else
                                <span class="text-subtle fs-xs">{{ __('data_transfer.file_expired') }}</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <x-empty-state icon="bi-box-arrow-up"
                                       :title="__('data_transfer.exports_empty_title')"
                                       :text="__('data_transfer.exports_empty_text')" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$logs" />
</x-card>
@endsection
