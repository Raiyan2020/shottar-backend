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
        $semesterId = $this->semester_id
            ?? $request->input('semester_id')
            ?? auth()->user()?->semester_id;

        return [
            'id'    => $this->id,
            'name'  => $lang === 'ar' ? $this->name_ar : $this->name_en,
            // صورة المادة الأساسية (العامة)، مش صورة نسخة الفصل/السنة
            'image' => image_url(optional($this->coreSubject)->image ?? $this->image),
            'price' => $this->price,
            'ios_product_id' => $this->ios_product_id,
            'duration' => $this->duration,
            'total_lessons' => $this->courseMaterials->where('status', 1)->where('type','lesson')->count(),
            'total_duration' => DurationFormatter($totalDurationSeconds,$lang),
            'hours' => floor($totalDurationSeconds / 3600),
            'minutes' => floor(($totalDurationSeconds % 3600) / 60),
            'progress_percent' => $progressPercent,
            'teacher_name' => $this->relationLoaded('teachers')
                ? optional($this->teachers->first())->name
                : optional($this->teachers()->first())->name,
            'grade' => $this->whenLoaded('grade', fn () => new GradeResource(
                $this->grade,
                $semesterId ? (int) $semesterId : null
            )),
            'semester' => new SemesterResource($this->whenLoaded('semester')),

        ];
    }
}
