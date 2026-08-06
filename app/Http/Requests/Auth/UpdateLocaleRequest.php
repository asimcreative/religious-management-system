<?php

namespace App\Http\Requests\Auth;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a display-language change.
 *
 * The language is a personal display preference, not company data, so every
 * visitor — signed in or not — may set their own. The value is constrained to
 * the locales the application actually ships translations for.
 */
class UpdateLocaleRequest extends FormRequest
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
        return [
            'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED_LOCALES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'locale.in' => __('ui.locale_unsupported'),
        ];
    }
}
