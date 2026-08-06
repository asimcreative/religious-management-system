@extends('layouts.app')

@section('title', __('data_transfer.imports_title'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('data_transfer.imports_title') }}</li>
@endsection

@section('content')
{{--
    Every file brought into this company, and what became of it.

    A user always sees their own uploads; seeing colleagues' uploads needs the
    activity-log privilege, which the controller applies to the query so the
    list and the per-record policy can never disagree.
--}}
<x-page-header :title="__('data_transfer.imports_title')"
               :subtitle="__('data_transfer.imports_subtitle')"
               icon="bi-box-arrow-in-down"
               :badge="number_format($logs->total())">
    <x-slot:actions>
        <a href="{{ route('data.exports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-box-arrow-up" aria-hidden="true"></i>
            <span>{{ __('data_transfer.export_history') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-card flush>
    <x-filters :reset-url="route('data.imports.index')"
               :active="request()->filled('resource') && isset($resources[request('resource')])
                   ? [__('data_transfer.module').': '.$resources[request('resource')]->label() => route('data.imports.index')]
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

    <x-table sticky :label="__('data_transfer.imports_title')">
        <thead>
            <tr>
                <th scope="col">{{ __('data_transfer.module') }}</th>
                <th scope="col">{{ __('data_transfer.file') }}</th>
                <th scope="col">{{ __('data_transfer.user') }}</th>
                <th scope="col">{{ __('data_transfer.started') }}</th>
                <th scope="col">{{ __('data_transfer.rows') }}</th>
                <th scope="col">{{ __('data_transfer.status') }}</th>
                <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('ui.actions') }}</span></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td data-label="{{ __('data_transfer.module') }}">
                        <span class="fw-semibold text-strong">{{ $log->module_label }}</span>
                    </td>

                    <td data-label="{{ __('data_transfer.file') }}">
                        <span class="cell-primary__sub">{{ $log->file_name }}</span>
                    </td>

                    <td data-label="{{ __('data_transfer.user') }}">{{ $log->user?->name ?? '—' }}</td>

                    <td data-label="{{ __('data_transfer.started') }}" class="mono">
                        {{ App\Helpers\TimezoneHelper::formatForDisplay($log->created_at, 'Y-m-d H:i') }}
                    </td>

                    <td data-label="{{ __('data_transfer.rows') }}">
                        <span class="transfer-tally">
                            <span class="text-success" title="{{ __('data_transfer.created') }}">+{{ number_format($log->imported_rows) }}</span>
                            @if ($log->updated_rows)
                                <span title="{{ __('data_transfer.updated') }}">~{{ number_format($log->updated_rows) }}</span>
                            @endif
                            @if ($log->failed_rows)
                                <span class="text-danger" title="{{ __('data_transfer.failed') }}">!{{ number_format($log->failed_rows) }}</span>
                            @endif
                            <span class="text-soft">/ {{ number_format($log->total_rows) }}</span>
                        </span>
                    </td>

                    <td data-label="{{ __('data_transfer.status') }}">
                        <span class="badge {{ $log->status->badgeClass() }}">{{ $log->status->label() }}</span>
                    </td>

                    <td class="col-actions" data-label="{{ __('ui.actions') }}">
                        <div class="table-actions">
                            <a href="{{ route('data.imports.show', $log) }}" class="btn btn-sm btn-ghost btn-icon"
                               data-bs-toggle="tooltip" title="{{ __('data_transfer.view_details') }}"
                               aria-label="{{ __('data_transfer.view_details') }} — {{ $log->file_name }}">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </a>

                            @if ($log->hasErrorFile())
                                <a href="{{ route('data.imports.errors', $log) }}" class="btn btn-sm btn-ghost btn-icon text-danger"
                                   data-bs-toggle="tooltip" title="{{ __('data_transfer.download_errors') }}"
                                   aria-label="{{ __('data_transfer.download_errors') }} — {{ $log->file_name }}">
                                    <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state icon="bi-box-arrow-in-down"
                                       :title="__('data_transfer.imports_empty_title')"
                                       :text="__('data_transfer.imports_empty_text')" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$logs" />
</x-card>
@endsection
