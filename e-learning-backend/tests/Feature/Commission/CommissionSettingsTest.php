<?php

namespace Tests\Feature\Commission;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Commission\Models\CommissionSetting;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class CommissionSettingsTest extends TestCase
{
    use RefreshDatabase, HasAdminUser;

    public function test_get_returns_current_rate(): void
    {
        $this->setupAdmin();
        CommissionSetting::create(['teacher_rate' => 70.00]);

        $response = $this->getJson('/api/v1/admin/commission-settings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.teacher_rate', '70.00');
    }

    public function test_patch_updates_rate(): void
    {
        $this->setupAdmin();
        CommissionSetting::create(['teacher_rate' => 70.00]);

        $response = $this->patchJson('/api/v1/admin/commission-settings', ['teacher_rate' => 75.00]);

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertDatabaseHas('commission_settings', ['teacher_rate' => 75.00]);
    }

    public function test_patch_rejects_rate_above_100(): void
    {
        $this->setupAdmin();

        $response = $this->patchJson('/api/v1/admin/commission-settings', ['teacher_rate' => 110]);

        $response->assertStatus(422);
    }
}
