<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class EmployeeUserLinkIntegrityTest extends TestCase
{
    public function test_a_user_can_be_linked_to_only_one_employee_record(): void
    {
        $user = $this->createUserWithCompany();

        Employee::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);

        $this->expectException(QueryException::class);

        Employee::factory()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
        ]);
    }
}
