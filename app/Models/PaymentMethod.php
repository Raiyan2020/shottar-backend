<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    public const SLUG_CASH = 'cash';

    public const SLUG_APPLE_IAP = 'apple_iap';

    /** @var list<string> Slugs handled inside the app (no MyFatoorah redirect). */
    public const OFFLINE_SLUGS = [
        self::SLUG_CASH,
    ];

    /** @var list<string> Seeded / system payment methods — not deletable from admin. */
    public const SYSTEM_SLUGS = [
        self::SLUG_CASH,
        self::SLUG_APPLE_IAP,
        'apple-pay',
        'knet',
        'visa',
    ];

    protected $fillable = [
        'name_ar',
        'name_en',
        'image',
        'slug',
        'status',
    ];

    /** MyFatoorah ExecutePayment method IDs (offline / Apple IAP excluded). */
    const ALL_METHODS = [
        'knet' => 1,
        'apple_iap' => 'apple_iap',
        'apple-pay' => 6,
        'Google Pay' => 32,
        'Visa' => 2,
        'visa' => 2,
        'Stc Pay' => 14,
        'Benefit' => 5,
    ];

    public static function isOffline(?string $slug): bool
    {
        return in_array($slug, self::OFFLINE_SLUGS, true);
    }

    public static function isDeletable(?string $slug): bool
    {
        return $slug !== null && $slug !== '' && ! in_array($slug, self::SYSTEM_SLUGS, true);
    }

    public function canBeDeleted(): bool
    {
        return self::isDeletable($this->slug);
    }

    public function canBeEdited(): bool
    {
        return self::isDeletable($this->slug);
    }

    public static function usesMyFatoorah(?string $slug): bool
    {
        if ($slug === null || $slug === '') {
            return false;
        }

        if (self::isOffline($slug) || $slug === self::SLUG_APPLE_IAP) {
            return false;
        }

        return array_key_exists($slug, self::ALL_METHODS);
    }
}
