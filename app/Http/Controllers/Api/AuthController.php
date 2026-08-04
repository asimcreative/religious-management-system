<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    /**
     * POST /api/v1/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user === null || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->isActive()) {
            return $this->errorResponse('Your account is inactive. Contact your administrator.', 403);
        }

        $company = Company::find($user->company_id);

        if ($company === null || ! $company->isActive()) {
            return $this->errorResponse('Your company account is inactive.', 403);
        }

        // Revoke all previous tokens for this device
        $deviceName = $request->device_name ?? 'api-token';
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        $user->update(['last_login' => now()]);

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'language' => $user->language ?? 'en',
                'roles' => $user->getRoleNames(),
            ],
        ], 'Login successful');
    }

    /**
     * POST /api/v1/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * GET /api/v1/profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = Company::find($user->company_id);

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'language' => $user->language ?? 'en',
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->company_name,
            ] : null,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * PUT /api/v1/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'language' => ['nullable', 'in:en,ur'],
        ]);

        $user->update($validated);

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'language' => $user->language,
        ], 'Profile updated successfully');
    }

    /**
     * PUT /api/v1/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // Do NOT pre-hash — User model has 'password' => 'hashed' cast which hashes automatically
        $user->update(['password' => $request->password]);

        // Revoke all tokens to force re-login on other devices
        $user->tokens()->whereNot('id', $request->user()->currentAccessToken()->id)->delete();

        return $this->successResponse(null, 'Password changed successfully');
    }

    /**
     * GET /api/v1/me/unread-notifications-count
     */
    public function unreadNotificationsCount(Request $request): JsonResponse
    {
        $count = $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->count();

        return $this->successResponse(['count' => $count]);
    }
}
