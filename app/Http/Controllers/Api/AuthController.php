<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Functions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    use Functions;
    public function login(LoginRequest $request)
    {
        $user = User::Where('phone', $request->phone)->first();

        if (!$user) {
            $errMsg = $request->header('lang') == 'ar' ? "الرقم غير مسجل" : 'This phone number is not registered';
            return sendError($errMsg);
        }
        $activation_code = generate_activation_code($user->phone);

        $user->update([
            'device_type' => $request->device_type,
            'device_token' => $request->device_token,
            'status' => '2', // assuming status 2 means active
            'activation_code' => $activation_code, // set activation code
        ]);

        if (! uses_fixed_otp($user->phone)) {
            // إرسال كود التحقق إذا لزم الأمر
            try {
                $send = $this->sendVerificationCode($user->phone, $activation_code);
                $payload = $send->json();
                if (!$send->successful() || (is_array($payload) && ($payload['status'] ?? true) === false)) {
                    Log::error('Shottar login OTP WhatsApp send failed', [
                        'phone_suffix' => substr(preg_replace('/\D+/', '', $user->phone), -4),
                        'provider_status' => $send->status(),
                        'provider_code' => is_array($payload) ? ($payload['code'] ?? null) : null,
                    ]);
                    return sendError('تعذر إرسال رمز التفعيل عبر واتساب، يرجى المحاولة مرة أخرى');
                }
            } catch (\Throwable $exception) {
                Log::error('Shottar login OTP WhatsApp exception', [
                    'phone_suffix' => substr(preg_replace('/\D+/', '', $user->phone), -4),
                    'exception' => $exception->getMessage(),
                ]);
                return sendError('تعذر إرسال رمز التفعيل عبر واتساب، يرجى المحاولة مرة أخرى');
            }
        }

        $response = [
            'user' => new UserResource($user),
        ];

        return sendResponse($response, __('messages.login_success'));
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $data['password'] = $data['phone'];
        $data['status'] = '2';
        $data['activation_code'] = generate_activation_code($data['phone']);

        $user = User::create($data);

        $success['user'] = new UserResource($user);
//        $success['token'] = $user->createToken('MyAuthApp')->plainTextToken;

        if (! uses_fixed_otp($user->phone)) {
            $this->sendVerificationCode($user->phone, $user->activation_code);
        }

        return sendResponse( new UserResource($user), __('User registered successfully'));
    }

    public function activateAccount(Request $request)
    {
//        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'activation_code' => 'required|numeric|digits:4',
            'phone' => 'required|max:191',
            'country_code' => 'required|max:191',
        ]);
        $lang = $request->header('lang');
        $phone = $request->country_code . $request->phone;
        $user = User::where('phone',$phone)->first();
        if (!$user){
            return sendError('user not found');
        }
        if (empty($request->input('activation_code'))) {
            return sendError('activation_code_missing');
        }

        //check user inactive
        if ($user->status == '3') {
            return sendError('user inactive');
        }

        // check device serial
        if (empty($user->activation_code) || $user->status == '1') {
            return sendError('user already activated');

        }


        $activationCode = $request->input('activation_code');
        $code = intval($activationCode);
        if (!preg_match("/^[0-9]{4}$/", $code)) {
            return sendError('activation_code_invalid');
        }

        if ($user->activation_code != $activationCode) {
            $manageMsg = $lang == 'ar' ? 'كود التفعيل غير صحيح' : 'Invalid activation code';
            return sendError($manageMsg);
        }

        $user->activation_code = '';
        $user->status = '1';


        try {
            if ($user->save()) {
                $token = $user->createToken('authToken')->plainTextToken;
                $userdata = [
                    'user' => new UserResource($user),
                    'token' => $token,
                ];

                return sendResponse($userdata);
            } else {
                return sendError('update_error');
            }
        } catch (\PDOException $ex) {
            return sendError(['message' => 'pdo_exception']);
        }
    }
    public function resendActivation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|max:191',
            'country_code' => 'required|max:191',
        ]);
        if ($validator->fails()) {
            return sendError($validator->errors());
        }
        $phone = $request->country_code . $request->phone;
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return sendError( 'user not found');
        }

        if (empty($user->activation_code) || $user->status == '1') {
            return sendError( 'user already activated');
        }

        $user->status = '2';
        $user->resend_code_count = $user->resend_code_count + 1;
        try {
            if ($user->save()) {
                $message = 'your activation code is ' . $user->activation_code;
//                $send = $this->whatsapp($user->phone, $message_whatsapp);
                $userdata = [
                    'resend_code_count' => $user->resend_code_count,
                ];
                return sendResponse($userdata);
            } else {
                return sendError(['message' => 'update_error']);
            }
        } catch (\PDOException $ex) {
            return sendError(['message' => 'pdo_exception']);
        }
    }


    public function logout(Request $request)
    {
        if (auth()->user()) {
            auth()->user()->tokens()->delete();
            return sendResponse(['message' => 'Logged out successfully']);
        } else {
            return sendResponse('User not logged in');
        }
    }

}
