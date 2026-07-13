<?php

namespace App\Helpers;

use Carbon\Carbon;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Http;
use Kreait\Firebase\Factory;
use Intervention\Image\ImageManagerStatic as Image;

/**
 * Class Helpers
 * @package App\Helpers
 */
trait Functions
{

    public function whatsappOld($phone , $bode){

        $whatsappToken = config('services.whatsapp.token');
        $whatsappInstance = config('services.whatsapp.instance');

        $params=array(
            'token' => $whatsappToken,
            'to' => $phone,
            'body' =>$bode,

        );
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ultramsg.com/{$whatsappInstance}/messages/chat",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        }

    }

    public function whatsapp($phone, $body ,$dedupKey = null)
    {
//        $instanceId   = $user->whatsapp_instance_id ?? config('services.wawp.instance_id');
//        $accessToken  = $user->whatsapp_token ?? config('services.wawp.access_token');     // access_token\

//        $instanceId= '3A8430C0E496';
        $instanceId= 'FDF6B20941DD';
        $accessToken = 'rhS3eDMYV7goCg';

        $chatId = $this->formatWawpChatId($phone , $dedupKey);

//        $url = "https://wawp.net/wp-json/awp/v1/send";
        $url = "https://api.wawp.net/v2/send/text";
        $response = Http::timeout(20)->post($url, [
            'instance_id'   => $instanceId,
            'access_token'  => $accessToken,
            'chatId'        => $chatId,
            'message'       => $body,
        ]);

        return $response;
    }


    public function whatsappImage(string $phone, string $imageUrl, ?string $caption = null): \Illuminate\Http\Client\Response
    {
        $instanceId  = 'FDF6B20941DD';
//        $instanceId  = '3A8430C0E496';

        $accessToken = 'rhS3eDMYV7goCg';

        $chatId = $this->formatWawpChatId($phone);

        // filename + mimetype (حسب رابط الصورة)
        $path = parse_url($imageUrl, PHP_URL_PATH) ?: '';
        $filename = basename($path) ?: 'image.jpg';

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimetype = match ($ext) {
            'png' => 'image/png',
            default => 'image/jpeg', // jpg / jpeg / أي شيء اعتبره jpeg
        };

//        $url = "https://wawp.net/wp-json/awp/v1/sendImage";
        $url = "https://api.wawp.net/v2/send/image";


        // الأفضل إرسالها كـ form (الدوك عاملها query params لكن الفورم شغال نفس الفكرة)
        $payload = [
            'instance_id'  => $instanceId,
            'access_token' => $accessToken,
            'chatId'       => $chatId,
            'file'         => [
                'url'      => $imageUrl,
                'filename' => $filename,
                'mimetype' => $mimetype,
            ],
        ];

        if (!empty($caption)) {
            $payload['caption'] = $caption;
        }

        return Http::timeout(30)->asForm()->post($url, $payload);
    }


    protected function formatWawpChatId(string $phone): string
    {
        $phone = trim($phone);

        // لو أصلاً جايك chatId جاهز من webhook
        if (str_contains($phone, '@c.us') || str_contains($phone, '@g.us') || str_contains($phone, '@lid')) {
            return $phone;
        }

        // شيل كل شيء غير أرقام
        $digits = preg_replace('/\D+/', '', $phone);

        // لو بدأ بـ 00 (مثل 00970...) احذفها
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits =  substr($digits, 1);
        }

        // الآن صار E.164 بدون +
        return $digits . '@c.us';
    }

    public function sendVerificationCode(string $phone, int $code,$update_phone = false): void
    {
        if ($update_phone){
            $msg = 'Your Update Phone code is ' . $code . ' Welcome to Shottar 👩🏻‍🏫';
        }else{
        $msg = 'رمز التفعيل الخاص بك هو ' . $code . ' أهلاً بك في تطبيق شطار 👩🏻‍🏫';
        }
        $this->whatsapp($phone, $msg);
    }

    function vimeoToPlayerUrl(string $link): ?string {
        // يقبل شكل: vimeo.com/{id}/{hash} أو vimeo.com/{id}
        if (preg_match('~vimeo\.com/(\d+)(?:/([a-zA-Z0-9]+))?~', $link, $m)) {
            $id = $m[1] ?? null;
            $hash = $m[2] ?? null;
            if ($id) {
                return $hash
                    ? "https://player.vimeo.com/video/{$id}?h={$hash}"
                    : "https://player.vimeo.com/video/{$id}";
            }
        }
        return null;
    }






}
