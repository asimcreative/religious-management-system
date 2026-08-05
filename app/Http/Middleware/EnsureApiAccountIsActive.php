<?php

namespace App\Http\Middleware;

use App\Enums\Status;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->status !== Status::Active) {
            return $this->inactiveResponse('Your account is inactive. Contact your administrator.');
        }

        $company = $user->company;

        if ($company === null || $company->status !== Status::Active) {
            return $this->inactiveResponse('Your company account is inactive.');
        }

        return $next($request);
    }

    private function inactiveResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], Response::HTTP_FORBIDDEN);
    }
}
