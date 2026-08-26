<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\MaterialView;
use Illuminate\Support\Facades\DB;


class SubjectDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = auth()->user();
        $lang = $request->header('lang', 'ar');

        $courseMaterials = $this->courseMaterials;

        // تجميع الأقسام ومحتواها
        $sections = $courseMaterials
            ->groupBy('lesson_section_id')
            // كان بيرتب بالـ id، يعني ترتيب الإنشاء — فترتيب الوحدات اللي
            // المدرّس بيظبطه بالسحب في الداشبورد كان بيتجاهله التطبيق تمامًا.
            // دلوقتي بنرتب بـ order_by بتاع الوحدة نفسها والـ id كسر تعادل.
            ->sortBy(function ($materials, $sectionId) {
                $section = optional($materials->first())->section;

                return [(int) optional($section)->order_by, (int) $sectionId];
            })
            ->map(function ($materials, $sectionId) use ($lang) {
                return [
                    'id' => $sectionId,
                    'name' => optional($materials->first()->section)->{"name_$lang"},
                    // الدروس بتيجي مرتّبة أصلاً من علاقة courseMaterials، بس
                    // بنأكد الترتيب هنا لو حد جاب الداتا باستعلام مش مرتّب.
                    'lessons' => LessonResource::collection(
                        $materials->where('type', 'lesson')->sortBy([['order_by', 'asc'], ['id', 'asc']])->values()
                    ),
                    'notes' => NoteResource::collection(
                        $materials->where('type', 'note')->sortBy([['order_by', 'asc'], ['id', 'asc']])->values()
                    ),
                ];
            })
            ->values();

        // التحقق من الاشتراك
        $isPurchased = $this->orders()
            ->where('user_id', $user?->id)
            ->where('status', 'paid')
            ->exists();
        $progressPercent = $user ? $this->progressPercentForUser($user->id) : 0.0;



        return [
            'id' => $this->id,
            'name' => $lang === 'ar' ? $this->name_ar : $this->name_en,
            // صورة المادة الأساسية (العامة)، مش صورة نسخة الفصل/السنة
            'image' => image_url(optional($this->coreSubject)->image ?? $this->image),
            'total_lessons' => $courseMaterials->where('type', 'lesson')->count(),
            'total_duration' => DurationFormatter(
                $courseMaterials->where('type', 'lesson')->sum('duration'),
                $lang
            ) ?? 0,
            'price' => number_format($this->price, 3),
            'ios_product_id' => $this->ios_product_id,
            'is_purchased' => $isPurchased,
            'grade' => $this->whenLoaded('grade', fn () => new GradeResource(
                $this->grade,
                $this->semester_id ? (int) $this->semester_id : null
            )),
            'sections' => $sections,
            'exams' => ExamResource::collection(
                $this->relationLoaded('exams')
                    ? $this->exams->where('status', true)->values()
                    : $this->exams()->where('status', true)->get()
            ),
            'progress_percent' => $progressPercent,
        ];
    }
}
