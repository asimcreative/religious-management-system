<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Jamaat;
use App\Models\QuranClass;
use App\Models\Teacher;
use App\Models\User;
use Tests\TestCase;

/**
 * An employee belongs to at most one active jamaat and one active Quran class.
 *
 * The rule was already in the specification and in the interface copy — "Only
 * active employees not already in a jamaat are listed" — but the query behind
 * that sentence only excluded the members of the jamaat being looked at. So a
 * member of another jamaat was offered, and picking them moved them: their old
 * jamaat lost a member without a word on any screen, and its attendance sheet
 * was simply shorter the next morning.
 */
class OneActiveMembershipTest extends TestCase
{
    private function jamaatAdmin(): User
    {
        return $this->createUserWithCompany(['jamaat.view', 'jamaat.create', 'jamaat.update']);
    }

    private function classAdmin(): User
    {
        return $this->createUserWithCompany(['quran.class.view', 'quran.class.create', 'quran.class.update']);
    }

    private function makeJamaat(User $user, string $name): Jamaat
    {
        return Jamaat::factory()->create([
            'company_id' => $user->company_id,
            'jamaat_name' => $name,
            'branch_id' => Branch::factory()->create(['company_id' => $user->company_id])->id,
            'leader_id' => Employee::factory()->create(['company_id' => $user->company_id])->id,
        ]);
    }

    private function makeClass(User $user, string $name): QuranClass
    {
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $teacher = Teacher::factory()->create([
            'company_id' => $user->company_id,
            'employee_id' => $employee->id,
        ]);

        return QuranClass::factory()->create([
            'company_id' => $user->company_id,
            'class_name' => $name,
            'teacher_id' => $teacher->id,
        ]);
    }

    // ── Jamaats ────────────────────────────────────────────────────────────

    public function test_an_employee_in_another_jamaat_is_not_offered(): void
    {
        $user = $this->jamaatAdmin();
        $taken = $this->makeJamaat($user, 'Jamaat A');
        $offering = $this->makeJamaat($user, 'Jamaat B');

        $member = Employee::factory()->create([
            'company_id' => $user->company_id,
            'employee_name' => 'Already Placed',
        ]);
        $free = Employee::factory()->create([
            'company_id' => $user->company_id,
            'employee_name' => 'Still Free',
        ]);
        $taken->members()->attach($member->id, ['is_active' => true, 'joined_at' => now()]);

        $this->actingAs($user)
            ->get(route('jamaats.members.index', $offering))
            ->assertOk()
            ->assertSee('Still Free')
            ->assertDontSee('Already Placed');
    }

