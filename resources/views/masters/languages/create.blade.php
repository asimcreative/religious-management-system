@extends('layouts.app')

@section('title', __('masters.add_new').' — '.__('masters.language'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.languages.index') }}">{{ __('masters.languages') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.add_new') }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.add_new').' — '.__('masters.language'),
    'singular' => __('masters.language'),
    'plural' => __('masters.languages'),
    'intro' => __('masters.languages_intro'),
    'icon' => 'bi-translate',
    'routeBase' => 'masters.languages',
    'fieldsView' => 'masters.languages.partials.fields',
    'record' => null,
    'aside' => [__('masters.language_use_1'), __('masters.language_use_2')],
])
@endsection
