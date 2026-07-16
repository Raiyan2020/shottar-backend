<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subscription = $this->resolveSubscription();

        return [

            'id' => $this->id,
            'name' => $this->name,
            'phone'=> $this->phone,
            'device_type' => $this->device_type,
            'status' => $this->status == 1 ? 'active' : ($this->status == '2' ? 'pending activation':'inactive') ,
            'image' => $this->image ? asset($this->image) : null,
            'country_code' => $this->country_code,
            'phone_not_code' => $this->phone_not_code,
            'language' => $this->language ??'ar',
            'notification_enabled' => $this->notification_enabled == 1 ? true : false,
            //grade
            'grade' => $this->grade_id,
            'semester' => $this->semester_id,
            'points' => (int) ($this->points ?? 0),
            'streak' => (int) ($this->streak ?? 0),
            'daily_goal' => (int) ($this->daily_goal ?? 0),
            'daily_goal_done' => (int) ($this->daily_goal_done ?? 0),
            'package_name' => $subscription['package_name'],
            'subscription_end_date' => $subscription['subscription_end_date'],
            'last_result' => null,

        ];
    }

    /**
     * يشتق الباقة الحالية وتاريخ الانتهاء من آخر طلب مدفوع للمستخدم.
     *
     * @return array{package_name: ?string, subscription_end_date: ?string}
     */
    protected function resolveSubscription(): array
    {
        $default = ['package_name' => null, 'subscription_end_date' => null];

        if (! $this->resource || ! method_exists($this->resource, 'orders')) {
            return $default;
        }

        $order = $this->orders()
            ->where('status', 'paid')
            ->latest('id')
            ->first();

        if (! $order) {
            return $default;
        }

        $lang = request()->header('lang', $this->language ?? 'ar');

        if ($order->is_all_materials) {
            $packageName = $lang === 'en' ? 'Full package' : 'الباقة الشاملة';
        } else {
            $count = $order->items()->count();
            $packageName = $lang === 'en'
                ? ($count > 0 ? "{$count} subjects" : 'Subjects package')
                : ($count > 0 ? "{$count} مواد" : 'باقة مواد');
        }

        return [
            'package_name' => $packageName,
            'subscription_end_date' => $order->expires_at
                ? \Illuminate\Support\Carbon::parse($order->expires_at)->toDateString()
                : null,
        ];
    }
}
