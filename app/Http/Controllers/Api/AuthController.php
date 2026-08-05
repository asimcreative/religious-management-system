<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\ApiChangePasswordRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class AuthController extends BaseApiController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

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

        $result = $this->authService->attemptLogin(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        if (! $result['success']) {
            if ($result['error'] === 'account_inactive') {
                return $this->errorResponse('Your account is inactive. Contact your administrator.', 403);
            }

            if ($result['error'] === 'company_inactive') {
                return $this->errorResponse('Your company account is inactive.', 403);
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var User $user */
        $user = $result['user'];

        // The login route runs before the authenticated-route middleware that
        // normally establishes Spatie's tenant team context.
        $this->permissionRegistrar->setPermissionsTeamId((int) $user->company_id);

        // Revoke all previous tokens for this device
        $deviceName = $request->device_name ?? 'api-token';
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        $this->authService->recordTokenLogin($user);

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
        /** @var User $user */
        $user = $request->user();
        $token = $user->currentAccessToken();

        if (method_exists($token, 'delete')) {
            $token->delete();
        } elseif ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $this->authService->recordTokenLogout($user);

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
    public function changePassword(ApiChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // Do NOT pre-hash — User model has 'password' => 'hashed' cast which hashes automatically
        $this->authService->changePassword($user, $request->validated('password'));

        return $this->successResponse(null, 'Password changed successfully');
    }

    /**
     * GET /api/v1/me/unread-notifications-count
     */
    public function unreadNotificationsCount(Request $request): JsonResponse
    {
        $this->authorize('notification.view');

        $count = $request->user()
            ->notifications()
            ->whereNull('read_at')
            ->count();

        return $this->successResponse(['count' => $count]);
    }
}
