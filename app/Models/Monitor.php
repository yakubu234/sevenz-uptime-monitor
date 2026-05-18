<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monitor extends Model
{
    protected $fillable = [
        'url',
        'check_interval',
        'threshold',
        'status',
        'last_checked_at',
        'consecutive_failures',
        'last_status_change_at',
    ];

    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
            'last_status_change_at' => 'datetime',
        ];
    }

    public function checks(): HasMany
    {
        return $this->hasMany(MonitorCheck::class);
    }
}
