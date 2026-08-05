<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\PasswordNotReused;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class ApiChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();

        $passwordRules = [
            'required',
            'string',
            'confirmed',
            new StrongPassword,
        ];

        if ($user instanceof User) {
            $passwordRules[] = new PasswordNotReused($user);
        }

        return [
            'current_password' => ['required', 'string'],
            'password' => $passwordRules,
        ];
    }
}
