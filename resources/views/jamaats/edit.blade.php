@extends('layouts.app')

@section('title', __('jamaats.edit_jamaat'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('jamaats.index') }}">{{ __('jamaats.jamaats') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('jamaats.show', $jamaat) }}">{{ $jamaat->jamaat_name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('jamaats.edit') }}</li>
@endsection

@section('content')
<x-page-header :title="__('jamaats.edit_jamaat')"
               :subtitle="$jamaat->jamaat_name.' · '.$jamaat->jamaat_number"
               icon="bi-pencil-square">
    <x-slot:actions>
        <a href="{{ route('jamaats.show', $jamaat) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('jamaats.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('jamaats.update', $jamaat) }}" novalidate data-guard>
    @csrf
    @method('PUT')

    @include('jamaats.partials.form', ['jamaat' => $jamaat])

    <x-form.actions :submit="__('jamaats.save')" :cancel-url="route('jamaats.show', $jamaat)" />
</form>
@endsection
