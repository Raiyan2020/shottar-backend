<?php


use Carbon\Carbon;

function sendResponse($result, $message = null)
{

    $response = [
        'status' => true,
//        'message' => $message,
        'data'    => $result,
    ];
    if(!empty($result)){
        $response['data'] = $result;
    }
    if(!empty($message)){
        $response['message'] = $message;
    }

    return response()->json($response, 200);
}

 function sendError($error = 'error', $errorMessages = [], $code = 400 , )
{
    $response = [
        'status' => false,
        'message' => $error,
    ];

    if(!empty($errorMessages)){
        $response['data'] = $errorMessages;
    }

    return response()->json($response, $code);
}

function getimg($filename)
{
    return asset($filename);
}

/**
 * Upload an image
 *
 * @param $img
 */
function uploader($value ,$directory)
{
    $path = '/storage/' . \Storage::disk('public')->putFile($directory, $value);

    return $path;
}

function check_promocode($promocode, $today)
{
    $back['status'] = 0;

    if (!$promocode) {
        $back['message'] = __('lang.not_found_promocode');
        return $back;
    } else if ($promocode->status == 'not_active') {
        $back['message'] = __('lang.in_active_promocode');
        return $back;
    } else if ($promocode->end <= $today || $promocode->start > $today) {
        $back['message'] = __('lang.expired_promocode');
        return $back;
    }


    $back['status'] = 1;
    return $back;
}

function dayNumber($day) {
    $days = [
        'sunday' => 1,
        'monday' => 2,
        'tuesday' => 3,
        'wednesday' => 4,
        'thursday' => 5,
        'friday' => 6,
        'saturday' => 7,
    ];

    return $days[$day];
}

function setting($key, $default = null)
{
    return \App\Models\Setting::where('key_id', $key)->value('value') ?? $default;
}

function socials(): array
{
    return [
        'facebook',
        'twitter',
        'instagram',
        'snapchat',
        'tiktok',
        'youtube',
    ];
}
 function SwalMessage($route,$icon, $title, $text)
{
    return redirect()->route($route)->with([
        'swal' => [
            'icon' => $icon,
            'title' => $title,
            'text' => $text
        ]
    ]);
}

function months($days)
{
    $month = round($days / 30);
    switch ($month) {
        case 1: $result = __('Month'); break;
        case 3: $result = '3 '.__('Months'); break;
        case 6: $result = '6 '.__('Months'); break;
        case 12: $result = __('Year'); break;
        default: $result = $month.' '.__('Months'); break;
    }

    return $result;
}

function isAllowedCanteenDay(): bool
{
    $dayOfWeek = now()->dayOfWeek;
    return in_array($dayOfWeek, [
        Carbon::SUNDAY,    // 0
        Carbon::MONDAY,    // 1
        Carbon::TUESDAY,   // 2
        Carbon::WEDNESDAY, // 3
        Carbon::THURSDAY   // 4
    ]);
}
//DurationFormatter
function DurationFormatter(int $seconds, string $lang = 'ar') : string
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
//    $parts = [];
//
    if ($lang === 'ar') {
        return $hours.' '.'ساعة' . ' ' . $minutes . ' ' . 'دقيقة';
    }else{
        return $hours . ' ' . __('hours') . ' ' . $minutes . ' ' . __('minutes');
    }

}

function DurationFormatterMinutesAndSeconds(int $seconds = 0, string $lang = 'ar'): string
{
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;

    if ($lang === 'ar') {
        return $minutes . ' دقيقة ' . $remainingSeconds . ' ثانية';
    } else {
        return $minutes . ' min ' . $remainingSeconds . ' sec';
    }
}


if (! function_exists('panelPrefix')) {
    function panelPrefix(): string {
        $user = auth('admin')->user();
        return $user && $user->hasRole('admin') ? 'admin' : 'teacher';


    }
}

if (!function_exists('vimeo_video_details')) {
    function vimeo_video_details(string $url = null): ?array
    {
        if (empty($url)) {
            return null;
        }

        try {
            // ابني رابط الـ oEmbed API
            $apiUrl = "https://vimeo.com/api/oembed.json?url=" . urlencode($url);

            // استدعاء البيانات (ممكن تستخدم Http::get من Laravel أو file_get_contents)
            $response = file_get_contents($apiUrl);

            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);
//            return $data;

            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }

            return [
                'video_id'   => $data['video_id'] ?? null,
                'title'      => $data['title'] ?? null,
//                'author'     => $data['author_name'] ?? null,
                'duration'   => $data['duration'] ?? null,
                'width'      => $data['width'] ?? null,
                'height'     => $data['height'] ?? null,
                'thumbnail'  => $data['thumbnail_url'] ?? null,
                'embed_html' => $data['html'] ?? null,
                'player_url' => isset($data['video_id'])
                    ? "https://player.vimeo.com/video/{$data['video_id']}?h=" . (explode(':', $data['uri'])[1] ?? '')
                    : null,
//                'raw'        => $data, // لو بدك كل البيانات الخام كمان
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}



