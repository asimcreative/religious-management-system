<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Architectural guard: every state-changing application route must enforce
 * authorization, either by an explicit `$this->authorize(...)` call in the
 * controller action or by a Form Request whose `authorize()` is a real check.
 *
 * This exists because `SalahAttendanceController@store` shipped with inline
 * `$request->validate()` and no authorization at all, which let a user holding
 * only `salah.attendance.view` write attendance rows. A per-endpoint test would
 * only have caught that one endpoint; this catches the entire class of bug for
 * every route added in the future.
 */
class RouteAuthorizationCoverageTest extends TestCase
{
    /**
     * Routes that legitimately need no permission check because they are public
     * or act solely on the authenticated user's own account. Entries are route
     * names, or "METHOD uri" signatures for routes registered without a name.
     *
     * @var list<string>
     */
    private const SELF_SCOPED_OR_PUBLIC = [
        'POST login',
        'logout',
        'password.email',
        'password.update',
        'password.change',
        'api.v1.auth.login',
        'api.v1.auth.logout',
        'api.v1.auth.profile.update',
        'api.v1.auth.change-password',
        // Display language is a personal preference, not company data. Guests
        // must be able to switch language on the login/reset screens, and for
        // signed-in users the controller writes only to $request->user(), so
        // there is no cross-account surface. The value is constrained to
        // SetLocale::SUPPORTED_LOCALES and it is a POST, not a GET mutation.
        'locale.update',
    ];

    public function test_every_state_changing_application_route_enforces_authorization(): void
    {
        $unprotected = [];

        foreach (Route::getRoutes() as $route) {
            if (! $this->isStateChangingApplicationRoute($route)) {
                continue;
            }

            if ($this->isExempt($route)) {
                continue;
            }

            if (! $this->enforcesAuthorization($route)) {
                $unprotected[] = sprintf(
                    '%s %s -> %s',
                    implode('|', $route->methods()),
                    $route->uri(),
                    $route->getActionName()
                );
            }
        }

        $this->assertSame([], $unprotected, sprintf(
            "State-changing routes without an authorization check:\n%s\n\n".
            'Add $this->authorize(...) to the action, or use a Form Request with a real authorize().',
            implode("\n", $unprotected)
        ));
    }

    private function isExempt(RoutingRoute $route): bool
    {
        if (in_array($route->getName(), self::SELF_SCOPED_OR_PUBLIC, true)) {
            return true;
        }

        foreach ($route->methods() as $method) {
            if (in_array($method.' '.$route->uri(), self::SELF_SCOPED_OR_PUBLIC, true)) {
                return true;
            }
        }

        return false;
    }

    private function isStateChangingApplicationRoute(RoutingRoute $route): bool
    {
        $writeMethods = array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);

        if ($writeMethods === []) {
            return false;
        }

        $action = $route->getActionName();

        // Ignore closures and third-party (vendor) routes such as Horizon.
        // NOTE: invokable controllers report no "@method" suffix, so this must
        // NOT require one — requiring it silently skipped every single-action
        // controller, which is exactly the blind spot this suite exists to close.
        return str_starts_with($action, 'App\\Http\\Controllers\\');
    }

    private function enforcesAuthorization(RoutingRoute $route): bool
    {
        $action = $route->getActionName();

        // Invokable single-action controllers report just the class name.
        [$class, $method] = str_contains($action, '@')
            ? explode('@', $action, 2)
            : [$action, '__invoke'];

        if (! class_exists($class) || ! method_exists($class, $method)) {
            return false;
        }

        $reflection = new ReflectionMethod($class, $method);

        if (str_contains($this->sourceOf($reflection), '$this->authorize(')) {
            return true;
        }

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $requestClass = $type->getName();

            if (! is_subclass_of($requestClass, FormRequest::class)) {
                continue;
            }

            if ($this->hasRealAuthorizeCheck($requestClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A Form Request counts as protecting the route only when `authorize()`
     * actually evaluates something — `return true;` is not authorization.
     *
     * @param  class-string<FormRequest>  $requestClass
     */
    private function hasRealAuthorizeCheck(string $requestClass): bool
    {
        if (! method_exists($requestClass, 'authorize')) {
            return false;
        }

        $body = $this->sourceOf(new ReflectionMethod($requestClass, 'authorize'));

        return ! preg_match('/return\s+true\s*;/', $body);
    }

    private function sourceOf(ReflectionMethod $method): string
    {
        $file = $method->getFileName();

        if ($file === false) {
            return '';
        }

        $lines = file($file) ?: [];
        $start = $method->getStartLine() - 1;
        $length = $method->getEndLine() - $start;

        return implode('', array_slice($lines, $start, $length));
    }
}
