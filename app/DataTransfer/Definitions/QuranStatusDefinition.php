<?php

namespace App\DataTransfer\Definitions;

use App\Enums\Status;
use App\Models\QuranStatus;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;

class QuranStatusDefinition extends AbstractResourceDefinition
{
    public function key(): string
    {
        return 'quran-statuses';
    }

    public function modelClass(): string
    {
        return QuranStatus::class;
    }

    public function label(): string
    {
        return __('masters.quran_statuses');
    }

    public function singularLabel(): string
    {
        return __('masters.quran_status');
    }

    public function icon(): string
    {
        return 'bi-patch-check';
    }

    public function indexRoute(): string
    {
        return 'masters.quran-statuses.index';
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'quran_status.manage',
            'import' => 'quran_status.import',
            'export' => 'quran_status.export',
            'sample' => 'quran_status.import',
        ];
    }

    /** @return array<int, Column> */
    protected function defineColumns(): array
    {
        return [
            // "status_name" rather than "status": the record's own active flag
            // is a separate column, and conflating them in a template is how
            // people end up importing the wrong value into the wrong field.
            Column::string('status_name')
                ->label(__('masters.status_name'))
                ->required()
                ->unique()
                ->rules(['max:255'])
                ->sample('In Progress')
                ->width(24),

            Column::text('description')
                ->label(__('masters.description'))
                ->rules(['max:1000'])
                ->sample('Currently studying')
                ->width(34),

            Column::string('color')
                ->label(__('masters.color'))
                ->rules(['regex:/^#[0-9A-Fa-f]{6}$/'])
                ->help(__('masters.color_help'))
                ->sample('#0F766E'),

            Column::string('icon')
                ->label(__('masters.icon'))
                ->rules(['max:50'])
                ->help(__('masters.icon_help'))
                ->sample('bi-hourglass-split'),

            Column::integer('display_order')
                ->label(__('masters.display_order'))
                ->required()
                ->rules(['min:0', 'max:255'])
                ->help(__('masters.display_order_help'))
                ->sample(1),

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
        return ['status_name', 'description'];
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
