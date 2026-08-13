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
        $instanceId = config('services.wawp.instance_id');
        $accessToken = config('services.wawp.access_token');

        if (empty($instanceId) || empty($accessToken)) {
            throw new \RuntimeException('WAWP credentials are not configured.');
        }

        $chatId = $this->formatWawpChatId($phone);

        $url = config('services.wawp.base_url', 'https://api.wawp.net/v2/send/text');
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
        $instanceId = config('services.wawp.instance_id');
        $accessToken = config('services.wawp.access_token');

        if (empty($instanceId) || empty($accessToken)) {
            throw new \RuntimeException('WAWP credentials are not configured.');
        }

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

        // زي مشروع Raod: أرقام فقط + @c.us (بدون قصّ صفر عشوائي)
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        // 00965... → 965...
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // رقم محلي مصري 01xxxxxxxxx → 201xxxxxxxxx (WhatsApp لازم كود الدولة)
        if (preg_match('/^01[0125]\d{8}$/', $digits)) {
            $digits = '20' . substr($digits, 1);
        }

        return $digits . '@c.us';
    }

    public function sendVerificationCode(string $phone, int $code,$update_phone = false): \Illuminate\Http\Client\Response
    {
        $msg = $code . ' is your Shottar Application OTP';

        return $this->whatsapp($phone, $msg);
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
