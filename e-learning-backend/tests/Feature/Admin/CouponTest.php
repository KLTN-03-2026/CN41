<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Coupons\Models\Coupon;
use Modules\Students\Models\Student;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase, \Tests\Traits\HasAdminUser;

    private string $baseUrl = '/api/v1/admin/coupons';

    protected function setupStudent()
    {
        $student = Student::forceCreate([
            'name' => 'Student Test',
            'email' => 'student_coupon_test@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
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

        $response = $this->patchJson($this->baseUrl.'/'.$coupon->id, [
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

    public function test_validate_coupon_fails_when_inactive()
    {
        $this->setupStudent();
        Coupon::create(['code' => 'INACTIVE', 'type' => 'fixed', 'value' => 50000, 'status' => 0]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'INACTIVE',
            'subtotal' => 300000,
        ]);

        $response->assertStatus(422);
    }

    public function test_validate_coupon_fails_when_exhausted()
    {
        $this->setupStudent();
        Coupon::create([
            'code' => 'USED_UP',
            'type' => 'fixed',
            'value' => 50000,
            'usage_limit' => 1,
            'used_count' => 1,
            'status' => 1,
        ]);

        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'USED_UP',
            'subtotal' => 300000,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Mã giảm giá đã hết lượt sử dụng.');
    }

    public function test_validate_percentage_coupon_respects_max_discount_cap()
    {
        $this->setupStudent();
        Coupon::create([
            'code' => 'PCT20',
            'type' => 'percentage',
            'value' => 20,
            'max_discount' => 50000,
            'status' => 1,
        ]);

        // 20% of 500k = 100k → capped at 50k
        $response = $this->postJson('/api/v1/coupons/validate', [
            'code' => 'PCT20',
            'subtotal' => 500000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.discount_amount', 50000);
    }

    public function test_trashed_coupons_returns_deleted_coupons()
    {
        $this->setupAdmin();
        $coupon = Coupon::create(['code' => 'TRASHED1', 'type' => 'fixed', 'value' => 10]);
        $coupon->delete();

        $response = $this->getJson($this->baseUrl.'/trashed');

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'TRASHED1']);
    }

    public function test_restore_coupon_success()
    {
        $this->setupAdmin();
        $coupon = Coupon::create(['code' => 'RESTORE1', 'type' => 'fixed', 'value' => 10]);
        $coupon->delete();

        $response = $this->patchJson($this->baseUrl.'/'.$coupon->id.'/restore');

        $response->assertStatus(200);
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'deleted_at' => null]);
    }

    public function test_force_delete_coupon_success()
    {
        $this->setupAdmin();
        $coupon = Coupon::create(['code' => 'FORCE1', 'type' => 'fixed', 'value' => 10]);
        $coupon->delete();

        $response = $this->deleteJson($this->baseUrl.'/'.$coupon->id.'/force-delete');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    public function test_bulk_restore_coupons_success()
    {
        $this->setupAdmin();
        $c1 = Coupon::create(['code' => 'BR1', 'type' => 'fixed', 'value' => 10]);
        $c2 = Coupon::create(['code' => 'BR2', 'type' => 'fixed', 'value' => 10]);
        $c1->delete();
        $c2->delete();

        $response = $this->patchJson($this->baseUrl.'/bulk-restore', [
            'ids' => [$c1->id, $c2->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('coupons', ['id' => $c1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('coupons', ['id' => $c2->id, 'deleted_at' => null]);
    }
}
