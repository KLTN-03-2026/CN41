<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\CommissionSetting;
use Modules\Commission\Models\TeacherEarning;
use Modules\Teachers\Models\Teachers;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeacherPortalTest extends TestCase
{
    use RefreshDatabase;

    private function setupTeacher(): array
    {
        $user = User::forceCreate(['name' => 'Teacher User', 'email' => 'teacher@test.com', 'password' => 'password']);
        $role = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'admin']);
        $user->assignRole($role);
        $teacher = Teachers::create(['name' => 'Teacher', 'slug' => 'teacher-t', 'exp' => 1, 'status' => 1, 'user_id' => $user->id]);
        $this->actingAs($user, 'admin');

        return [$user, $teacher];
    }

    public function test_teacher_can_view_own_earnings_with_balance(): void
    {
        [, $teacher] = $this->setupTeacher();
        TeacherEarning::create(['teacher_id' => $teacher->id, 'type' => 'credit', 'amount' => 300000, 'commission_rate' => 70]);

        $response = $this->getJson('/api/v1/teacher/earnings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.balance.available', 300000);
    }

    public function test_teacher_can_request_payout_within_balance(): void
    {
        [, $teacher] = $this->setupTeacher();
        CommissionSetting::create(['teacher_rate' => 70]);
        TeacherEarning::create(['teacher_id' => $teacher->id, 'type' => 'credit', 'amount' => 500000, 'commission_rate' => 70]);

        $response = $this->postJson('/api/v1/teacher/payouts', ['amount' => 300000]);

        $response->assertStatus(201)->assertJsonPath('success', true);
        $this->assertDatabaseHas('teacher_payouts', ['teacher_id' => $teacher->id, 'amount' => 300000, 'status' => 'pending']);
    }

    public function test_teacher_cannot_request_payout_exceeding_balance(): void
    {
        [, $teacher] = $this->setupTeacher();
        TeacherEarning::create(['teacher_id' => $teacher->id, 'type' => 'credit', 'amount' => 100000, 'commission_rate' => 70]);

        $response = $this->postJson('/api/v1/teacher/payouts', ['amount' => 500000]);

        $response->assertStatus(422);
    }
}
