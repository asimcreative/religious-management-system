<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeePhotoAccessTest extends TestCase
{
    public function test_employee_photo_is_served_through_an_authorized_route(): void
    {
        Storage::fake('local');

        $user = $this->createUserWithCompany(['employee.view']);
        $employee = Employee::factory()->create([
            'company_id' => $user->company_id,
            'photo' => 'employees/photos/photo.jpg',
        ]);
        Storage::disk('local')->put($employee->photo, 'image-content');

        $this->actingAs($user)
            ->get(route('employees.photo', $employee))
            ->assertOk();
    }

    public function test_user_without_employee_permission_cannot_view_employee_photo(): void
    {
        Storage::fake('local');

        $authorizedUser = $this->createUserWithCompany(['employee.view']);
        $employee = Employee::factory()->create([
            'company_id' => $authorizedUser->company_id,
            'photo' => 'employees/photos/photo.jpg',
        ]);
        Storage::disk('local')->put($employee->photo, 'image-content');

        $unauthorizedUser = User::factory()->create(['company_id' => $authorizedUser->company_id]);

        $this->actingAs($unauthorizedUser)
            ->get(route('employees.photo', $employee))
            ->assertForbidden();
    }

    public function test_legacy_public_employee_photo_remains_available_through_the_authorized_route(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $user = $this->createUserWithCompany(['employee.view']);
        $employee = Employee::factory()->create([
            'company_id' => $user->company_id,
            'photo' => 'employees/photos/legacy-photo.jpg',
        ]);
        Storage::disk('public')->put($employee->photo, 'legacy-image-content');

        $this->actingAs($user)
            ->get(route('employees.photo', $employee))
            ->assertOk();
    }
}
