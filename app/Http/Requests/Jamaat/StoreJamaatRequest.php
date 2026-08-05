<?php

namespace App\Http\Requests\Jamaat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJamaatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('jamaat.create') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'jamaat_name' => ['required', 'string', 'max:255'],
            'jamaat_number' => [
                'required', 'string', 'max:50',
                Rule::unique('jamaats')->where('company_id', $companyId),
            ],
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'leader_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'vice_leader_id' => [
                'nullable',
                Rule::exists('employees', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
                'different:leader_id',
            ],
            'status' => ['required', 'integer', 'in:0,1,2'],
        ];
    }
}
