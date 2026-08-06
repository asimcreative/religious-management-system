<?php

namespace App\Http\Requests\Company;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Company::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'company_code' => ['required', 'string', 'max:50', Rule::unique('companies', 'company_code')],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['required', 'string', 'timezone'],
            'default_language' => ['nullable', 'string', 'max:10'],
            'subscription_plan' => ['nullable', 'string', 'max:50'],
            'subscription_expiry' => ['nullable', 'date'],
            'status' => ['required', 'integer', 'in:0,1,2'],
        ];
    }
}
