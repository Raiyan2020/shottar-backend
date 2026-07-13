<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'status'    => 'boolean',
    ];

    public function isExpired(): bool
    {
        $now = now();

        if ($this->expires_at && $this->expires_at->lt($now)) {
            return true;
        }

        return false;
    }
}
