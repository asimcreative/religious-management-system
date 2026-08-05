<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\QuranClass;
use App\Models\Teacher;
use Tests\TestCase;

class ApiInfrastructureTest extends TestCase
{
    public function test_api_validation_errors_use_the_standard_error_envelope(): void
    {
        $this->postJson('/api/v1/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The given data was invalid.')
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_unauthenticated_api_requests_use_the_standard_error_envelope(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_missing_api_models_use_the_standard_error_envelope(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/employees/999999')
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Resource not found.',
            ]);
    }

    public function test_cors_only_allows_configured_origins(): void
    {
        $originalOrigins = config('cors.allowed_origins');

        try {
            config()->set('cors.allowed_origins', [
                'https://trusted.example',
                'https://second-trusted.example',
            ]);

            $this->withHeaders([
                'Origin' => 'https://trusted.example',
                'Access-Control-Request-Method' => 'POST',
            ])->options('/api/v1/login')
                ->assertNoContent()
                ->assertHeader('Access-Control-Allow-Origin', 'https://trusted.example');

            $this->withHeaders([
                'Origin' => 'https://untrusted.example',
                'Access-Control-Request-Method' => 'POST',
            ])->options('/api/v1/login')
                ->assertNoContent()
                ->assertHeaderMissing('Access-Control-Allow-Origin');
        } finally {
            config(['cors.allowed_origins' => $originalOrigins]);
        }
    }

    public function test_api_pagination_is_capped(): void
    {
        $user = $this->createUserWithCompany(['employee.view']);
        Employee::factory()->create(['company_id' => $user->company_id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/employees?per_page=1000')
            ->assertOk()
            ->assertJsonPath('data.meta.per_page', 100);
    }

    public function test_quran_class_api_eager_loads_teacher_employees(): void
    {
        $user = $this->createUserWithCompany(['quran.class.view']);
        $employee = Employee::factory()->create(['company_id' => $user->company_id]);
        $teacher = Teacher::factory()->create([
            'company_id' => $user->company_id,
            'employee_id' => $employee->id,
        ]);
        QuranClass::factory()->create([
            'company_id' => $user->company_id,
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/quran/classes')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $teacher->id,
                'name' => $employee->employee_name,
            ]);
    }
}
