@extends('layouts.app')

@section('title', __('masters.add_new').' — '.__('masters.attendance_reason'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.attendance-reasons.index', ['type' => $type->value]) }}">{{ __('masters.attendance_reasons') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.add_new') }}</li>
@endsection

@section('content')
@include('masters.attendance-reasons.partials.tabs', ['type' => $type])

@include('masters.partials.form-page', [
    'title' => __('masters.add_new').' — '.__('masters.attendance_reason_type_'.$type->value),
    'singular' => __('masters.attendance_reason'),
    'plural' => __('masters.attendance_reason_type_'.$type->value),
    'intro' => __('masters.attendance_reasons_intro_'.$type->value),
    'icon' => 'bi-chat-left-text',
    'routeBase' => 'masters.attendance-reasons',
    'fieldsView' => 'masters.attendance-reasons.partials.fields',
    'record' => null,
    'aside' => [__('masters.'.$type->value.'_reason_use_1'), __('masters.reason_use_2'), __('masters.reason_use_3')],
])
@endsection
