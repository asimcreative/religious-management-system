<?php

namespace App\Http\Requests\Language;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('language.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'language_name' => ['required', 'string', 'max:100'],
            'native_name' => ['nullable', 'string', 'max:100'],
            'locale' => ['required', 'string', 'max:10'],
            'direction' => ['required', 'string', 'in:ltr,rtl'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
