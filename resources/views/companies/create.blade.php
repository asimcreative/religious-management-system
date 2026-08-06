@extends('layouts.app')

@section('title', __('companies.create_company'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('companies.index') }}">{{ __('companies.companies') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('companies.create_company') }}</li>
@endsection

@section('content')
<x-page-header :title="__('companies.create_company')" :subtitle="__('companies.create_subtitle')" icon="bi-building-add">
    <x-slot:actions>
        <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('companies.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-form.error-summary />

<form method="POST" action="{{ route('companies.store') }}" novalidate data-guard>
    @csrf

    <x-card>
        @include('companies.partials.form', ['company' => null])
    </x-card>

    <x-form.actions :submit="__('companies.save')" :cancel-url="route('companies.index')" />
</form>
@endsection
