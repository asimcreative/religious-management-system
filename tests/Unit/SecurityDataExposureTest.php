<?php

namespace Tests\Unit;

use App\Exports\Concerns\SanitizesSpreadsheetValues;
use App\Models\Employee;
use Tests\TestCase;

class SecurityDataExposureTest extends TestCase
{
    public function test_employee_serialization_hides_cnic_and_lookup_hash(): void
    {
        $employee = Employee::factory()->withCnic()->create();

        $attributes = $employee->toArray();

        $this->assertArrayNotHasKey('cnic', $attributes);
        $this->assertArrayNotHasKey('cnic_hash', $attributes);
    }

    public function test_spreadsheet_values_starting_with_formula_characters_are_escaped(): void
    {
        $sanitizer = new class
        {
            use SanitizesSpreadsheetValues;

            /** @param array<int, mixed> $values
             * @return array<int, mixed>
             */
            public function sanitize(array $values): array
            {
                return $this->sanitizeSpreadsheetValues($values);
            }
        };

        $this->assertSame(
            ["'=SUM(A1:A2)", "'+cmd", "'-1", "'@value", "' =SUM(A1:A2)", "'\t=cmd", "'\r@value", 'normal', 10],
            $sanitizer->sanitize(['=SUM(A1:A2)', '+cmd', '-1', '@value', ' =SUM(A1:A2)', "\t=cmd", "\r@value", 'normal', 10]),
        );
    }
}
