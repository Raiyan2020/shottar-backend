<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ImageTrait;

    public function show($user_id = null)
    {
        if ($user_id) {
            $user = User::findOrFail($user_id);
        } else {
            $user = Auth::user();
        }
        return  sendResponse( new UserResource($user));
    }

    /**
     * §1 / §2 / §3 / §7
     *
     * - بيحفظ `grade_id` و `semester_id` فعلًا (اختياريين).
     * - **مش بيغيّر رقم الجوال**: الرقم بيتغيّر بس عبر /profile/phone/request-change
     *   ثم /profile/phone/confirm-change بتحقّق OTP.
     * - بيرجّع الـ user من الداتابيز بعد الحفظ عشان `grade`/`semester` يرجعوا
     *   int مضبوطة، مش القيمة الخام اللي جاية من الطلب.
     * - بيقبل JSON و multipart/form-data.
     */
    public function update(UpdateUserRequest $request)
    {
        $user = Auth::user();
        $data = $request->profileData();

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadImage('admin', $request->file('image'));
        }

        if (! empty($data)) {
            $user->update($data);
        }

        // refresh ضروري: بعد update الموديل بيكون شايل القيم الخام (string)
        // اللي جاية من الطلب، فـ grade/semester كانوا بيرجعوا string.
        $user->refresh();

        $lang = $request->header('lang') === 'en' ? 'en' : 'ar';
        $message = $lang === 'ar' ? 'تم تحديث الملف الشخصي بنجاح.' : 'Profile updated successfully.';

        // تنويه للتطبيق إن الرقم اللي بعته اتجاهل ولازم يمشي على مسار التحقّق.
        // شكل `data` بيفضل هو هو (كائن الـ user) عشان مفيش breaking change —
        // التنويه بيتحط في `message` بس.
        if ($request->attemptedPhoneChange()) {
            $message = $lang === 'ar'
                ? 'تم تحديث الملف الشخصي. رقم الجوال لم يتغيّر — استخدم /profile/phone/request-change ثم /profile/phone/confirm-change لتغييره.'
                : 'Profile updated. The phone number was not changed — use /profile/phone/request-change then /profile/phone/confirm-change.';
        }

        return sendResponse(new UserResource($user), $message);
    }

    //changePassword
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'old_password' => ['required'],
            'new_password' => ['required', 'min:6', 'confirmed'], // تستخدم new_password_confirmation تلقائياً
        ]);

        if ($validator->fails()) {
            return sendError($validator->errors()->first());
        }

        if (!Hash::check($request->old_password, $user->password)) {
            return sendError('Old password is incorrect');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return sendResponse(new UserResource($user), 'Password changed successfully.');
    }
    //updateUserSettings
    public function updateUserSettings(Request $request)
    {
        $user = Auth::user();
        $data = $request->only(['notification_enabled', 'language']);

        // Validate the data
        $validator = Validator::make($data, [
            'notification_enabled' => 'boolean',
            'language' => 'string|in:en,ar', // Assuming you support English and Arabic
        ]);

        if ($validator->fails()) {
            return sendError($validator->errors()->first());
        }

        // Update user settings
        $user->update($data);

        return sendResponse(new UserResource($user), 'User settings updated successfully.');
    }
    //destroy
    public function destroy()
    {
        return $this->deleteAccount();
    }

    // حذف الحساب نهائياً (يُلغي كل التوكنات ثم يحذف المستخدم)
    public function deleteAccount()
    {
        $user = Auth::user();

        if (! $user) {
            return sendError('User not found.', [], 404);
        }

        $user->tokens()->delete();
        $user->delete();

        return sendResponse(['message' => 'Account deleted successfully.'], 'Account deleted successfully.');
    }

    //notificationSwitch
    public function notificationSwitch(Request $request)
    {
        $user = Auth::user();
        $user->notification_switch = $request->status ?? true;
        $user->save();

        return sendResponse(new UserResource($user));
    }

}
