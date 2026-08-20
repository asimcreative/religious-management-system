@extends('layouts.app')

@section('title', __('masters.edit').' — '.$reason->reason_name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('masters.salah-attendance-reasons.index') }}">{{ __('masters.salah_attendance_reasons') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $reason->reason_name }}</li>
@endsection

@section('content')
@include('masters.partials.form-page', [
    'title' => __('masters.edit').' — '.$reason->reason_name,
    'singular' => __('masters.salah_attendance_reason'),
    'plural' => __('masters.salah_attendance_reasons'),
    'icon' => 'bi-chat-left-text',
    'routeBase' => 'masters.salah-attendance-reasons',
    'fieldsView' => 'masters.attendance-reasons.partials.fields',
    'record' => $reason,
    'aside' => [__('masters.salah_reason_use_1'), __('masters.reason_use_2'), __('masters.reason_use_3')],
])
@endsection
