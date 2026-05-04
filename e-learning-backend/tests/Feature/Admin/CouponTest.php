<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Coupons\Models\Coupon;
use Modules\Users\Models\User;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase, \Tests\Traits\HasAdminUser;

    private string $baseUrl = '/api/v1/admin/coupons';

    protected function setupStudent()
    {
        $student = User::forceCreate([
            'name' => 'Student Test',
            'email' => 'student_coupon_test@test.com',
            'password' => 'password123',
            'email_verified_at' => now(), // Đã xác thực
        ]);

        $this->actingAs($student, 'api');

        return $student;
    }

    public function test_coupons_index_returns_success()
    {
        $this->setupAdmin();
        Coupon::create(['code' => 'TEST1', 'type' => 'fixed', 'value' => 50000]);
        Coupon::create(['code' => 'TEST2', 'type' => 'percentage', 'value' => 10]);

        $response = $this->getJson($this->baseUrl);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_coupons_index_requires_admin()
    {
        $response = $this->getJson($this->baseUrl);
        $response->assertStatus(401);
    }

    public function test_create_coupon_success()
    {
        $this->setupAdmin();

        $response = $this->postJson($this->baseUrl, [
            'code' => 'SUMMER2024',
            'type' => 'fixed',
            'value' => 100000,
            'usage_limit' => 50,
            'description' => 'Giảm 100k mùa hè',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('coupons', [
            'code' => 'SUMMER2024',
            'value' => 100000,
            'usage_limit' => 50,
        ]);
    }

    public function test_create_coupon_fails_with_duplicate_code()
    {
        $this->setupAdmin();
        Coupon::create(['code' => 'DUP123', 'type' => 'fixed', 'value' => 10]);

        $response = $this->postJson($this->baseUrl, [
            'code' => 'DUP123',
            'type' => 'fixed',
            'value' => 20,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_update_coupon_success()
    {
        $this->setupAdmin();
        $coupon = Coupon::create(['code' => 'OLDCODE', 'type' => 'fixed', 'value' => 10000]);

        $response = $this->putJson($this->baseUrl.'/'.$coupon->id, [
            'code' => 'NEWCODE',
            'type' => 'percentage',
            'value' => 15,
            'max_discount' => 50000,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'NEWCODE',
            'type' => 'percentage',
            'value' => 15,
            'max_discount' => 50000,
        ]);
    }

    public function test_toggle_coupon_status()
    {
        $this->setupAdmin();
        $coupon = Coupon::create(['code' => 'C1', 'type' => 'fixed', 'value' => 10, 'status' => 1]);

        $response = $this->patchJson($this->baseUrl.'/'.$coupon->id.'/toggle-status');

        $response->assertStatus(200);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'status' => 0]);

        $this->patchJson($this->baseUrl.'/'.$coupon->id.'/toggle-status');
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'status' => 1]);
    }

    public function test_delete_coupon_soft_delete()
    {
        $this->setupAdmin();
        $coupon = Coupon::create(['code' => 'DEL1', 'type' => 'fixed', 'value' => 10]);

        $response = $this->deleteJson($this->baseUrl.'/'.$coupon->id);

        $response->assertStatus(200);
        $this->assertSoftDeleted('coupons', ['id' => $coupon->id]);
    }

    public function test_bulk_delete_coupons()
    {
        $this->setupAdmin();
        $c1 = Coupon::create(['code' => 'C1', 'type' => 'fixed', 'value' => 10]);
        $c2 = Coupon::create(['code' => 'C2', 'type' => 'fixed', 'value' => 10]);

        $response = $this->deleteJson($this->baseUrl.'/bulk-delete', [
            'ids' => [$c1->id, $c2->id],
        ]);

        $response->assertStatus(200);
        $this->assertSoftDeleted('coupons', ['id' => $c1->id]);
        $this->assertSoftDeleted('coupons', ['id' => $c2->id]);
    }

    public function test_validate_coupon_success()
    {
        $this->setupStudent();
        Coupon::create([
            'code' => 'VALIDATE10',
            'type' => 'fixed',
            'value' => 100000,
            'min_order_value' => 200000,
            'status' => 1,
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'VALIDATE10',
            'subtotal' => 300000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.discount_amount', 100000)
            ->assertJsonPath('data.code', 'VALIDATE10');
    }

    public function test_validate_coupon_fails_min_order()
    {
        $this->setupStudent();
        Coupon::create([
            'code' => 'MIN500K',
            'type' => 'fixed',
            'value' => 50000,
            'min_order_value' => 500000,
            'status' => 1,
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'MIN500K',
            'subtotal' => 400000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Đơn hàng tối thiểu 500,000₫ để sử dụng mã này.');
    }

    public function test_validate_coupon_fails_expired()
    {
        $this->setupStudent();
        Coupon::create([
            'code' => 'EXPIRED',
            'type' => 'fixed',
            'value' => 50000,
            'end_date' => now()->subDay(),
            'status' => 1,
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'EXPIRED',
            'subtotal' => 200000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Mã giảm giá đã hết hạn.');
    }
}
