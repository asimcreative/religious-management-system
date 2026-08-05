<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', __('auth.login')) — {{ config('app.name', 'RAMS') }}</title>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light d-flex align-items-center min-vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4">

                {{-- Logo / App Name --}}
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-primary">{{ config('app.name', 'RAMS') }}</h3>
                    <p class="text-muted small">{{ __('auth.app_tagline') }}</p>
                </div>

                {{-- Status Messages --}}
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Page Content --}}
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        @yield('content')
                    </div>
                </div>

                {{-- Footer --}}
                <div class="text-center mt-3">
                    <small class="text-muted">&copy; {{ date('Y') }} {{ config('app.name', 'RAMS') }}</small>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
