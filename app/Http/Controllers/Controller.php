<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Resolve a bounded page size for list endpoints.
     */
    protected function perPage(Request $request, int $default = 25, int $maximum = 100): int
    {
        $value = filter_var($request->query('per_page'), FILTER_VALIDATE_INT);

        if ($value === false || $value === null) {
            return $default;
        }

        return min(max($value, 1), $maximum);
    }
}
