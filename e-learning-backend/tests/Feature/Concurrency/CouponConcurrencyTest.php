<?php

namespace Tests\Feature\Concurrency;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Coupons\Models\Coupon;
use Modules\Course\Models\Course;
use Modules\Students\Models\Student;
use Tests\TestCase;

class CouponConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/orders';

    protected function setupStudent()
    {
        $student = Student::forceCreate([
            'name' => 'Concurrency Student',
            'email' => 'concurrency@test.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        $this->actingAs($student, 'api');

        return $student;
    }

    public function test_coupon_usage_limit_is_strictly_enforced()
    {
        $student = $this->setupStudent();
        $teacher = DB::table('teachers')->insertGetId([
            'name' => 'Teacher Test',
            'slug' => 'teacher-test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Giả lập khóa học có giá 200k
        $course = Course::create([
            'name' => 'Concurrency Course',
            'slug' => 'concurrency-course',
            'price' => 200000,
            'teacher_id' => $teacher,
            'status' => 1,
            'published_at' => now(),
        ]);

        $coupon = Coupon::create([
            'code' => 'LIMIT1',
            'type' => 'fixed',
            'value' => 50000,
            'usage_limit' => 1,
            'status' => 1,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        // Request 1: Nên thành công
        $response1 = $this->postJson($this->baseUrl, [
            'course_ids' => [$course->id],
            'coupon_code' => 'LIMIT1',
        ]);

        $response1->assertStatus(201);
        $this->assertEquals(1, $coupon->fresh()->used_count);

        // Request 2: Nên thất bại vì đã hết lượt dùng
        $response2 = $this->postJson($this->baseUrl, [
            'course_ids' => [$course->id],
            'coupon_code' => 'LIMIT1',
        ]);

        $response2->assertStatus(422);
        $this->assertEquals(1, $coupon->fresh()->used_count);
    }
}
