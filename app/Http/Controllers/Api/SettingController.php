<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Functions;
use App\Http\Controllers\Controller;
use App\Jobs\BroadcastWawpImageJob;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use Functions;

    public function __invoke(Request $request)
    {
        $lang = $request->header('lang') === 'en' ? 'en' : 'ar';
        $fallback = $lang === 'en' ? 'ar' : 'en';

        // §9: النص بيرجع زي ما هو بأسطره الأصلية (\n) من غير أي إعادة تنسيق.
        // لو نسخة اللغة المطلوبة فاضية بنرجّع اللغة التانية بدل ما نرجّع فراغ.
        $data['terms'] = $this->legalText('terms', $lang, $fallback);
        $data['privacy_policy'] = $this->legalText('privacy_policy', $lang, $fallback);

        // §10: اللينك اللي مش لينك حساب حقيقي على المنصة بيرجع null عشان
        // التطبيق يخفي الأيقونة بدل ما يفتح لينك غلط (زي لينك واتساب في خانة
        // تويتر، أو الصفحة الرئيسية لتيك توك من غير حساب).
        $data['instagram'] = $this->socialUrl(setting('instagram'), 'instagram');
        $data['twitter'] = $this->socialUrl(setting('twitter'), 'twitter');
        $data['tiktok'] = $this->socialUrl(setting('tiktok'), 'tiktok');
        $data['phone'] = setting('phone');
        $data['whatsapp'] = $this->whatsappUrl(setting('phone'));

        $data['forced_update_android'] = setting('forced_update_android');
        $data['forced_update_ios'] = setting('forced_update_ios');
        $data['android_version'] = setting('android_version');
        $data['ios_version'] = setting('ios_version');
        $data['force_close'] = setting('force_close', '0');

        return sendResponse($data);


    }

    /**
     * §9 — الشروط / سياسة الخصوصية.
     *
     * بيرجّع القيمة المخزّنة كما هي (بأسطرها) بدون أي معالجة. المهم إن اللي
     * بيتخزن من لوحة التحكم يكون فيه \n حقيقي — الخانة في اللوحة بقت textarea
     * بعد ما كانت input نص بسطر واحد وكانت بتضيّع الأسطر عند الحفظ.
     */
    protected function legalText(string $key, string $lang, string $fallback): ?string
    {
        $value = setting($key . '_' . $lang);

        if (! is_string($value) || trim($value) === '') {
            $value = setting($key . '_' . $fallback);
        }

        if (! is_string($value)) {
            return null;
        }

        // توحيد نهايات الأسطر بس (CRLF من محرّرات ويندوز) — الباقي زي ما هو.
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return trim($value) === '' ? null : $value;
    }

    /**
     * §10 — بيتأكد إن اللينك فعلًا لحساب على المنصة المطلوبة.
     */
    protected function socialUrl(?string $value, string $platform): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || ! preg_match('#^https?://#i', $value)) {
            return null;
        }

        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        $path = trim((string) parse_url($value, PHP_URL_PATH), '/');

        if ($host === '') {
            return null;
        }

        $allowedHosts = [
            'instagram' => ['instagram.com', 'www.instagram.com'],
            'twitter' => ['twitter.com', 'www.twitter.com', 'x.com', 'www.x.com'],
            'tiktok' => ['tiktok.com', 'www.tiktok.com', 'vm.tiktok.com'],
        ];

        if (! in_array($host, $allowedHosts[$platform] ?? [], true)) {
            return null;
        }

        // الصفحة الرئيسية من غير حساب مش لينك مفيد
        if ($path === '') {
            return null;
        }

        // قيم القوالب الافتراضية اللي جاية من الـ seeder
        if (in_array(strtolower($path), ['yourprofile', 'username', 'user'], true)) {
            return null;
        }

        return $value;
    }

    /**
     * لينك واتساب مبني على رقم الدعم — عشان التطبيق مايحتاجش يستخدم خانة
     * `twitter` كأنها واتساب زي ما كان بيحصل.
     */
    protected function whatsappUrl(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits === '' ? null : 'https://wa.me/' . $digits;
    }

    //sendImage



    public function broadcastImage(Request $request)
    {
        // الصورة + الكابشن ثابتين (بدون إرسالهم بالطلب)
        $imageUrl = url('whatsapp.jpeg');
        $caption = 'عرض خاص لطلبة الثانوية';

        if (!$imageUrl) {
            return response()->json([
                'ok' => false,
                'message' => 'broadcast_image_url not configured',
            ], 422);
        }

        $queued = 0;

        User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('id')
            ->chunk(200, function ($users) use ($imageUrl, $caption, &$queued) {
                foreach ($users as $user) {
                    dispatch(new BroadcastWawpImageJob(
                        userId: $user->id,
                        imageUrl: $imageUrl,
                        caption: $caption
                    ))->onQueue('wawp');

                    $queued++;
                }
            });

        return response()->json([
            'ok' => true,
            'message' => 'Broadcast queued',
            'queued_count' => $queued,
        ]);
    }

}
