<?php

namespace Modules\Commission\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Commission\Models\TeacherEarning;
use Modules\Commission\Models\TeacherPayout;
use Modules\Teachers\Models\Teachers;

class CommissionDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Xóa dữ liệu demo cũ
        TeacherEarning::truncate();
        TeacherPayout::truncate();

        // Cập nhật thông tin ngân hàng cho các giảng viên
        $bankData = [
            1 => ['bank_name' => 'Vietcombank', 'bank_account_number' => '1234567890', 'bank_account_name' => 'NGUYEN VAN AN'],
            2 => ['bank_name' => 'Techcombank', 'bank_account_number' => '9876543210', 'bank_account_name' => 'TRAN THI BINH'],
            3 => ['bank_name' => 'BIDV',        'bank_account_number' => '1122334455', 'bank_account_name' => 'LE MINH CUONG'],
            4 => ['bank_name' => 'MB Bank',     'bank_account_number' => '5566778899', 'bank_account_name' => 'PHAM HONG DUC'],
            7 => ['bank_name' => 'ACB',         'bank_account_number' => '6677889900', 'bank_account_name' => 'DANG THI GIANG'],
        ];
        foreach ($bankData as $id => $info) {
            Teachers::where('id', $id)->update($info);
        }

        $now = Carbon::now();

        // ──────────────────────────────────────────────────────────
        // Teacher 1 — Nguyễn Văn An (top earner: Laravel, Vue, React)
        // ──────────────────────────────────────────────────────────
        $this->credits(1, [
            // Tháng 3
            ['amount' => 560000,  'desc' => 'Hoa hồng từ khóa học: Laravel 12 Từ Cơ Bản Đến Nâng Cao', 'days_ago' => 85],
            ['amount' => 420000,  'desc' => 'Hoa hồng từ khóa học: Vue.js 3 & Pinia Thực Chiến',        'days_ago' => 83],
            ['amount' => 560000,  'desc' => 'Hoa hồng từ khóa học: Laravel 12 Từ Cơ Bản Đến Nâng Cao', 'days_ago' => 80],
            ['amount' => 280000,  'desc' => 'Hoa hồng từ khóa học: HTML, CSS & JavaScript Cho Người Mới','days_ago' => 78],
            ['amount' => 420000,  'desc' => 'Hoa hồng từ khóa học: React.js & Next.js Full-Stack',       'days_ago' => 76],
            ['amount' => 560000,  'desc' => 'Hoa hồng từ khóa học: Laravel 12 Từ Cơ Bản Đến Nâng Cao', 'days_ago' => 73],
            ['amount' => 336000,  'desc' => 'Hoa hồng từ khóa học: Node.js & Express REST API',          'days_ago' => 71],
            ['amount' => 420000,  'desc' => 'Hoa hồng từ khóa học: Vue.js 3 & Pinia Thực Chiến',        'days_ago' => 68],
            // Tháng 4
            ['amount' => 560000,  'desc' => 'Hoa hồng từ khóa học: Laravel 12 Từ Cơ Bản Đến Nâng Cao', 'days_ago' => 60],
            ['amount' => 420000,  'desc' => 'Hoa hồng từ khóa học: React.js & Next.js Full-Stack',       'days_ago' => 58],
            ['amount' => 280000,  'desc' => 'Hoa hồng từ khóa học: TypeScript Từ Cơ Bản Đến Nâng Cao',  'days_ago' => 55],
            ['amount' => 560000,  'desc' => 'Hoa hồng từ khóa học: Laravel 12 Từ Cơ Bản Đến Nâng Cao', 'days_ago' => 52],
            ['amount' => 420000,  'desc' => 'Hoa hồng từ khóa học: Vue.js 3 & Pinia Thực Chiến',        'days_ago' => 49],
            ['amount' => 336000,  'desc' => 'Hoa hồng từ khóa học: Node.js & Express REST API',          'days_ago' => 46],
            ['amount' => 560000,  'desc' => 'Hoa hồng từ khóa học: Laravel 12 Từ Cơ Bản Đến Nâng Cao', 'days_ago' => 43],
            ['amount' => 280000,  'desc' => 'Hoa hồng từ khóa học: HTML, CSS & JavaScript Cho Người Mới','days_ago' => 40],
            ['amount' => 420000,  'desc' => 'Hoa hồng từ khóa học: React.js & Next.js Full-Stack',       'days_ago' => 37],
            ['amount' => 560000,  'desc' => 'Hoa hồng từ khóa học: Laravel 12 Từ Cơ Bản Đến Nâng Cao', 'days_ago' => 35],
            // Tháng 5
            ['amount' => 420000,  'desc' => 'Hoa hồng từ khóa học: Vue.js 3 & Pinia Thực Chiến',        'days_ago' => 28],
            ['amount' => 560000,  'desc' => 'Hoa hồng từ khóa học: Laravel 12 Từ Cơ Bản Đến Nâng Cao', 'days_ago' => 24],
            ['amount' => 336000,  'desc' => 'Hoa hồng từ khóa học: Node.js & Express REST API',          'days_ago' => 21],
            ['amount' => 420000,  'desc' => 'Hoa hồng từ khóa học: React.js & Next.js Full-Stack',       'days_ago' => 18],
            ['amount' => 560000,  'desc' => 'Hoa hồng từ khóa học: Laravel 12 Từ Cơ Bản Đến Nâng Cao', 'days_ago' => 14],
            ['amount' => 280000,  'desc' => 'Hoa hồng từ khóa học: TypeScript Từ Cơ Bản Đến Nâng Cao',  'days_ago' => 10],
            ['amount' => 420000,  'desc' => 'Hoa hồng từ khóa học: Vue.js 3 & Pinia Thực Chiến',        'days_ago' => 7],
            ['amount' => 560000,  'desc' => 'Hoa hồng từ khóa học: Laravel 12 Từ Cơ Bản Đến Nâng Cao', 'days_ago' => 4],
            ['amount' => 336000,  'desc' => 'Hoa hồng từ khóa học: Node.js & Express REST API',          'days_ago' => 2],
        ]);
        // 1 refund nhỏ
        $this->debit(1, 420000, 'Hoàn tiền đơn hàng bị khiếu nại: Vue.js 3 & Pinia Thực Chiến', 30);

        // Payouts teacher 1
        TeacherPayout::create([
            'teacher_id'   => 1, 'amount' => 5000000, 'status' => 'paid',
            'teacher_note' => 'Rút tiền tháng 3', 'admin_note' => 'Đã chuyển khoản VCB',
            'processed_at' => $now->copy()->subDays(70),
            'created_at'   => $now->copy()->subDays(75),
            'updated_at'   => $now->copy()->subDays(70),
        ]);
        TeacherPayout::create([
            'teacher_id'   => 1, 'amount' => 4000000, 'status' => 'paid',
            'teacher_note' => 'Rút tiền tháng 4', 'admin_note' => 'Đã chuyển khoản VCB',
            'processed_at' => $now->copy()->subDays(28),
            'created_at'   => $now->copy()->subDays(33),
            'updated_at'   => $now->copy()->subDays(28),
        ]);
        TeacherPayout::create([
            'teacher_id'   => 1, 'amount' => 3000000, 'status' => 'approved',
            'teacher_note' => 'Rút tiền tháng 5', 'admin_note' => 'Đã duyệt, chờ chuyển khoản',
            'processed_at' => $now->copy()->subDays(3),
            'created_at'   => $now->copy()->subDays(5),
            'updated_at'   => $now->copy()->subDays(3),
        ]);
        TeacherPayout::create([
            'teacher_id'   => 1, 'amount' => 2000000, 'status' => 'pending',
            'teacher_note' => null, 'admin_note' => null,
            'processed_at' => null,
            'created_at'   => $now->copy()->subDays(1),
            'updated_at'   => $now->copy()->subDays(1),
        ]);

        // ──────────────────────────────────────────────────────────
        // Teacher 2 — Trần Thị Bình
        // ──────────────────────────────────────────────────────────
        $this->credits(2, [
            ['amount' => 240000, 'desc' => 'Hoa hồng từ khóa học: Spring Boot & Java Backend', 'days_ago' => 72],
            ['amount' => 240000, 'desc' => 'Hoa hồng từ khóa học: Spring Boot & Java Backend', 'days_ago' => 60],
            ['amount' => 192000, 'desc' => 'Hoa hồng từ khóa học: MySQL Nâng Cao & Tối Ưu Query', 'days_ago' => 50],
            ['amount' => 240000, 'desc' => 'Hoa hồng từ khóa học: Spring Boot & Java Backend', 'days_ago' => 40],
            ['amount' => 192000, 'desc' => 'Hoa hồng từ khóa học: MySQL Nâng Cao & Tối Ưu Query', 'days_ago' => 30],
            ['amount' => 240000, 'desc' => 'Hoa hồng từ khóa học: Spring Boot & Java Backend', 'days_ago' => 18],
            ['amount' => 192000, 'desc' => 'Hoa hồng từ khóa học: Git & GitHub Cho Lập Trình Viên', 'days_ago' => 9],
            ['amount' => 240000, 'desc' => 'Hoa hồng từ khóa học: Spring Boot & Java Backend', 'days_ago' => 3],
        ]);

        TeacherPayout::create([
            'teacher_id'   => 2, 'amount' => 1500000, 'status' => 'paid',
            'teacher_note' => 'Rút tiền tháng 4', 'admin_note' => 'Đã chuyển khoản',
            'processed_at' => $now->copy()->subDays(35),
            'created_at'   => $now->copy()->subDays(38),
            'updated_at'   => $now->copy()->subDays(35),
        ]);
        TeacherPayout::create([
            'teacher_id'   => 2, 'amount' => 800000, 'status' => 'pending',
            'teacher_note' => 'Rút tháng 5', 'admin_note' => null,
            'processed_at' => null,
            'created_at'   => $now->copy()->subDays(2),
            'updated_at'   => $now->copy()->subDays(2),
        ]);

        // ──────────────────────────────────────────────────────────
        // Teacher 3 — Lê Minh Cường
        // ──────────────────────────────────────────────────────────
        $this->credits(3, [
            ['amount' => 168000, 'desc' => 'Hoa hồng từ khóa học: API Design & RESTful Best Practices', 'days_ago' => 55],
            ['amount' => 168000, 'desc' => 'Hoa hồng từ khóa học: API Design & RESTful Best Practices', 'days_ago' => 38],
            ['amount' => 168000, 'desc' => 'Hoa hồng từ khóa học: API Design & RESTful Best Practices', 'days_ago' => 20],
            ['amount' => 168000, 'desc' => 'Hoa hồng từ khóa học: API Design & RESTful Best Practices', 'days_ago' => 6],
        ]);

        TeacherPayout::create([
            'teacher_id'   => 3, 'amount' => 500000, 'status' => 'rejected',
            'teacher_note' => 'Rút tiền', 'admin_note' => 'Thông tin ngân hàng chưa cập nhật, vui lòng bổ sung',
            'processed_at' => $now->copy()->subDays(25),
            'created_at'   => $now->copy()->subDays(28),
            'updated_at'   => $now->copy()->subDays(25),
        ]);

        // ──────────────────────────────────────────────────────────
        // Teacher 7 — Đặng Thị Giang
        // ──────────────────────────────────────────────────────────
        $this->credits(7, [
            ['amount' => 192000, 'desc' => 'Hoa hồng từ khóa học: MySQL Nâng Cao & Tối Ưu Query', 'days_ago' => 45],
            ['amount' => 192000, 'desc' => 'Hoa hồng từ khóa học: MySQL Nâng Cao & Tối Ưu Query', 'days_ago' => 22],
            ['amount' => 192000, 'desc' => 'Hoa hồng từ khóa học: MySQL Nâng Cao & Tối Ưu Query', 'days_ago' => 8],
        ]);
    }

    private function credits(int $teacherId, array $entries): void
    {
        foreach ($entries as $e) {
            TeacherEarning::create([
                'teacher_id'      => $teacherId,
                'order_item_id'   => null,
                'type'            => 'credit',
                'amount'          => $e['amount'],
                'commission_rate' => 80.00,
                'description'     => $e['desc'],
                'created_at'      => Carbon::now()->subDays($e['days_ago']),
                'updated_at'      => Carbon::now()->subDays($e['days_ago']),
            ]);
        }
    }

    private function debit(int $teacherId, float $amount, string $desc, int $daysAgo): void
    {
        TeacherEarning::create([
            'teacher_id'      => $teacherId,
            'order_item_id'   => null,
            'type'            => 'debit',
            'amount'          => $amount,
            'commission_rate' => 80.00,
            'description'     => $desc,
            'created_at'      => Carbon::now()->subDays($daysAgo),
            'updated_at'      => Carbon::now()->subDays($daysAgo),
        ]);
    }
}
