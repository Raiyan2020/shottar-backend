<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lang = $request->header('lang', 'ar');
        $name = $lang === 'ar' ? $this->name_ar : $this->name_en;
        $userId = optional(auth()->user())->id;
        // أولوية لعمود is_viewed الجاي من السكوب، ولو مش موجود استخدم دالة الموديل
        $isViewed = false;
        if ($userId) {
            $isViewed = isset($this->is_viewed)
                ? (bool) $this->is_viewed
                : $this->isViewedBy($userId);
        }

        return [
            'id' => $this->id,
            'title' => $name ,
            'duration' => DurationFormatterMinutesAndSeconds($this->duration ?? 0),
            'type' => $this->type,
            'is_free' => $this->is_free ? true : false,
            'video' => $this->video ??  null,
//            'video_details' => vimeo_video_details($this->video ?? null) ,
            'video_details' => null,
            'is_viewed'     => $isViewed,


        ];
    }
}
