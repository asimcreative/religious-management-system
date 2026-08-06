@extends('layouts.app')

@section('title', __('masters.edit').' — '.$language->language_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.languages.index') }}">{{ __('masters.languages') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $language->language_name }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.edit').' — '.$language->language_name,
    'singular' => __('masters.language'),
    'plural' => __('masters.languages'),
    'icon' => 'bi-translate',
    'routeBase' => 'masters.languages',
    'fieldsView' => 'masters.languages.partials.fields',
    'record' => $language,
    'aside' => [__('masters.language_use_1'), __('masters.language_use_2')],
])
@endsection
