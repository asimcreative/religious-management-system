@extends('layouts.app')

@section('title', __('auth.dashboard'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h4>{{ __('auth.dashboard') }}</h4>
            <p class="text-muted">{{ __('auth.welcome_message', ['name' => Auth::user()?->name ?? '']) }}</p>
        </div>
    </div>
</div>
@endsection
