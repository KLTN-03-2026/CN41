<?php

namespace Modules\Commission\Models;

use Illuminate\Database\Eloquent\Model;

class AdminOtpToken extends Model
{
    protected $table = 'admin_otp_tokens';

    protected $fillable = ['user_id', 'otp', 'purpose', 'new_email', 'expires_at', 'used'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];
}
