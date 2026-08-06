@extends('layouts.app')

@section('title', __('masters.edit').' — '.$quranStatus->status_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.quran-statuses.index') }}">{{ __('masters.quran_statuses') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $quranStatus->status_name }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.edit').' — '.$quranStatus->status_name,
    'singular' => __('masters.quran_status'),
    'plural' => __('masters.quran_statuses'),
    'icon' => 'bi-patch-check',
    'routeBase' => 'masters.quran-statuses',
    'fieldsView' => 'masters.quran-statuses.partials.fields',
    'record' => $quranStatus,
    'aside' => [__('masters.quran_status_use_1'), __('masters.quran_status_use_2')],
])
@endsection
