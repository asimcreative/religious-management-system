<?php

namespace App\Http\Requests\Jamaat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJamaatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('jamaat.update') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;
        $jamaatId = $this->route('jamaat')?->id;

        return [
            'jamaat_name' => ['required', 'string', 'max:255'],
            'jamaat_number' => [
                'required', 'string', 'max:50',
                Rule::unique('jamaats')->where('company_id', $companyId)->ignore($jamaatId),
            ],
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
            ],
            'leader_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'vice_leader_id' => [
                'nullable',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
                'different:leader_id',
            ],
            'status' => ['required', 'integer', 'in:0,1,2'],
        ];
    }
}
