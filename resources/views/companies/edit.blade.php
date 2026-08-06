@extends('layouts.app')

@section('title', __('companies.edit_company'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">{{ __('companies.companies') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $company->company_name }}</li>
@endsection

@section('content')
<x-page-header :title="__('companies.edit_company')" :subtitle="$company->company_name" icon="bi-building-gear">
    <x-slot:actions>
        <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('companies.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-form.error-summary />

<form method="POST" action="{{ route('companies.update', $company) }}" novalidate data-guard>
    @csrf
    @method('PUT')

    <x-card>
        @include('companies.partials.form')
    </x-card>

    <x-form.actions :submit="__('companies.save')" :cancel-url="route('companies.index')" />
</form>
@endsection
