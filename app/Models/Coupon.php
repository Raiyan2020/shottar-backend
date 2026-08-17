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

    public function setStartsAtAttribute($value): void
    {
        $this->attributes['starts_at'] = $value
            ? \Illuminate\Support\Carbon::parse($value)->startOfDay()->format('Y-m-d H:i:s')
            : null;
    }

    public function setExpiresAtAttribute($value): void
    {
        $this->attributes['expires_at'] = $value
            ? \Illuminate\Support\Carbon::parse($value)->endOfDay()->format('Y-m-d H:i:s')
            : null;
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return now()->startOfDay()->gt($this->expires_at->copy()->startOfDay());
    }

    public function isNotYetActive(): bool
    {
        if (!$this->starts_at) {
            return false;
        }

        return now()->startOfDay()->lt($this->starts_at->copy()->startOfDay());
    }
}
