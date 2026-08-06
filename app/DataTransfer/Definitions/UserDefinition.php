<?php

namespace App\DataTransfer\Definitions;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Enums\Status;
use App\Models\User;
use App\Support\DataTransfer\AbstractResourceDefinition;
use App\Support\DataTransfer\Column;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Export only.
 *
 * Creating a sign-in account from a spreadsheet means granting access in bulk,
 * with a password nobody chose and roles nobody reviewed. That decision
 * belongs on the user form, one account at a time, so this module exports and
 * does not import.
 */
class UserDefinition extends AbstractResourceDefinition
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function key(): string
    {
        return 'users';
    }

    public function modelClass(): string
    {
        return User::class;
    }

    public function label(): string
    {
        return __('users.users');
    }

    public function singularLabel(): string
    {
        return __('users.user');
    }

    public function icon(): string
    {
        return 'bi-person-badge';
    }

    public function indexRoute(): string
    {
        return 'users.index';
    }

    public function supportsImport(): bool
    {
        return false;
    }

    /** @return array<string, string> */
    public function permissions(): array
    {
        return [
            'view' => 'user.view',
            'export' => 'user.export',
        ];
    }

    /**
     * User is the one model without the BelongsToCompany global scope, so its
     * tenant boundary is applied by the repository instead. Every query in
     * this definition must start from there.
     */
    public function newQuery(): Builder
    {
        return $this->users->scoped()->with($this->eagerLoads());
    }

    /**
     * No password column, hashed or otherwise, and no remember token: an
     * export is a file that leaves the building.
     *
     * @return array<int, Column>
     */
    protected function defineColumns(): array
    {
        return [
            Column::string('name')
                ->label(__('users.name'))
                ->width(24),

            Column::email('email')
                ->label(__('users.email'))
                ->width(28),

            Column::phone('mobile')
                ->label(__('users.mobile')),

            Column::computed(
                'roles',
                static fn (User $user): string => $user->roles->pluck('name')->implode(', '),
            )->label(__('users.roles'))->width(26),

            Column::enum('status', Status::class)
                ->label(__('users.status')),

            Column::string('language')
                ->label(__('users.language')),

            Column::datetime('last_login')
                ->label(__('users.last_login'))
                ->width(18),
        ];
    }

    /** @return array<int, string> */
    protected function extraEagerLoads(): array
    {
        return ['roles'];
    }

    /** @return array<int, string> */
    protected function searchColumns(): array
    {
        return ['name', 'email', 'mobile'];
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

    /**
     * Accounts are removed one at a time from the user screen, where the
     * "not yourself, not the platform account" rules are visible.
     */
    public function supportsBulkActions(): bool
    {
        return false;
    }

    public function canDelete(Model $record): bool
    {
        return $record instanceof User && ! $record->isSystemAdministrator();
    }
}
