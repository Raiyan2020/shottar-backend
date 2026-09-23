<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'title',
        'title_en',
        'body',
        'body_en',
        'type',
        'category',
        'data',
        'is_read',
    ];

    /** التصنيفات المتاحة لمحتوى الإشعار — دي اللي التبويبات في التطبيق بتفلتر عليها. */
    public const CATEGORIES = ['general', 'educational', 'achievement', 'transaction'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