    public function test_an_employee_released_from_a_jamaat_is_offered_again(): void
    {
        $user = $this->jamaatAdmin();
        $first = $this->makeJamaat($user, 'Jamaat A');
        $second = $this->makeJamaat($user, 'Jamaat B');
        $employee = Employee::factory()->create([
            'company_id' => $user->company_id,
            'employee_name' => 'Moves Along',
        ]);

        $this->actingAs($user)->post(route('jamaats.members.store', $first), [
            'employee_id' => $employee->id,
        ])->assertRedirect();

        $this->actingAs($user)->delete(route('jamaats.members.destroy', [$first, $employee]))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('jamaats.members.index', $second))
            ->assertOk()
            ->assertSee('Moves Along');
    }

    public function test_posting_an_employee_who_belongs_to_another_jamaat_is_refused(): void
    {
        $user = $this->jamaatAdmin();
        $taken = $this->makeJamaat($user, 'Jamaat A');
        $offering = $this->makeJamaat($user, 'Jamaat B');
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $taken->members()->attach($employee->id, ['is_active' => true, 'joined_at' => now()]);

        $this->actingAs($user)
            ->post(route('jamaats.members.store', $offering), ['employee_id' => $employee->id])
            ->assertRedirect(route('jamaats.members.index', $offering))
            ->assertSessionHas('error');

        // The point of refusing: the jamaat they were in still has them.
        $this->assertDatabaseHas('jamaat_members', [
            'jamaat_id' => $taken->id,
            'employee_id' => $employee->id,
            'is_active' => 1,
        ]);
        $this->assertDatabaseMissing('jamaat_members', [
            'jamaat_id' => $offering->id,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_the_refusal_names_the_jamaat_holding_the_employee(): void
    {
        $user = $this->jamaatAdmin();
        $taken = $this->makeJamaat($user, 'Masjid-e-Noor');
        $offering = $this->makeJamaat($user, 'Jamaat B');
        $employee = Employee::factory()->create([
            'company_id' => $user->company_id,
            'employee_name' => 'Bilal Ahmed',
        ]);

        $taken->members()->attach($employee->id, ['is_active' => true, 'joined_at' => now()]);

        $this->actingAs($user)
            ->post(route('jamaats.members.store', $offering), ['employee_id' => $employee->id])
            ->assertSessionHas('error', __('jamaats.already_in_another_jamaat', [
                'name' => 'Bilal Ahmed',
                'jamaat' => 'Masjid-e-Noor',
            ]));
    }

    public function test_a_member_of_this_jamaat_can_still_be_re_added(): void
    {
        $user = $this->jamaatAdmin();
        $jamaat = $this->makeJamaat($user, 'Jamaat A');
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)->post(route('jamaats.members.store', $jamaat), [
            'employee_id' => $employee->id,
        ])->assertRedirect();

        $this->actingAs($user)
            ->post(route('jamaats.members.store', $jamaat), ['employee_id' => $employee->id])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('jamaat_members', [
            'jamaat_id' => $jamaat->id,
            'employee_id' => $employee->id,
            'is_active' => 1,
        ]);
    }

    public function test_another_companys_membership_does_not_block_an_employee(): void
    {
        // Company isolation cuts both ways: a neighbouring tenant's jamaat must
        // not make an employee look unavailable here.
        $user = $this->jamaatAdmin();
        $other = $this->createUserWithCompany(['jamaat.update']);
        $otherJamaat = $this->makeJamaat($other, 'Their Jamaat');
        $otherEmployee = Employee::factory()->create(['company_id' => $other->company_id]);
        $otherJamaat->members()->attach($otherEmployee->id, ['is_active' => true, 'joined_at' => now()]);

        $jamaat = $this->makeJamaat($user, 'Our Jamaat');
        $employee = Employee::factory()->create([
            'company_id' => $user->company_id,
            'employee_name' => 'Ours Only',
        ]);

        $this->actingAs($user)
            ->get(route('jamaats.members.index', $jamaat))
            ->assertOk()
            ->assertSee('Ours Only');

        $this->actingAs($user)
            ->post(route('jamaats.members.store', $jamaat), ['employee_id' => $employee->id])
            ->assertSessionHas('success');
    }

    // ── Quran classes ──────────────────────────────────────────────────────

    public function test_an_employee_in_another_class_is_not_offered(): void
    {
        $user = $this->classAdmin();
        $taken = $this->makeClass($user, 'Class A');
        $offering = $this->makeClass($user, 'Class B');

        $member = Employee::factory()->create([
            'company_id' => $user->company_id,
            'employee_name' => 'Already Enrolled',
        ]);
        $free = Employee::factory()->create([
            'company_id' => $user->company_id,
            'employee_name' => 'Not Enrolled',
        ]);
        $taken->members()->attach($member->id, ['is_active' => true, 'joined_at' => now()]);

        $this->actingAs($user)
            ->get(route('quran-classes.members.index', $offering))
            ->assertOk()
            ->assertSee('Not Enrolled')
            ->assertDontSee('Already Enrolled');
    }

    public function test_posting_an_employee_who_belongs_to_another_class_is_refused(): void
    {
        $user = $this->classAdmin();
        $taken = $this->makeClass($user, 'Nazira A');
        $offering = $this->makeClass($user, 'Nazira B');
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $taken->members()->attach($employee->id, ['is_active' => true, 'joined_at' => now()]);

        $this->actingAs($user)
            ->post(route('quran-classes.members.store', $offering), ['employee_id' => $employee->id])
            ->assertRedirect(route('quran-classes.members.index', $offering))
            ->assertSessionHas('error', __('quran_classes.already_in_another_class', [
                'name' => $employee->employee_name,
                'class' => 'Nazira A',
            ]));

        $this->assertDatabaseHas('quran_class_members', [
            'class_id' => $taken->id,
            'employee_id' => $employee->id,
            'is_active' => 1,
        ]);
        $this->assertDatabaseMissing('quran_class_members', [
            'class_id' => $offering->id,
            'employee_id' => $employee->id,
        ]);
    }

    public function test_a_member_of_this_class_can_still_be_re_added(): void
    {
        $user = $this->classAdmin();
        $class = $this->makeClass($user, 'Class A');
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user)->post(route('quran-classes.members.store', $class), [
            'employee_id' => $employee->id,
        ])->assertRedirect();

        $this->actingAs($user)
            ->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('quran_class_members', [
            'class_id' => $class->id,
            'employee_id' => $employee->id,
            'is_active' => 1,
        ]);
    }

    public function test_a_jamaat_membership_does_not_block_a_class_and_the_other_way_round(): void
    {
        // The two rules are independent: everyone attends prayers and may also
        // study, so one membership must not consume the other.
        $user = $this->createUserWithCompany([
            'jamaat.view', 'jamaat.update', 'quran.class.view', 'quran.class.update',
        ]);
        $jamaat = $this->makeJamaat($user, 'Jamaat A');
        $class = $this->makeClass($user, 'Class A');
        $employee = Employee::factory()->create([
            'company_id' => $user->company_id,
            'employee_name' => 'Prays And Studies',
        ]);

        $this->actingAs($user)->post(route('jamaats.members.store', $jamaat), [
            'employee_id' => $employee->id,
        ])->assertSessionHas('success');

        $this->actingAs($user)
            ->get(route('quran-classes.members.index', $class))
            ->assertOk()
            ->assertSee('Prays And Studies');

        $this->actingAs($user)
            ->post(route('quran-classes.members.store', $class), ['employee_id' => $employee->id])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('jamaat_members', [
            'jamaat_id' => $jamaat->id,
            'employee_id' => $employee->id,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('quran_class_members', [
            'class_id' => $class->id,
            'employee_id' => $employee->id,
            'is_active' => 1,
        ]);
    }
}
