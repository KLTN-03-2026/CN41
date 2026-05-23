<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Commission\Models\TeacherPayout;
use Modules\Course\Models\Course;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\OrderItem;
use Modules\Students\Models\Student;
use Modules\Teachers\Models\Teachers;
use Modules\Users\Database\Seeders\RolePermissionSeeder;
use Modules\Users\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class ExcelExportTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    private function makeTeacherAndCourse(): array
    {
        $teacher = Teachers::create(['name' => 'T', 'slug' => 'teacher-t', 'exp' => 1, 'status' => 1]);
        $course = Course::create([
            'teacher_id' => $teacher->id,
            'name' => 'PHP Course',
            'slug' => 'php-course',
            'price' => 100000,
            'status' => 1,
        ]);

        return [$teacher, $course];
    }

    private function makeOrderWithItem(int $courseId): Order
    {
        $student = Student::forceCreate(['name' => 'S', 'email' => uniqid().'@test.com', 'password' => 'pw']);
        $order = Order::create([
            'order_code' => 'ORD'.uniqid(),
            'student_id' => $student->id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'status' => 'paid',
            'payment_method' => 'vnpay',
            'paid_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'course_id' => $courseId,
            'price' => 100000,
            'sale_price' => 100000,
            'final_price' => 100000,
        ]);

        return $order;
    }

    public function test_admin_can_export_orders_as_excel(): void
    {
        Excel::fake();
        $this->setupAdmin();
        [, $course] = $this->makeTeacherAndCourse();
        $this->makeOrderWithItem($course->id);

        $from = now()->startOfMonth()->format('Y-m-d');
        $to   = now()->format('Y-m-d');

        $this->getJson("/api/v1/admin/orders/export?from={$from}&to={$to}")
            ->assertStatus(200);

        Excel::assertDownloaded("don-hang_{$from}_{$to}.xlsx");
    }

    public function test_admin_without_orders_export_permission_gets_403(): void
    {
        Excel::fake();

        // Seed all permissions and roles
        $this->seed(RolePermissionSeeder::class);

        // Create a regular admin user (not super-admin — super-admin bypasses all permission checks)
        $regularAdmin = User::forceCreate([
            'name'     => 'Regular Admin',
            'email'    => 'regular_admin@test.com',
            'password' => 'password123',
        ]);
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'admin')->first();
        // Sync only permissions that do NOT include orders.export
        $adminRole->revokePermissionTo('orders.export');
        $regularAdmin->assignRole($adminRole);

        $this->actingAs($regularAdmin, 'admin');

        $this->getJson('/api/v1/admin/orders/export')->assertStatus(403);
    }

    public function test_admin_can_export_payouts_as_excel(): void
    {
        Excel::fake();
        $this->setupAdmin();
        [$teacher] = $this->makeTeacherAndCourse();
        TeacherPayout::create([
            'teacher_id' => $teacher->id,
            'amount'     => 200000,
            'status'     => 'paid',
        ]);

        $from = now()->startOfMonth()->format('Y-m-d');
        $to   = now()->format('Y-m-d');

        $this->getJson("/api/v1/admin/payouts/export?from={$from}&to={$to}")
            ->assertStatus(200);

        Excel::assertDownloaded("rut-tien_{$from}_{$to}.xlsx");
    }

    public function test_admin_without_payouts_export_permission_gets_403(): void
    {
        Excel::fake();
        $this->seed(RolePermissionSeeder::class);
        $admin = User::forceCreate([
            'name'     => 'Admin No Export',
            'email'    => 'admin2@test.com',
            'password' => 'pw',
        ]);
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'admin')->first();
        $adminRole->revokePermissionTo('payouts.export');
        $admin->assignRole($adminRole);
        $this->actingAs($admin, 'admin');

        $this->getJson('/api/v1/admin/payouts/export')->assertStatus(403);
    }
}
