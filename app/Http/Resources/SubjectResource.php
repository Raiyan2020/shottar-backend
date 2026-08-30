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
            // قيمة رقمية خام عشان التطبيق يقدر يرتّب/يحسب من غير ما يـparse نص
            'total_duration_seconds' => (int) $totalDurationSeconds,
            'hours' => (int) floor($totalDurationSeconds / 3600),
            'minutes' => (int) floor(($totalDurationSeconds % 3600) / 60),
            // §12: نسبة التقدّم — بترجع دايمًا (0.0 لو مفيش مشاهدات) عشان
            // التطبيق يقدر يرتّب بالتقدّم ويخفي الزرار لو كلها صفر.
            'progress_percent' => $progressPercent,
            // §5: التقييم والشارة والتصنيف على كل مادة
            'rating' => $this->rating !== null ? round((float) $this->rating, 1) : null,
            'tag' => $this->tag,
            'tag_label' => $this->tagLabel($lang),
            'category' => $this->categoryPayload($lang),
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

    /**
     * §5 — التصنيف هو "المادة الأساسية" (core subject) اللي بتجمع نسخ المادة
     * على كل الصفوف والفصول، وهي التجميعة الموجودة فعلًا في الداتابيز.
     */
    protected function categoryPayload(string $lang): ?array
    {
        $core = $this->relationLoaded('coreSubject')
            ? $this->coreSubject
            : ($this->core_subject_id ? $this->coreSubject : null);

        if (! $core) {
            return null;
        }

        return [
            'id' => (int) $core->id,
            'name' => $core->localizedName($lang),
        ];
    }
}
