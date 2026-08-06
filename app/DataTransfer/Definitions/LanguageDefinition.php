<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\Language;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

class LanguageDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'languages';
    }

    public function modelClass(): string
    {
        return Language::class;
    }

    public function label(): string
    {
        return __('masters.languages');
    }

    public function singularLabel(): string
    {
        return __('masters.language');
    }

    public function icon(): string
    {
        return 'bi-translate';
    }

    public function indexRoute(): string
    {
        return 'masters.languages.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'language.manage',
            'import' => 'language.import',
            'export' => 'language.export',
            'sample' => 'language.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            Column::string('language_name')
                ->label(__('masters.language_name'))
                ->required()
                ->unique()
                ->rules(['max:100'])
                ->sample('Urdu')
                ->width(20),

            Column::string('native_name')
                ->label(__('masters.native_name'))
                ->rules(['max:100'])
                ->help(__('masters.native_name_help'))
                ->sample('اردو'),

            // The locale identifies the translation file, so a duplicate would
            // make two records claim the same set of strings.
            Column::string('locale')
                ->label(__('masters.locale'))
                ->required()
                ->unique()
                ->rules(['max:10'])
                ->help(__('masters.locale_help'))
                ->sample('ur'),

            Column::choice('direction', ['ltr' => __('masters.ltr'), 'rtl' => __('masters.rtl')])
                ->label(__('masters.direction'))
                ->required()
                ->sample(__('masters.rtl')),

            Column::enum('status', Status::class)
                ->label(__('masters.status'))
                ->required()
                ->only([Status::Active, Status::Inactive])
                ->sample('Active'),
        ];
    }

    /** @return array<int, string> */
    protected function searchColumns(): array
    {
        return ['language_name', 'native_name', 'locale'];
    }

    /** @return array<string, string> */
    protected function filters(): array
    {
        return ['status' => 'status'];
    }

    /** @return array<string, string> */
    protected function defaultSort(): array
    {
        return ['created_at' => 'desc'];
    }
}
