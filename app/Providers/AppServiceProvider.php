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
                    ->with('grade','semester')
                    ->get();

                $view->with('teacherSubjects', $subjects);
            }
        });

    }
}
