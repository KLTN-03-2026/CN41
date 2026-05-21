<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\TeacherEarning;
use Modules\Teachers\Models\Teachers;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class TeacherEarningsTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    public function test_admin_can_get_teacher_earnings_summary(): void
    {
        $this->setupAdmin();
        $teacher = Teachers::create(['name' => 'T', 'slug' => 'teacher-t', 'exp' => 1, 'status' => 1]);
        TeacherEarning::create(['teacher_id' => $teacher->id, 'type' => 'credit', 'amount' => 500000, 'commission_rate' => 70]);

        $response = $this->getJson('/api/v1/admin/teacher-earnings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['total_earned' => 500000]);
    }
}
