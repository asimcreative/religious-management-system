<?php

namespace App\Http\Requests\QuranStatus;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuranStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('quran_status.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'display_order' => ['required', 'integer', 'min:0', 'max:255'],
            'status' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
