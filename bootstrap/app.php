<?php

use App\Http\Middleware\EnforcePlatformBoundary;
use App\Http\Middleware\EnforceReadOnlyImpersonation;
use App\Http\Middleware\EnsureApiAccountIsActive;
use App\Http\Middleware\EnsureCompanyIsActive;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPermissionTeamContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Store the authenticated user's password hash in each web session so a
        // password reset/change invalidates sessions held by other devices.
        $middleware->authenticateSessions();

        // Apply the display locale on every web request — guest screens (login,
        // password reset) included, not just the authenticated area. Otherwise a
        // guest can switch language and have the cookie stored, but the very page
        // they are returned to never reads it, so nothing appears to change.
        $middleware->web(append: [SetLocale::class]);

        $middleware->alias([
            'api.account.active' => EnsureApiAccountIsActive::class,
            'company.active' => EnsureCompanyIsActive::class,
            'impersonation.readonly' => EnforceReadOnlyImpersonation::class,
            'permission.team' => SetPermissionTeamContext::class,
            'platform.boundary' => EnforcePlatformBoundary::class,
            'set.locale' => SetLocale::class,
            'user.active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $apiError = static function (string $message, int $status, array $errors = [], array $headers = []): JsonResponse {
            $payload = [
                'success' => false,
                'message' => $message,
            ];

            if ($errors !== []) {
                $payload['errors'] = $errors;
            }

            return response()->json($payload, $status, $headers);
        };

        $exceptions->render(function (ValidationException $exception, Request $request) use ($apiError): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError('The given data was invalid.', $exception->status, $exception->errors());
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) use ($apiError): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError('Unauthenticated.', 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) use ($apiError): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError('This action is unauthorized.', 403);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) use ($apiError): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError('Resource not found.', 404);
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) use ($apiError): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError('Resource not found.', 404);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($apiError): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError('Request failed.', $exception->getStatusCode(), [], $exception->getHeaders());
        });

        $exceptions->render(function (Throwable $exception, Request $request) use ($apiError): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return $apiError('Server error.', 500);
        });
    })->create();
