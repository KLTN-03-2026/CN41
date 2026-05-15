<?php

namespace Modules\Coupons\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CouponsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $coupons = [
            [
                'code' => 'NEWUSER10',
                'type' => 'percentage',
                'value' => 10,
                'min_order_value' => 200000,
                'max_discount' => 50000,
                'usage_limit' => null,
                'used_count' => 0,
                'start_date' => '2026-01-01 00:00:00',
                'end_date' => '2027-12-31 23:59:59',
                'status' => 1,
                'description' => 'Giảm 10% cho học viên mới (tối đa 50.000đ)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'FLASH50',
                'type' => 'fixed',
                'value' => 50000,
                'min_order_value' => 300000,
                'max_discount' => null,
                'usage_limit' => 100,
                'used_count' => 37,
                'start_date' => '2026-05-01 00:00:00',
                'end_date' => '2026-06-30 23:59:59',
                'status' => 1,
                'description' => 'Giảm ngay 50.000đ cho đơn từ 300.000đ',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUMMER30',
                'type' => 'percentage',
                'value' => 30,
                'min_order_value' => 500000,
                'max_discount' => 150000,
                'usage_limit' => 50,
                'used_count' => 12,
                'start_date' => '2026-06-01 00:00:00',
                'end_date' => '2026-07-31 23:59:59',
                'status' => 1,
                'description' => 'Khuyến mãi hè: giảm 30% (tối đa 150.000đ)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'TECH200',
                'type' => 'fixed',
                'value' => 200000,
                'min_order_value' => 800000,
                'max_discount' => null,
                'usage_limit' => 30,
                'used_count' => 8,
                'start_date' => '2026-04-01 00:00:00',
                'end_date' => '2026-05-31 23:59:59',
                'status' => 1,
                'description' => 'Giảm 200.000đ cho khóa học công nghệ từ 800.000đ',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'EXPIRED2025',
                'type' => 'percentage',
                'value' => 20,
                'min_order_value' => 100000,
                'max_discount' => null,
                'usage_limit' => null,
                'used_count' => 245,
                'start_date' => '2025-01-01 00:00:00',
                'end_date' => '2025-12-31 23:59:59',
                'status' => 0,
                'description' => 'Mã giảm giá năm 2025 (đã hết hạn)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'VIP500',
                'type' => 'fixed',
                'value' => 500000,
                'min_order_value' => 2000000,
                'max_discount' => null,
                'usage_limit' => 10,
                'used_count' => 2,
                'start_date' => '2026-01-01 00:00:00',
                'end_date' => '2026-12-31 23:59:59',
                'status' => 1,
                'description' => 'Ưu đãi VIP: giảm 500.000đ cho đơn từ 2.000.000đ',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($coupons as $coupon) {
            DB::table('coupons')->updateOrInsert(['code' => $coupon['code']], $coupon);
        }

        $this->command->info('CouponsDatabaseSeeder: Seeded '.count($coupons).' coupons.');
    }
}
