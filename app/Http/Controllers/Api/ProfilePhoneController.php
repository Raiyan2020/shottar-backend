<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Functions;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * §2 — تغيير رقم الجوال بتحقّق OTP.
 *
 * رقم الجوال هو هوية الدخول (مفيش باسورد)، فتغييره من غير تحقّق معناه إن غلطة
 * كتابة واحدة بتقفل الحساب للأبد. المسار بقى خطوتين:
 *
 *   1) POST /profile/phone/request-change  → بيبعت OTP على الرقم الجديد، وميغيّرش حاجة.
 *   2) POST /profile/phone/confirm-change  → الكود الصح بس هو اللي بينقل الحساب للرقم الجديد.
 */
class ProfilePhoneController extends Controller
{
    use Functions;

    public function requestChange(Request $request)
    {
        $lang = $request->header('lang') === 'en' ? 'en' : 'ar';
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'max:5'],
        ]);

        if ($validator->fails()) {
            return sendError($validator->errors()->first(), [], 422);
        }

        $countryCode = trim((string) $request->input('country_code'));
        $phoneNotCode = trim((string) $request->input('phone'));
        // نفس تركيب /login و /register بالحرف: concat خام من غير أي تنسيق،
        // عشان المستخدم يقدر يسجّل دخول بالرقم الجديد بعدها.
        $fullPhone = $countryCode . $phoneNotCode;

        if (preg_replace('/\D+/', '', $phoneNotCode) === '') {
            return sendError($lang === 'ar' ? 'رقم الجوال غير صالح.' : 'Invalid phone number.', [], 422);
        }

        if ($this->sameNumber($fullPhone, (string) $user->phone)) {
            return sendError(
                $lang === 'ar' ? 'هذا هو رقمك الحالي بالفعل.' : 'This is already your current number.',
                [],
                422
            );
        }

        // الرقم مستخدم في حساب تاني؟ نرفض قبل إرسال أي كود.
        if ($this->phoneTakenByAnother($fullPhone, $user->id)) {
            return sendError(
                $lang === 'ar' ? 'رقم الجوال مستخدم في حساب آخر.' : 'This phone number is already used by another account.',
                [],
                422
            );
        }

        $code = generate_activation_code($fullPhone);
        $ttlMinutes = max(1, (int) config('services.otp.phone_change_ttl', 10));

        // مفيش أي تعديل على `phone` الحقيقي — الطلب بيتخزن في حقول pending لوحدها.
        $user->forceFill([
            'pending_phone' => $phoneNotCode,
            'pending_country_code' => $countryCode,
            'pending_phone_code' => (string) $code,
            'pending_phone_expires_at' => now()->addMinutes($ttlMinutes),
            'pending_phone_attempts' => 0,
        ])->save();

        if (! uses_fixed_otp($fullPhone)) {
            try {
                $send = $this->sendVerificationCode($fullPhone, $code, true);
                $payload = $send->json();

                if (! $send->successful() || (is_array($payload) && ($payload['status'] ?? true) === false)) {
                    Log::error('Shottar phone-change OTP WhatsApp send failed', [
                        'user_id' => $user->id,
                        'phone_suffix' => substr($fullPhone, -4),
                        'provider_status' => $send->status(),
                    ]);

                    return sendError(
                        $lang === 'ar'
                            ? 'تعذر إرسال رمز التحقق عبر واتساب، يرجى المحاولة مرة أخرى'
                            : 'Could not send the verification code over WhatsApp, please try again'
                    );
                }
            } catch (\Throwable $exception) {
                Log::error('Shottar phone-change OTP WhatsApp exception', [
                    'user_id' => $user->id,
                    'phone_suffix' => substr($fullPhone, -4),
                    'exception' => $exception->getMessage(),
                ]);

                return sendError(
                    $lang === 'ar'
                        ? 'تعذر إرسال رمز التحقق عبر واتساب، يرجى المحاولة مرة أخرى'
                        : 'Could not send the verification code over WhatsApp, please try again'
                );
            }
        }

        return sendResponse([
            'phone' => $phoneNotCode,
            'country_code' => $countryCode,
            'expires_at' => $user->pending_phone_expires_at->toIso8601String(),
            'expires_in' => $ttlMinutes * 60,
            'resend_after' => 60,
        ], $lang === 'ar'
            ? 'تم إرسال رمز التحقق إلى رقم الجوال الجديد.'
            : 'A verification code has been sent to the new phone number.');
    }

    public function confirmChange(Request $request)
    {
        $lang = $request->header('lang') === 'en' ? 'en' : 'ar';
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'max:5'],
            'activation_code' => ['required', 'numeric', 'digits:4'],
        ]);

        if ($validator->fails()) {
            return sendError($validator->errors()->first(), [], 422);
        }

        $countryCode = trim((string) $request->input('country_code'));
        $phoneNotCode = trim((string) $request->input('phone'));
        $fullPhone = $countryCode . $phoneNotCode;

        if (! $user->pending_phone || ! $user->pending_phone_code) {
            return sendError(
                $lang === 'ar' ? 'لا يوجد طلب تغيير رقم. ابدأ من جديد.' : 'No pending phone change request. Please start again.',
                [],
                422
            );
        }

        if ($user->pending_phone_expires_at && $user->pending_phone_expires_at->isPast()) {
            $this->clearPending($user);

            return sendError(
                $lang === 'ar' ? 'انتهت صلاحية رمز التحقق. اطلب رمزًا جديدًا.' : 'The verification code has expired. Please request a new one.',
                [],
                422
            );
        }

        // الرقم المبعوت لازم يكون هو نفس الرقم اللي اتطلب له الكود.
        if ($phoneNotCode !== (string) $user->pending_phone
            || $countryCode !== (string) $user->pending_country_code) {
            return sendError(
                $lang === 'ar' ? 'رقم الجوال لا يطابق الطلب الحالي.' : 'The phone number does not match the pending request.',
                [],
                422
            );
        }

        $maxAttempts = max(1, (int) config('services.otp.phone_change_max_attempts', 5));

        if ($user->pending_phone_attempts >= $maxAttempts) {
            $this->clearPending($user);

            return sendError(
                $lang === 'ar' ? 'تم تجاوز عدد المحاولات المسموح. اطلب رمزًا جديدًا.' : 'Too many attempts. Please request a new code.',
                [],
                429
            );
        }

        if (! hash_equals((string) $user->pending_phone_code, (string) $request->input('activation_code'))) {
            $user->forceFill(['pending_phone_attempts' => $user->pending_phone_attempts + 1])->save();

            return sendError(
                $lang === 'ar' ? 'رمز التحقق غير صحيح' : 'Invalid verification code',
                ['attempts_left' => max(0, $maxAttempts - $user->pending_phone_attempts)],
                422
            );
        }

        // آخر تحقّق قبل الحفظ — يمكن حد تاني سجّل بالرقم في الوقت ده.
        if ($this->phoneTakenByAnother($fullPhone, $user->id)) {
            $this->clearPending($user);

            return sendError(
                $lang === 'ar' ? 'رقم الجوال مستخدم في حساب آخر.' : 'This phone number is already used by another account.',
                [],
                422
            );
        }

        $oldPhone = $user->phone;

        $user->forceFill([
            'phone' => $fullPhone,
            'country_code' => $countryCode,
            'phone_not_code' => $phoneNotCode,
            'pending_phone' => null,
            'pending_country_code' => null,
            'pending_phone_code' => null,
            'pending_phone_expires_at' => null,
            'pending_phone_attempts' => 0,
        ])->save();

        Log::info('Shottar phone changed', [
            'user_id' => $user->id,
            'old_suffix' => substr((string) $oldPhone, -4),
            'new_suffix' => substr($fullPhone, -4),
        ]);

        // التوكن القديم مربوط بالهوية القديمة — نلغي كل الجلسات ونصدر توكن جديد.
        $user->tokens()->delete();
        $token = $user->createToken('authToken')->plainTextToken;

        return sendResponse([
            'user' => new UserResource($user->fresh()),
            'token' => $token,
        ], $lang === 'ar' ? 'تم تغيير رقم الجوال بنجاح.' : 'Phone number updated successfully.');
    }

    protected function clearPending(User $user): void
    {
        $user->forceFill([
            'pending_phone' => null,
            'pending_country_code' => null,
            'pending_phone_code' => null,
            'pending_phone_expires_at' => null,
            'pending_phone_attempts' => 0,
        ])->save();
    }

    /**
     * الرقم بيتخزن خام (زي ما التطبيق بعته) فممكن يكون `+96550…` أو `96550…`.
     * المقارنة بتبقى على الأرقام بس عشان منعتبرش الشكلين رقمين مختلفين.
     */
    protected function sameNumber(?string $a, ?string $b): bool
    {
        $a = preg_replace('/\D+/', '', (string) $a);
        $b = preg_replace('/\D+/', '', (string) $b);

        return $a !== '' && $a === $b;
    }

    /**
     * هل الرقم محجوز لحساب تاني؟ بنفحص كل الأشكال الواردة للتخزين.
     */
    protected function phoneTakenByAnother(string $fullPhone, int $userId): bool
    {
        $digits = preg_replace('/\D+/', '', $fullPhone);

        $candidates = array_unique(array_filter([
            $fullPhone,
            $digits,
            $digits !== '' ? '+' . $digits : null,
        ]));

        return User::whereIn('phone', $candidates)
            ->where('id', '!=', $userId)
            ->exists();
    }
}
