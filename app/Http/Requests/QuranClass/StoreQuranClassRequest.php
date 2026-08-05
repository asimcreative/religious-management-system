<?php

namespace App\Http\Requests\QuranClass;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuranClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('quran.class.create') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'class_name' => ['required', 'string', 'max:100'],
            'class_code' => [
                'required', 'string', 'max:50',
                Rule::unique('quran_classes')->where('company_id', $companyId),
            ],
            'teacher_id' => [
                'required',
                Rule::exists('teachers', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'max_strength' => ['required', 'integer', 'min:1', 'max:999'],
            'status' => ['required', 'integer', 'in:0,1,2'],
        ];
    }
}
