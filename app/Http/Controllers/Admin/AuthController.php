<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showLoginForm(){
        if (auth('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login', ['prefix' => 'admin']);
    }


    public function login(Request $request)
    {
        // تحديد نوع المستخدم بناءً على المدخلات
        $userType = $request->input('user_type') ?? 'admin'; // "admin" أو "school"
        $credentials = $request->only('login-email', 'login-password');

        $validator = Validator::make($credentials, [
            'login-email'    => 'required|email',
            'login-password' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // تحديد الـ guard
        $guard = ($userType == 'admin') ? 'admin' : 'school';

        if (Auth::guard($guard)->attempt([
            'email'    => $credentials['login-email'],
            'password' => $credentials['login-password']
        ], $request->filled('remember'))) {

            $user = Auth::guard($guard)->user();

            // 👇 تحقق من الرول ووجهه
            if ($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->hasRole('teacher')) {
                return redirect()->route('teacher.dashboard');
            }

            // في حال ما إله رول معروف
            return redirect()->route($userType.'.dashboard');
        }

        // إذا فشل تسجيل الدخول
        return back()->withErrors([
            'login-email' => 'The provided credentials do not match our records.',
        ])->withInput();
    }




    public function logout(){
        Auth::logout();

        return redirect()->route('admin.login');
    }
}
