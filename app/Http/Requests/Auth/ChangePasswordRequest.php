<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\PasswordNotReused;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [
                'required',
                'string',
                'confirmed',
                new StrongPassword,
                new PasswordNotReused($user),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => __('auth.current_password_incorrect'),
        ];
    }
}
