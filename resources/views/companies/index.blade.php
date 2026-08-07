@extends('layouts.app')

@section('title', __('companies.companies'))

@section('breadcrumbs')
    <li class="breadcrumb-item active" aria-current="page">{{ __('companies.companies') }}</li>
@endsection

@section('content')
@php($hasFilters = filled(request('search')) || filled(request('status')))

<x-page-header :title="__('companies.companies')"
               :subtitle="__('companies.subtitle')"
               icon="bi-buildings"
               :badge="number_format($companies->total())">
    <x-slot:actions>
        <x-data-toolbar resource="companies"
                        :create-route="route('companies.create')"
                        :create-model="App\Models\Company::class"
                        :create-label="__('companies.add_new')"
                        :filters="request()->query()" />
    </x-slot:actions>
</x-page-header>

<x-card flush>
    <x-filters :reset-url="$hasFilters ? route('companies.index') : null"
               :active="$hasFilters ? [__('ui.search').': '.request('search') => route('companies.index')] : []">
        <div class="field field--grow">
            <label for="search" class="form-label">{{ __('ui.search') }}</label>
            <div class="input-icon">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" id="search" class="form-control form-control-sm"
                       placeholder="{{ __('companies.search_placeholder') }}" value="{{ request('search') }}">
            </div>
        </div>

        <div class="field field--sm">
            <label for="filter_status" class="form-label">{{ __('companies.status') }}</label>
            <select name="status" id="filter_status" class="form-select form-select-sm">
                <option value="">{{ __('companies.all_statuses') }}</option>
                @foreach (App\Enums\Status::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === (string) $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
    </x-filters>

    <x-table sticky :label="__('companies.companies')">
        <thead>
            <tr>
                <th scope="col" class="col-num">#</th>
                <th scope="col">{{ __('companies.company_name') }}</th>
                <th scope="col">{{ __('companies.email') }}</th>
                <th scope="col">{{ __('companies.timezone') }}</th>
                <th scope="col">{{ __('companies.user_count') }}</th>
                <th scope="col">{{ __('companies.status') }}</th>
                <th scope="col" class="col-actions"><span class="visually-hidden">{{ __('companies.actions') }}</span></th>
            </tr>
        </thead>

        <tbody>
            @forelse ($companies as $company)
                <tr>
                    <td class="col-num" data-label="#">{{ $companies->firstItem() + $loop->index }}</td>

                    <td data-label="{{ __('companies.company_name') }}">
                        <span class="cell-primary__text">
                            <span class="cell-primary__title">{{ $company->company_name }}</span>
                            <span class="cell-primary__sub code-cell">{{ $company->company_code }}</span>
                        </span>
                    </td>

                    <td data-label="{{ __('companies.email') }}">{{ $company->email ?: '—' }}</td>
                    <td data-label="{{ __('companies.timezone') }}" class="mono fs-xs">{{ $company->timezone }}</td>
                    <td data-label="{{ __('companies.user_count') }}" class="mono">{{ number_format($company->users_count) }}</td>

                    <td data-label="{{ __('companies.status') }}">
                        <x-status-badge :status="$company->status" />
                    </td>

                    <td class="col-actions" data-label="{{ __('companies.actions') }}">
                        <div class="table-actions">
                            <x-impersonate-button :company="$company" />

                            @can('update', $company)
                                <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-ghost btn-icon"
                                   data-bs-toggle="tooltip" title="{{ __('companies.edit') }}"
                                   aria-label="{{ __('companies.edit') }} — {{ $company->company_name }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                            @endcan

                            @can('delete', $company)
                                <x-delete-button :action="route('companies.destroy', $company)"
                                                 :record="$company->company_name"
                                                 :title="__('companies.delete')" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <x-empty-state icon="bi-buildings"
                                       :title="__('companies.empty_title')"
                                       :text="__('companies.empty_text')" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

    <x-table-footer :paginator="$companies" />
</x-card>
@endsection
