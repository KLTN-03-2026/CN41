<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\TeacherPayout;
use Modules\Teachers\Models\Teachers;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class AdminPayoutTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    private function makeTeacher(): Teachers
    {
        return Teachers::create(['name' => 'T', 'slug' => 'teacher-t', 'exp' => 1, 'status' => 1]);
    }

    public function test_admin_can_list_payouts(): void
    {
        $this->setupAdmin();
        $teacher = $this->makeTeacher();
        TeacherPayout::create(['teacher_id' => $teacher->id, 'amount' => 100000, 'status' => 'pending']);

        $this->getJson('/api/v1/admin/payouts')->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_admin_can_approve_payout(): void
    {
        $this->setupAdmin();
        $payout = TeacherPayout::create(['teacher_id' => $this->makeTeacher()->id, 'amount' => 100000, 'status' => 'pending']);

        $this->patchJson("/api/v1/admin/payouts/{$payout->id}/approve", ['admin_note' => 'OK'])
            ->assertStatus(200)->assertJsonPath('success', true);

        $this->assertDatabaseHas('teacher_payouts', ['id' => $payout->id, 'status' => 'approved']);
    }

    public function test_admin_can_reject_payout(): void
    {
        $this->setupAdmin();
        $payout = TeacherPayout::create(['teacher_id' => $this->makeTeacher()->id, 'amount' => 100000, 'status' => 'pending']);

        $this->patchJson("/api/v1/admin/payouts/{$payout->id}/reject", ['admin_note' => 'Thiếu TK'])
            ->assertStatus(200);

        $this->assertDatabaseHas('teacher_payouts', ['id' => $payout->id, 'status' => 'rejected']);
    }

    public function test_admin_can_mark_payout_as_paid(): void
    {
        $this->setupAdmin();
        $payout = TeacherPayout::create(['teacher_id' => $this->makeTeacher()->id, 'amount' => 100000, 'status' => 'approved']);

        $this->patchJson("/api/v1/admin/payouts/{$payout->id}/mark-paid")->assertStatus(200);

        $this->assertDatabaseHas('teacher_payouts', ['id' => $payout->id, 'status' => 'paid']);
    }

    public function test_cannot_approve_non_pending_payout(): void
    {
        $this->setupAdmin();
        $payout = TeacherPayout::create(['teacher_id' => $this->makeTeacher()->id, 'amount' => 100000, 'status' => 'approved']);

        $this->patchJson("/api/v1/admin/payouts/{$payout->id}/approve")->assertStatus(422);
    }
}
