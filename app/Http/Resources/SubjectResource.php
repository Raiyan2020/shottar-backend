<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $user = auth()->user();

        $lang = $request->header('lang', 'ar');
        $totalDurationSeconds = $this->courseMaterials
            ->where('status', 1)
            ->where('type', 'lesson')
            ->sum('duration');
        $progressPercent = $user ? $this->progressPercentForUser($user->id) : 0.0;
        return [
            'id'    => $this->id,
            'name'  => $lang === 'ar' ? $this->name_ar : $this->name_en,
            'image' => $this->image ? asset( $this->image) : null,
            'price' => $this->price,
            'duration' => $this->duration,
            'total_lessons' => $this->courseMaterials->where('status', 1)->where('type','lesson')->count(),
            'total_duration' => DurationFormatter($totalDurationSeconds,$lang),
            'hours' => floor($totalDurationSeconds / 3600),
            'minutes' => floor(($totalDurationSeconds % 3600) / 60),
            'progress_percent' => $progressPercent,
            'grade' => new GradeResource($this->whenLoaded('grade')),
            'semester' => new SemesterResource($this->whenLoaded('semester')),

        ];
    }
}
