<?php

namespace App\Providers;

use App\Models\Subject;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\Html\Builder;
use Illuminate\Support\Facades\Session;


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
                    $semesters = $subject->semesters->isNotEmpty()
                        ? $subject->semesters
                        : collect($subject->semester ? [$subject->semester] : [null]);

                    return $semesters->map(function ($semester) use ($subject) {
                        $semesterLabel = $semester
                            ? trim(str_ireplace('Semester', '', $semester->name_en))
                            : null;

                        return (object) [
                            'subject' => $subject,
                            'semester' => $semester,
                            'semester_label' => $semesterLabel,
                            'meta' => collect([
                                $subject->grade?->name_en,
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
