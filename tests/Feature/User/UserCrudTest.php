<?php

namespace Tests\Feature\User;

use App\Enums\Status;
use App\Models\Company;
use App\Models\User;
use App\Support\DataTransfer\ResourceRegistry;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Account administration.
 *
 * User is the one model in the system without the BelongsToCompany global
 * scope — authentication has to resolve an account before a session exists.
 * Its tenant boundary is therefore hand-applied, which makes it the model most
 * worth testing across companies.
 */
class UserCrudTest extends TestCase
{
    private function admin(array $extra = []): User
    {
        return $this->createUserWithCompany(array_merge(
            ['user.view', 'user.create', 'user.update', 'user.delete', 'permission.assign'],
            $extra,
        ));
    }

    private function roleIn(int $companyId, string $name): Role
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);

        return Role::findOrCreate($name, 'web');
    }

    // ── Access ─────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_sign_in(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_the_list_requires_the_view_permission(): void
    {
        $this->actingAs($this->createUserWithCompany())
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_the_list_shows_only_this_companys_accounts(): void
    {
        $admin = $this->admin();
        $other = Company::factory()->create();

        $colleague = User::factory()->create(['company_id' => $admin->company_id, 'name' => 'Our Colleague']);
        User::factory()->create(['company_id' => $other->id, 'name' => 'Their Person']);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee($colleague->name);
        $response->assertDontSee('Their Person');
    }

    // ── Create ─────────────────────────────────────────────────────

    public function test_an_account_is_created_inside_the_acting_users_company(): void
    {
        $admin = $this->admin();
        $role = $this->roleIn((int) $admin->company_id, 'HR Manager');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Person',
            'email' => 'new@example.test',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
            'status' => Status::Active->value,
            'language' => 'en',
            'roles' => [$role->name],
        ]);

        $created = User::query()->where('email', 'new@example.test')->firstOrFail();

        $response->assertRedirect(route('users.show', $created));

        $this->assertSame($admin->company_id, $created->company_id);
        $this->assertNotSame('Str0ng-Passw0rd!', $created->password, 'The password must be hashed.');

        app(PermissionRegistrar::class)->setPermissionsTeamId($admin->company_id);
        $this->assertTrue($created->fresh()->hasRole('HR Manager'));
    }

    public function test_a_company_id_in_the_request_is_ignored(): void
    {
        $admin = $this->admin();
        $other = Company::factory()->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Injected',
            'email' => 'injected@example.test',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
            'status' => Status::Active->value,
            'company_id' => $other->id,
        ]);

        $created = User::query()->where('email', 'injected@example.test')->firstOrFail();

        $this->assertSame($admin->company_id, $created->company_id);
    }

    public function test_a_role_belonging_to_another_company_cannot_be_assigned(): void
    {
        $admin = $this->admin();
        $other = Company::factory()->create();
        $foreignRole = $this->roleIn($other->id, 'Foreign Role');

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Person',
            'email' => 'person@example.test',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
            'status' => Status::Active->value,
            'roles' => [$foreignRole->name],
        ])->assertSessionHasErrors('roles.0');

        $this->assertDatabaseMissing('users', ['email' => 'person@example.test']);
    }

    public function test_creating_requires_the_create_permission(): void
    {
        $this->actingAs($this->createUserWithCompany(['user.view']))
            ->post(route('users.store'), [
                'name' => 'Nope',
                'email' => 'nope@example.test',
                'password' => 'Str0ng-Passw0rd!',
                'password_confirmation' => 'Str0ng-Passw0rd!',
                'status' => Status::Active->value,
            ])
            ->assertForbidden();
    }

    public function test_a_duplicate_email_within_the_company_is_rejected(): void
    {
        $admin = $this->admin();
        User::factory()->create(['company_id' => $admin->company_id, 'email' => 'taken@example.test']);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Duplicate',
            'email' => 'taken@example.test',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
            'status' => Status::Active->value,
        ])->assertSessionHasErrors('email');
    }

    // ── Read / update / delete across tenants ──────────────────────

    public function test_another_companys_account_is_not_found(): void
    {
        $admin = $this->admin();
        $other = Company::factory()->create();
        $foreign = User::factory()->create(['company_id' => $other->id]);

        $this->actingAs($admin)->get(route('users.show', $foreign))->assertNotFound();
        $this->actingAs($admin)->get(route('users.edit', $foreign))->assertNotFound();
        $this->actingAs($admin)->delete(route('users.destroy', $foreign))->assertNotFound();
    }

    public function test_another_companys_account_cannot_be_updated(): void
    {
        $admin = $this->admin();
        $other = Company::factory()->create();
        $foreign = User::factory()->create(['company_id' => $other->id, 'name' => 'Untouched']);

        $this->actingAs($admin)->put(route('users.update', $foreign), [
            'name' => 'Hijacked',
            'email' => $foreign->email,
            'status' => Status::Active->value,
        ])->assertNotFound();

        $this->assertSame('Untouched', $foreign->fresh()->name);
    }

    public function test_an_account_is_updated(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['company_id' => $admin->company_id]);

        $this->actingAs($admin)->put(route('users.update', $target), [
            'name' => 'Renamed',
            'email' => $target->email,
            'mobile' => '0300-1234567',
            'status' => Status::Inactive->value,
        ])->assertRedirect(route('users.show', $target));

        $target->refresh();
        $this->assertSame('Renamed', $target->name);
        $this->assertSame(Status::Inactive, $target->status);
    }

    public function test_a_blank_password_leaves_the_existing_one_alone(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['company_id' => $admin->company_id]);
        $original = $target->password;

        $this->actingAs($admin)->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'password' => '',
            'status' => Status::Active->value,
        ]);

        $this->assertSame($original, $target->fresh()->password);
    }

    public function test_roles_are_left_alone_without_the_assign_permission(): void
    {
        $admin = $this->createUserWithCompany(['user.view', 'user.update']);
        $role = $this->roleIn((int) $admin->company_id, 'Auditor');

        $target = User::factory()->create(['company_id' => $admin->company_id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($admin->company_id);
        $target->assignRole($role);

        $this->actingAs($admin)->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'status' => Status::Active->value,
            'roles' => [],
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($admin->company_id);
        $this->assertTrue($target->fresh()->hasRole('Auditor'), 'Editing an account must not strip roles the editor may not manage.');
    }

    public function test_an_account_cannot_delete_itself(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_an_account_is_deleted(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['company_id' => $admin->company_id]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    // ── Export ─────────────────────────────────────────────────────

    public function test_the_export_never_carries_a_password(): void
    {
        $admin = $this->createUserWithCompany(['user.view', 'user.export']);

        $definition = app(ResourceRegistry::class)->get('users');
        $keys = array_map(static fn ($column) => $column->key, $definition->columns());

        $this->assertNotContains('password', $keys);
        $this->assertNotContains('remember_token', $keys);
        $this->assertFalse($definition->supportsImport(), 'Accounts must not be creatable from a spreadsheet.');
    }

    public function test_the_export_is_scoped_to_the_company(): void
    {
        $admin = $this->createUserWithCompany(['user.view', 'user.export']);
        $other = Company::factory()->create();

        User::factory()->create(['company_id' => $other->id]);

        $this->actingAs($admin);

        $definition = app(ResourceRegistry::class)->get('users');

        // The acting user plus nobody else: the other company's account and
        // the factory-made owner of that company are both outside the boundary.
        $this->assertSame(
            User::query()->where('company_id', $admin->company_id)->count(),
            $definition->newQuery()->count(),
        );
    }
}
