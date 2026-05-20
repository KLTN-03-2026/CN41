<?php

namespace Modules\Commission\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    protected $table = 'commission_settings';

    protected $fillable = ['teacher_rate'];

    protected $casts = ['teacher_rate' => 'decimal:2'];

    public static function current(): self
    {
        return static::firstOrCreate([], ['teacher_rate' => 70.00]);
    }
}
