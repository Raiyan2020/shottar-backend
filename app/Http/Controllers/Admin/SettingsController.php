<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        // Check if the user is an admin
        $setting = Setting::all();

        return view('dashboard.admin.settings.index', compact('setting'));
    }

    //update settings
    public function update(Request $request)
    {
        // ملاحظة: كان فيه تحقق بيلزم كل خانة سوشيال بلينك من نفس المنصة، واتشال
        // بناءً على طلب صاحب المشروع — أي لينك بيتحفظ زي ما هو.
        foreach ($request->except('_token') as $k => $v) {
            $this->update_setting([
                'key_id' => $k,
                // §9: النص بيتحفظ بأسطره زي ما هو. بنوحّد CRLF لـ \n بس.
                'value' => is_string($v) ? str_replace(["\r\n", "\r"], "\n", $v) : $v,
            ], $k);
        }

        return redirect()->back()->with('success', __('messages.updated successfully'));
    }

    public function update_setting($data, $key)
    {
        return Setting::where('key_id', $key)->update($data);
    }
}
