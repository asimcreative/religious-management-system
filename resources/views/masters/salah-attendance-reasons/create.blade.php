@extends('layouts.app')

@section('title', __('masters.add_new').' — '.__('masters.salah_attendance_reason'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.salah-attendance-reasons.index') }}">{{ __('masters.salah_attendance_reasons') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('masters.add_new') }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.add_new').' — '.__('masters.salah_attendance_reason'),
    'singular' => __('masters.salah_attendance_reason'),
    'plural' => __('masters.salah_attendance_reasons'),
    'intro' => __('masters.salah_attendance_reasons_intro'),
    'icon' => 'bi-chat-left-text',
    'routeBase' => 'masters.salah-attendance-reasons',
    'fieldsView' => 'masters.attendance-reasons.partials.fields',
    'record' => null,
    'aside' => [__('masters.salah_reason_use_1'), __('masters.reason_use_2'), __('masters.reason_use_3')],
])
@endsection
