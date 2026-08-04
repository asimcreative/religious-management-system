<?php

namespace App\Http\Requests\Designation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('designation.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'designation_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
