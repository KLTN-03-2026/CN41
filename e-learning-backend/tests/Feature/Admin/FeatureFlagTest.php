<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class FeatureFlagTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Feature::define('ai-quiz', true);
        Feature::define('hls-transcoding', true);
        Feature::define('payout-requests', true);
    }

    public function test_super_admin_can_list_feature_flags(): void
    {
        $this->setupAdmin();

        $response = $this->getJson('/api/v1/admin/feature-flags');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_list_returns_correct_flag_keys(): void
    {
        $this->setupAdmin();

        $response = $this->getJson('/api/v1/admin/feature-flags');

        $keys = collect($response->json('data'))->pluck('key')->toArray();
        $this->assertContains('ai-quiz', $keys);
        $this->assertContains('hls-transcoding', $keys);
        $this->assertContains('payout-requests', $keys);
    }

    public function test_super_admin_can_deactivate_flag(): void
    {
        $this->setupAdmin();

        $response = $this->patchJson('/api/v1/admin/feature-flags/ai-quiz', ['active' => false]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertFalse(Feature::active('ai-quiz'));
    }

    public function test_super_admin_can_activate_flag(): void
    {
        $this->setupAdmin();
        Feature::deactivate('ai-quiz');

        $response = $this->patchJson('/api/v1/admin/feature-flags/ai-quiz', ['active' => true]);

        $response->assertStatus(200);
        $this->assertTrue(Feature::active('ai-quiz'));
    }

    public function test_invalid_flag_key_returns_422(): void
    {
        $this->setupAdmin();

        $response = $this->patchJson('/api/v1/admin/feature-flags/unknown-flag', ['active' => false]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_access_feature_flags(): void
    {
        $response = $this->getJson('/api/v1/admin/feature-flags');

        $response->assertStatus(401);
    }
}
