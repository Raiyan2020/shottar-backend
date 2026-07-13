<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable ;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'image',
        'password',
        'address',
        'status',
        'activation_code',
        'resend_code_count',
        'device_token',
        'device_type',
        'remember_token',
        'country_code',
        'phone_not_code',
        'language',
        'notification_enabled',
        'grade_id',
        'semester_id',
        ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'activation_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */


    public static $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'phone' => 'required|max:191',
        'email' => 'nullable|email|max:255|unique:users,email',
        'password' => 'required|string|min:6|confirmed',
//        'country_code' => 'required|string|max:5',

    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    //orders
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    //notifications
//    public function notifications()
//    {
//        return $this->hasMany(Notification::class);
//    }

}
