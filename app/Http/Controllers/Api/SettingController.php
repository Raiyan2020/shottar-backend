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
        $lang =$request->header('lang');

        $data['terms'] = setting('terms_' . $lang);
        $data['privacy_policy'] = setting('privacy_policy_' . $lang);

        $data['instagram'] = setting('instagram');
        $data['twitter'] = setting('twitter');
        $data['tiktok'] = setting('tiktok');
        $data['phone'] = setting('phone');

        $data['forced_update_android'] = setting('forced_update_android');
        $data['forced_update_ios'] = setting('forced_update_ios');
        $data['android_version'] = setting('android_version');
        $data['ios_version'] = setting('ios_version');
        $data['force_close'] = setting('force_close', '0');

        return sendResponse($data);


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
