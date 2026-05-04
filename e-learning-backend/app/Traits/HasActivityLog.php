<?php

namespace App\Traits;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait HasActivityLog
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName(strtolower(class_basename($this)))
            ->logExcept($this->getSensitiveAttributes());
    }

    /**
     * Get attributes that should not be logged.
     */
    protected function getSensitiveAttributes(): array
    {
        return ['password', 'remember_token', 'token'];
    }
}
