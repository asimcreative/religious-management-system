<?php

namespace Tests\Feature\Masters;

use App\Models\QuranDepartment;
use App\Models\User;
use Tests\TestCase;

/**
 * QuranDepartment's `progress_fields_schema` builder — validation of the
 * dynamic Progress Fields the admin defines per department.
 */
class QuranDepartmentSchemaTest extends TestCase
{
    private function admin(): User
    {
        return $this->createUserWithCompany(['quran_department.manage']);
    }

    /** @return array<string, mixed> */
    private function basePayload(): array
    {
        return [
            'department_name' => 'Qaida',
            'display_order' => 1,
            'status' => 1,
        ];
    }

    public function test_store_accepts_a_valid_mixed_schema(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('masters.quran-departments.store'), [
                ...$this->basePayload(),
                'progress_fields_schema' => [
                    ['label' => 'Current Takhti', 'key' => 'current_takhti', 'type' => 'number', 'min' => 1, 'max' => 17, 'required' => '1'],
                    ['label' => 'Letter Recognition', 'key' => 'letter_recognition', 'type' => 'select', 'options' => 'Excellent, Average, Weak'],
                    ['label' => "Teacher's Remarks", 'key' => 'teacher_remarks', 'type' => 'text'],
                ],
            ])
            ->assertRedirect();

        $department = QuranDepartment::where('department_name', 'Qaida')->firstOrFail();

        $this->assertSame([
            ['key' => 'current_takhti', 'label' => 'Current Takhti', 'type' => 'number', 'required' => true, 'min' => 1, 'max' => 17],
            ['key' => 'letter_recognition', 'label' => 'Letter Recognition', 'type' => 'select', 'required' => false, 'options' => ['Excellent', 'Average', 'Weak']],
            ['key' => 'teacher_remarks', 'label' => "Teacher's Remarks", 'type' => 'text', 'required' => false],
        ], $department->progress_fields_schema);
    }

    public function test_store_rejects_a_duplicate_key(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('masters.quran-departments.store'), [
                ...$this->basePayload(),
                'progress_fields_schema' => [
                    ['label' => 'Field A', 'key' => 'shared_key', 'type' => 'text'],
                    ['label' => 'Field B', 'key' => 'shared_key', 'type' => 'text'],
                ],
            ])
            ->assertSessionHasErrors('progress_fields_schema');
    }

    public function test_store_rejects_an_invalid_key_format(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('masters.quran-departments.store'), [
                ...$this->basePayload(),
                'progress_fields_schema' => [
                    ['label' => 'Field A', 'key' => '1invalid', 'type' => 'text'],
                ],
            ])
            ->assertSessionHasErrors('progress_fields_schema');
    }

    public function test_store_rejects_a_select_field_with_fewer_than_two_options(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('masters.quran-departments.store'), [
                ...$this->basePayload(),
                'progress_fields_schema' => [
                    ['label' => 'Rating', 'key' => 'rating', 'type' => 'select', 'options' => 'Excellent'],
                ],
            ])
            ->assertSessionHasErrors('progress_fields_schema');
    }

    public function test_store_rejects_a_select_field_with_duplicate_options(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('masters.quran-departments.store'), [
                ...$this->basePayload(),
                'progress_fields_schema' => [
                    ['label' => 'Rating', 'key' => 'rating', 'type' => 'select', 'options' => 'Excellent, Excellent'],
                ],
            ])
            ->assertSessionHasErrors('progress_fields_schema');
    }

    public function test_store_rejects_a_number_field_where_min_is_greater_than_max(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('masters.quran-departments.store'), [
                ...$this->basePayload(),
                'progress_fields_schema' => [
                    ['label' => 'Current Juz', 'key' => 'current_juz', 'type' => 'number', 'min' => 30, 'max' => 1],
                ],
            ])
            ->assertSessionHasErrors('progress_fields_schema');
    }

    public function test_store_rejects_an_unknown_type(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('masters.quran-departments.store'), [
                ...$this->basePayload(),
                'progress_fields_schema' => [
                    ['label' => 'Field A', 'key' => 'field_a', 'type' => 'checkbox'],
                ],
            ])
            ->assertSessionHasErrors('progress_fields_schema');
    }

    public function test_store_accepts_progress_fields_schema_omitted_entirely(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('masters.quran-departments.store'), $this->basePayload())
            ->assertRedirect();

        $department = QuranDepartment::where('department_name', 'Qaida')->firstOrFail();
        $this->assertNull($department->progress_fields_schema);
    }

    public function test_store_splits_comma_separated_options_into_an_array(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('masters.quran-departments.store'), [
                ...$this->basePayload(),
                'progress_fields_schema' => [
                    ['label' => 'Rating', 'key' => 'rating', 'type' => 'select', 'options' => ' Excellent ,  Average ,Weak '],
                ],
            ])
            ->assertRedirect();

        $department = QuranDepartment::where('department_name', 'Qaida')->firstOrFail();
        $this->assertSame(['Excellent', 'Average', 'Weak'], $department->progress_fields_schema[0]['options']);
    }

    public function test_update_replaces_the_schema(): void
    {
        $user = $this->admin();
        $department = QuranDepartment::factory()->create([
            'company_id' => $user->company_id,
            'progress_fields_schema' => [
                ['key' => 'old_field', 'label' => 'Old Field', 'type' => 'text', 'required' => false],
            ],
        ]);

        $this->actingAs($user)
            ->put(route('masters.quran-departments.update', $department), [
                ...$this->basePayload(),
                'progress_fields_schema' => [
                    ['label' => 'New Field', 'key' => 'new_field', 'type' => 'number', 'min' => 1, 'max' => 10],
                ],
            ])
            ->assertRedirect();

        $department->refresh();
        $this->assertCount(1, $department->progress_fields_schema);
        $this->assertSame('new_field', $department->progress_fields_schema[0]['key']);
    }
}
