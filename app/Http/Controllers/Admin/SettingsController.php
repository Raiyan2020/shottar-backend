<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    /**
     * §10 — كل منصة والدومينات المسموحة ليها، عشان ميحصلش تاني إن لينك واتساب
     * يتحفظ في خانة تويتر.
     */
    protected array $socialHosts = [
        'instagram' => ['instagram.com', 'www.instagram.com'],
        'twitter' => ['twitter.com', 'www.twitter.com', 'x.com', 'www.x.com'],
        'tiktok' => ['tiktok.com', 'www.tiktok.com', 'vm.tiktok.com'],
        'facebook' => ['facebook.com', 'www.facebook.com', 'fb.com', 'm.facebook.com'],
        'youtube' => ['youtube.com', 'www.youtube.com', 'youtu.be'],
        'snapchat' => ['snapchat.com', 'www.snapchat.com'],
    ];

    public function index()
    {
        // Check if the user is an admin
        $setting = Setting::all();
//        return $setting;
        return view('dashboard.admin.settings.index',compact('setting'));
    }

    //update settings
    public function update(Request $request)
    {
        $payload = $request->except('_token');
        $errors = [];

        foreach ($payload as $key => $value) {
            if ($error = $this->socialLinkError($key, $value)) {
                $errors[$key] = $error;
            }
        }

        if ($errors) {
            return redirect()->back()->withInput()->withErrors($errors);
        }

        foreach ($payload as $k => $v) {
            $this->update_setting([
                'key_id' => $k,
                // §9: النص بيتحفظ بأسطره زي ما هو. بنوحّد CRLF لـ \n بس.
                'value' => is_string($v) ? str_replace(["\r\n", "\r"], "\n", $v) : $v,
            ], $k);
        }

        return redirect()->back()->with('success',__('messages.updated successfully'));
    }

    public function update_setting($data,$key){
        return Setting::where('key_id',$key)->update($data);
    }

    /**
     * بيرجّع رسالة خطأ لو الخانة دي خانة سوشيال واللينك مش من نفس المنصة.
     */
    protected function socialLinkError(string $key, $value): ?string
    {
        if (! isset($this->socialHosts[$key]) || ! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null; // الفاضي مسموح — معناه مفيش حساب والأيقونة تتخفي
        }

        if (! preg_match('#^https?://#i', $value)) {
            return __('general.Please enter a full link starting with https://');
        }

        $host = strtolower((string) parse_url($value, PHP_URL_HOST));

        if (! in_array($host, $this->socialHosts[$key], true)) {
            return __('general.This link does not belong to :platform', ['platform' => Str::title($key)])
                . ' (' . implode(' / ', array_slice($this->socialHosts[$key], 0, 2)) . ')';
        }

        if (trim((string) parse_url($value, PHP_URL_PATH), '/') === '') {
            return __('general.Enter the account link, not the platform home page.');
        }

        return null;
    }
}
