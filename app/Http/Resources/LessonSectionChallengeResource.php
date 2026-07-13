<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonSectionChallengeResource extends JsonResource
{
    public function toArray($request)
    {
        $user = auth('api')->user(); // أو حسب نظامك

        // عدد الدروس في القسم
        $totalLessons = $this->courseMaterials()->where('type',1)->count();

        // عدد الدروس التي شاهدها المستخدم
        $viewedLessons = 0;
        if ($user) {
            $viewedLessons = $this->courseMaterials()
                ->where('type',1)
                ->whereHas('materialViews', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->count();
        }

        // هل أكمل الدروس؟
        $completedLessons = $totalLessons > 0 && $viewedLessons >= $totalLessons;

        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'challenge_duration' => $this->challenge_duration,
            'challenge_active' => (bool) $this->challenge_active,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
            'viewed_lessons' => $viewedLessons,
            'status_label' => $completedLessons
                ? 'ready_for_challenge'
                : 'lessons_not_completed',
        ];
    }
}
