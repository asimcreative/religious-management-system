<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user.create') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'name' => ['required', 'string', 'max:255'],

            // Unique within the company rather than globally: two tenants may
            // legitimately employ the same person, and User::findByUniqueEmail
            // already fails closed when one address spans companies.
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users')->where('company_id', $companyId),
            ],

            'password' => ['required', 'confirmed', Password::defaults()],
            'mobile' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'integer', 'in:0,1,2'],
            'language' => ['nullable', 'string', 'max:10'],

            'roles' => ['array'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web')->where('company_id', $companyId),
            ],
        ];
    }
}
