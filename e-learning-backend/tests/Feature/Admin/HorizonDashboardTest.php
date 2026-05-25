<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class HorizonDashboardTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_horizon(): void
    {
        $response = $this->get('/horizon');

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_admin_user_can_access_horizon(): void
    {
        $this->setupAdmin();

        $response = $this->get('/horizon');

        $response->assertStatus(200);
    }
}
