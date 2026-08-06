@extends('layouts.app')

@section('title', __('quran_classes.edit_class'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('quran-classes.index') }}">{{ __('quran_classes.quran_classes') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('quran-classes.show', $quranClass) }}">{{ $quranClass->class_name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('quran_classes.edit') }}</li>
@endsection

@section('content')
<x-page-header :title="__('quran_classes.edit_class')"
               :subtitle="$quranClass->class_name.' · '.$quranClass->class_code"
               icon="bi-pencil-square">
    <x-slot:actions>
        <a href="{{ route('quran-classes.show', $quranClass) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('quran_classes.back_to_detail') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('quran-classes.update', $quranClass) }}" novalidate data-guard>
    @csrf
    @method('PUT')

    @include('quran-classes.partials.form', ['quranClass' => $quranClass])

    <x-form.actions :submit="__('quran_classes.save')" :cancel-url="route('quran-classes.show', $quranClass)" />
</form>
@endsection
