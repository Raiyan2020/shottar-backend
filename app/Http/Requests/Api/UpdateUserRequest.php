<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * §1 + §2 + §7
     *
     * - بيقبل `grade_id` و `semester_id` ويحفظهم (اختياريين).
     * - بيقبل JSON و multipart/form-data (Laravel بيتعامل مع الاتنين تلقائيًا).
     * - **بيتجاهل `phone` و `country_code`** تمامًا: تغيير الرقم بقى عبر
     *   `/profile/phone/request-change` + `/profile/phone/confirm-change` بتحقّق OTP،
     *   عشان غلطة كتابة متبوّظش هوية الحساب وتقفل الدخول للأبد.
     *   التطبيق يقدر يفضل يبعتهم عادي — مش هيحصل بيهم أي حاجة.
     */
    public function rules()
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:102400'], // 100MB max
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],

            // مقبولة للتوافق مع النسخ القديمة من التطبيق لكن **مش بتتحفظ**
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'country_code' => ['sometimes', 'nullable', 'string', 'max:5'],
        ];
    }

    /**
     * الحقول اللي فعلًا بتتحفظ على المستخدم.
     */
    public function profileData(): array
    {
        return collect($this->validated())
            ->only(['name', 'semester_id', 'grade_id'])
            ->reject(fn ($value, $key) => $value === null && ! $this->exists($key))
            ->all();
    }

    /**
     * هل التطبيق حاول يغيّر الرقم من غير تحقّق؟ (بنرجّعله تنويه في الرد)
     */
    public function attemptedPhoneChange(): bool
    {
        $user = Auth::user();

        if (! $user || ! $this->filled('phone')) {
            return false;
        }

        $incoming = preg_replace('/\D+/', '', (string) $this->input('country_code') . $this->input('phone'));
        $current = preg_replace('/\D+/', '', (string) $user->phone);

        return $incoming !== '' && $incoming !== $current;
    }
}
