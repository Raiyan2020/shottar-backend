<?php

namespace Database\Seeders;

use App\Models\DailyChallenge;
use App\Models\DailyChallengeOption;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeder اختباري: ينشئ تحدي يوم لكل (صف + ترم) موجود لمادة تابعة له.
 * للتشغيل: php artisan db:seed --class=DailyChallengeSeeder
 */
class DailyChallengeSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = Subject::whereNotNull('grade_id')
            ->whereNotNull('semester_id')
            ->get()
            ->unique(fn ($subject) => $subject->grade_id . '-' . $subject->semester_id);

        foreach ($subjects as $subject) {
            $exists = DailyChallenge::where('grade_id', $subject->grade_id)
                ->where('semester_id', $subject->semester_id)
                ->whereDate('challenge_date', Carbon::today())
                ->exists();

            if ($exists) {
                continue;
            }

            $challenge = DailyChallenge::create([
                'subject_id' => $subject->id,
                'grade_id' => $subject->grade_id,
                'semester_id' => $subject->semester_id,
                'title_ar' => 'ما معنى كلمة «الحُجُرات» في اللغة العربية؟',
                'title_en' => 'What does the word "Al-Hujurat" mean?',
                'reward_points' => 25,
                'time_limit_seconds' => 180,
                'challenge_date' => Carbon::today(),
                'status' => 1,
            ]);

            $options = [
                ['ar' => 'الغرف والحجرات السكنية', 'en' => 'Rooms and chambers', 'correct' => true],
                ['ar' => 'الأحجار الكريمة', 'en' => 'Precious stones', 'correct' => false],
                ['ar' => 'القلاع والحصون', 'en' => 'Castles and forts', 'correct' => false],
                ['ar' => 'الأبواب المغلقة', 'en' => 'Closed doors', 'correct' => false],
            ];

            foreach ($options as $index => $option) {
                DailyChallengeOption::create([
                    'daily_challenge_id' => $challenge->id,
                    'title_ar' => $option['ar'],
                    'title_en' => $option['en'],
                    'is_correct' => $option['correct'],
                    'order_by' => $index,
                ]);
            }
        }
    }
}
