<?php

namespace Tests\Feature;

use Tests\TestCase;

class MasterStyleValidationTest extends TestCase
{
    public function test_salah_attendance_reason_rejects_non_hex_color_values(): void
    {
        $user = $this->createUserWithCompany(['attendance_reason.manage']);

        $this->actingAs($user)
            ->post(route('masters.attendance-reasons.store', ['type' => 'salah']), [
                'reason_name' => 'Unsafe color',
                'color' => 'red;display:none',
                'status' => 1,
            ])
            ->assertSessionHasErrors('color');
    }

    public function test_quran_attendance_reason_rejects_non_hex_color_values(): void
    {
        $user = $this->createUserWithCompany(['attendance_reason.manage']);

        $this->actingAs($user)
            ->post(route('masters.attendance-reasons.store', ['type' => 'quran']), [
                'reason_name' => 'Unsafe color',
                'color' => 'red;display:none',
                'status' => 1,
            ])
            ->assertSessionHasErrors('color');
    }

    public function test_taleem_attendance_reason_rejects_non_hex_color_values(): void
    {
        $user = $this->createUserWithCompany(['attendance_reason.manage']);

        $this->actingAs($user)
            ->post(route('masters.attendance-reasons.store', ['type' => 'taleem']), [
                'reason_name' => 'Unsafe color',
                'color' => 'red;display:none',
                'status' => 1,
            ])
            ->assertSessionHasErrors('color');
    }

    public function test_quran_status_rejects_non_hex_color_values(): void
    {
        $user = $this->createUserWithCompany(['quran_status.manage']);

        $this->actingAs($user)
            ->post(route('masters.quran-statuses.store'), [
                'status_name' => 'Unsafe color',
                'color' => 'red;display:none',
                'display_order' => 1,
                'status' => 1,
            ])
            ->assertSessionHasErrors('color');
    }
}
