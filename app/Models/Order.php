<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total',
        'status',
        'payment_method_id',
        'payment_reference',
        'apple_transaction_id',
        'apple_original_transaction_id',
        'apple_product_id',
        'apple_environment',
        'is_all_materials',
        'expires_at',
        'discount_amount',
        'discount',
        'coupon_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_all_materials' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function packageName(?string $lang = null): string
    {
        $lang = $lang ?: app()->getLocale();

        if ($this->is_all_materials) {
            return $lang === 'en' ? 'Full package' : 'الباقة الشاملة';
        }

        $count = $this->relationLoaded('items')
            ? $this->items->count()
            : $this->items()->count();

        if ($lang === 'en') {
            return $count > 0 ? "{$count} subjects" : 'Subjects package';
        }

        return $count > 0 ? "{$count} مواد" : 'باقة مواد';
    }

    public function subjectsLabel(?string $lang = null): string
    {
        $lang = $lang ?: app()->getLocale();
        $nameField = $lang === 'en' ? 'name_en' : 'name_ar';

        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('subject')->get();

        $names = $items
            ->map(fn ($item) => $item->subject?->{$nameField} ?: $item->subject?->name_en)
            ->filter()
            ->unique()
            ->values();

        return $names->isNotEmpty() ? $names->implode(', ') : '-';
    }
}
