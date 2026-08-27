<?php

namespace App\Providers;

use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('dashboard.layouts.sidebar', function ($view) {
            if (Auth::check() && Auth::user()->hasRole('teacher')) {
                $subjects = Subject::whereHas('teachers', function ($q) {
                    $q->where('teacher_id', Auth::id());
                })
                    ->with('grade', 'semester', 'semesters')
                    ->get();

                // كل ترم يظهر كبند مستقل في الـ sidebar
                $teacherSubjectItems = $subjects->flatMap(function ($subject) {
                    $locale = app()->getLocale();
                    $nameColumn = $locale === 'ar' ? 'name_ar' : 'name_en';
                    $semesters = $subject->semesters->isNotEmpty()
                        ? $subject->semesters
                        : collect($subject->semester ? [$subject->semester] : [null]);

                    return $semesters->map(function ($semester) use ($subject, $locale, $nameColumn) {
                        $semesterLabel = $semester
                            ? ($semester->{$nameColumn} ?? $semester->name_en ?? $semester->name_ar)
                            : null;

                        if ($locale === 'en') {
                            $semesterLabel = trim(str_ireplace('Semester', '', $semesterLabel ?? ''));
                        }

                        return (object) [
                            'subject' => $subject,
                            'subject_name' => $subject->{$nameColumn} ?? $subject->name_en ?? $subject->name_ar,
                            'semester' => $semester,
                            'semester_label' => $semesterLabel,
                            'meta' => collect([
                                $subject->grade?->{$nameColumn} ?? $subject->grade?->name_en ?? $subject->grade?->name_ar,
                                $semesterLabel,
                            ])->filter()->implode(' · '),
                        ];
                    });
                })->values();

                $view->with('teacherSubjectItems', $teacherSubjectItems);
            }
        });

    }
}
