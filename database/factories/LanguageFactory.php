<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Company;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'language_name' => 'English',
            'native_name' => 'English',
            'locale' => 'en',
            'direction' => 'ltr',
            'status' => Status::Active,
        ];
    }

    public function urdu(): static
    {
        return $this->state([
            'language_name' => 'Urdu',
            'native_name' => 'اردو',
            'locale' => 'ur',
            'direction' => 'rtl',
        ]);
    }
}
