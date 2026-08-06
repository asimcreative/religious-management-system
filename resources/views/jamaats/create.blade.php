@extends('layouts.app')

@section('title', __('jamaats.create_jamaat'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('jamaats.index') }}">{{ __('jamaats.jamaats') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('jamaats.create_jamaat') }}</li>
@endsection

@section('content')
<x-page-header :title="__('jamaats.create_jamaat')"
               :subtitle="__('jamaats.create_subtitle')"
               icon="bi-people-fill">
    <x-slot:actions>
        <a href="{{ route('jamaats.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('jamaats.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('jamaats.store') }}" novalidate data-guard>
    @csrf

    @include('jamaats.partials.form', ['jamaat' => null])

    <x-form.actions :submit="__('jamaats.save')" :cancel-url="route('jamaats.index')" />
</form>
@endsection
