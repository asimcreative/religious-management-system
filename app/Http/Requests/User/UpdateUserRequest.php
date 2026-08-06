<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * The broad right only. Whether this particular account may be edited is
     * decided by the policy in the controller, which resolves the target
     * through the company-scoped repository first.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('user.update') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;
        $target = User::query()->find($this->route('user'));

        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users')
                    ->where('company_id', $companyId)
                    ->ignore($target?->id),
            ],

            // Blank leaves the existing password alone; the service drops the
            // key rather than writing an empty hash.
            'password' => ['nullable', 'confirmed', Password::defaults()],
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
