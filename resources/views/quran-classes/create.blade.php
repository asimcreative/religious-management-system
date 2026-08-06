@extends('layouts.app')

@section('title', __('quran_classes.create_class'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('quran-classes.index') }}">{{ __('quran_classes.quran_classes') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('quran_classes.create_class') }}</li>
@endsection

@section('content')
<x-page-header :title="__('quran_classes.create_class')"
               :subtitle="__('quran_classes.create_subtitle')"
               icon="bi-journal-plus">
    <x-slot:actions>
        <a href="{{ route('quran-classes.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('quran_classes.back_to_list') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('quran-classes.store') }}" novalidate data-guard>
    @csrf

    @include('quran-classes.partials.form', ['quranClass' => null])

    <x-form.actions :submit="__('quran_classes.save')" :cancel-url="route('quran-classes.index')" />
</form>
@endsection
