<?php

namespace Modules\Commission\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Commission\Models\CommissionSetting;

class CommissionSettingSeeder extends Seeder
{
    public function run(): void
    {
        CommissionSetting::firstOrCreate([], ['teacher_rate' => 70.00]);
    }
}
