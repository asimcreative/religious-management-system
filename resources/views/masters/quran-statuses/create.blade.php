@extends('layouts.app')

@section('title', __('masters.add_new').' — '.__('masters.quran_status'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.quran-statuses.index') }}">{{ __('masters.quran_statuses') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.add_new') }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.add_new').' — '.__('masters.quran_status'),
    'singular' => __('masters.quran_status'),
    'plural' => __('masters.quran_statuses'),
    'intro' => __('masters.quran_statuses_intro'),
    'icon' => 'bi-patch-check',
    'routeBase' => 'masters.quran-statuses',
    'fieldsView' => 'masters.quran-statuses.partials.fields',
    'record' => null,
    'aside' => [__('masters.quran_status_use_1'), __('masters.quran_status_use_2')],
])
@endsection
