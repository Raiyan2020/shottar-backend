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

function generate_activation_code(?string $phone = null): int
{
    $fixed = $phone !== null ? otp_fixed_code_for_phone($phone) : null;

    if ($fixed !== null) {
        return $fixed;
    }

    return random_int(1000, 9999);
}

function normalize_phone_digits(?string $phone): string
{
    $digits = preg_replace('/\D+/', '', (string) $phone);

    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }

    if (preg_match('/^01[0125]\d{8}$/', $digits)) {
        $digits = '20' . substr($digits, 1);
    }

    return $digits;
}

function otp_fixed_phones_map(): array
{
    $map = [];
    $raw = (string) config('services.otp.fixed_phones', '');

    foreach (explode(',', $raw) as $pair) {
        $pair = trim($pair);

        if ($pair === '' || ! str_contains($pair, ':')) {
            continue;
        }

        [$phone, $code] = explode(':', $pair, 2);
        $digits = normalize_phone_digits($phone);

        if ($digits !== '' && preg_match('/^\d{4}$/', trim($code))) {
            $map[$digits] = (int) trim($code);
        }
    }

    return $map;
}

function otp_fixed_code_for_phone(?string $phone): ?int
{
    $digits = normalize_phone_digits($phone);

    if ($digits === '') {
        return null;
    }

    return otp_fixed_phones_map()[$digits] ?? null;
}

function uses_fixed_otp(?string $phone): bool
{
    return otp_fixed_code_for_phone($phone) !== null;
}

function getimg($filename)
{
    return image_url($filename);
}

/**
 * Collapse duplicate slashes in stored public paths (e.g. images//file.jpg).
 */
function normalize_public_path(?string $path): ?string
{
    if ($path === null) {
        return null;
    }

    $path = trim($path);
    if ($path === '') {
        return null;
    }

    // Absolute URLs: keep scheme, collapse other duplicate slashes.
    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
        return preg_replace('#(?<!:)/{2,}#', '/', $path);
    }

    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');

    // Keep collapsing until stable (covers images////file.jpg).
    do {
        $previous = $path;
        $path = str_replace('//', '/', $path);
    } while ($path !== $previous);

    return $path !== '' ? $path : null;
}

function is_public_image_path(?string $path): bool
{
    $path = normalize_public_path($path);

    if (! $path) {
        return false;
    }

    $invalid = ['path_to_image', 'null', 'undefined', 'none', 'n/a'];
    if (in_array(strtolower($path), $invalid, true)) {
        return false;
    }

    if (preg_match('#^https?://#i', $path)) {
        return true;
    }

    return (bool) preg_match('#\.(jpe?g|png|gif|webp|svg|bmp)$#i', $path);
}

/**
 * Build a public URL for a stored image path (dynamic from DB value).
 * Always serve via /storage/... because the web root symlink points there.
 */
function image_url(?string $path): ?string
{
    $path = normalize_public_path($path);

    if (! is_public_image_path($path)) {
        return null;
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    if (str_starts_with($path, 'storage/')) {
        return asset($path);
    }

    $relative = ltrim(preg_replace('#^storage/#', '', $path), '/');
    $basename = basename($relative);

    // Auto-heal: move legacy app/public/images files into storage/app/public.
    if (! \Illuminate\Support\Facades\Storage::disk('public')->exists($relative)
        && is_file(public_path($relative))) {
        \Illuminate\Support\Facades\Storage::disk('public')->put(
            $relative,
            file_get_contents(public_path($relative))
        );
        @unlink(public_path($relative));
    }

    if ($basename
        && ! \Illuminate\Support\Facades\Storage::disk('public')->exists($relative)
        && is_file(public_path('images/' . $basename))) {
        $relative = 'images/' . $basename;
        \Illuminate\Support\Facades\Storage::disk('public')->put(
            $relative,
            file_get_contents(public_path('images/' . $basename))
        );
        @unlink(public_path('images/' . $basename));
    }

    return asset('storage/' . $relative);
}

/**
 * Public URL for any stored file (PDF/images) under the public disk.
 */
function stored_file_url(?string $path): ?string
{
    $path = normalize_public_path($path);

    if ($path === null || $path === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    if (str_starts_with($path, 'storage/')) {
        return asset($path);
    }

    $relative = ltrim(preg_replace('#^storage/#', '', $path), '/');

    if (! \Illuminate\Support\Facades\Storage::disk('public')->exists($relative)
        && is_file(public_path($relative))) {
        \Illuminate\Support\Facades\Storage::disk('public')->put(
            $relative,
            file_get_contents(public_path($relative))
        );
        @unlink(public_path($relative));
    }

    return asset('storage/' . $relative);
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



