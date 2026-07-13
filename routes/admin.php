<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContactUsController;
use App\Http\Controllers\Admin\CourseMaterialController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\LessonSectionController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\SemesterController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;


Route::prefix(LaravelLocalization::setLocale() . '/admin')->middleware(['web'])
    ->name('admin.')
    ->group(function () {
        Route::get('login', [AuthController::class, 'showLoginForm'])->name('login.form');
        Route::post('login', [AuthController::class, 'login'])->name('login');

        Route::middleware(['auth:admin','role:admin'])->group(function () {
            // Dashboard
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            // Logout
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

            Route::resource('admins', AdminController::class);

            Route::resource('teachers', TeacherController::class);

            //users
            Route::get('users', [UsersController::class, 'index'])->name('users.index');
            Route::get('users/{id}/edit', [UsersController::class, 'edit'])->name('users.edit');
            Route::put('users/{id}', [UsersController::class, 'update'])->name('users.update');
            Route::delete('users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');

            //cities
            Route::resource('grades', GradeController::class);
            Route::post('grades/toggle-status/{grade}', [GradeController::class, 'toggleStatus'])->name('grades.toggleStatus');
//            Route::post('grades/sort', [GradeController::class, 'sort'])->name('grades.sort');
            Route::post('grades/reorder', [GradeController::class, 'sort'])->name('grades.reorder');

            //study-types
//            Route::resource('study-types', StudyTypeController::class);
//            Route::post('study-type/toggle-status/{id}', [StudyTypeController::class, 'toggleStatus'])->name('study-types.toggleStatus');

            //semesters
            Route::resource('semesters', SemesterController::class);
            Route::post('semesters/toggle-status/{id}', [SemesterController::class, 'toggleStatus'])->name('semesters.toggleStatus');

            //
            Route::resource('subjects', SubjectController::class);
            Route::post('subjects/toggle-status/{id}', [SubjectController::class, 'toggleStatus'])->name('subjects.toggleStatus');

            //sections
                Route::prefix('subjects/{subject}/sections')->name('subjects.sections.')->group(function () {
                Route::get('/', [LessonSectionController::class, 'index'])->name('index');
                Route::get('/create', [LessonSectionController::class, 'create'])->name('create');
                Route::post('/', [LessonSectionController::class, 'store'])->name('store');
                Route::get('/{section}/edit', [LessonSectionController::class, 'edit'])->name('edit');
                Route::put('/{section}', [LessonSectionController::class, 'update'])->name('update');
                Route::delete('/{section}', [LessonSectionController::class, 'destroy'])->name('destroy');
            });
//            Route::get('/subjects/{subject}/sections/{section}/challenges', [\App\Http\Controllers\Admin\ChallengeController::class, 'index'])
//                ->name('subjects.sections.challenges.index');
            Route::prefix('subjects/{subject}/sections/{section}/challenges')
                ->name('subjects.sections.challenges.')
                ->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\ChallengeController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\ChallengeController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\ChallengeController::class, 'store'])->name('store');
                Route::get('/{challenge}/edit', [\App\Http\Controllers\Admin\ChallengeController::class, 'edit'])->name('edit');
                Route::put('/{challenge}', [\App\Http\Controllers\Admin\ChallengeController::class, 'update'])->name('update');
                Route::delete('/{challenge}', [\App\Http\Controllers\Admin\ChallengeController::class, 'destroy'])->name('destroy');
            });
            Route::post('subjects/{subject}/sections/reorder', [\App\Http\Controllers\Admin\LessonSectionController::class, 'sort'])->name('sections.reorder');




            Route::post('sections/{section}/toggle-status', [LessonSectionController::class, 'toggleStatus'])->name('sections.toggleStatus');


            Route::prefix('subjects/{subject}/materials')->name('subjects.materials.')->group(function () {
                Route::get('/{type}', [CourseMaterialController::class, 'index'])->name('index'); // type = lesson | note
                Route::get('/{type}/create', [CourseMaterialController::class, 'create'])->name('create');
                Route::post('/{type}', [CourseMaterialController::class, 'store'])->name('store');
                Route::get('/{material}/edit', [CourseMaterialController::class, 'edit'])->name('edit');
                Route::put('/{material}', [CourseMaterialController::class, 'update'])->name('update');
                Route::delete('/{material}', [CourseMaterialController::class, 'destroy'])->name('destroy');
            });
            Route::post('subjects/materials/toggle-status/{id}', [CourseMaterialController::class, 'toggleStatus'])->name('subjects.materials.toggleStatus');
            Route::post('subjects/materials/toggle-free/{id}', [CourseMaterialController::class, 'toggleIsFrees'])->name('subjects.materials.toggleIsFree');
            Route::post('subjects/{section}/materials/reorder', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'sort'])->name('materials.reorder');


            //challenge
//            Route::resource('challenges', \App\Http\Controllers\Admin\ChallengeController::class);
            Route::post('challenges/{id}/toggle-status', [\App\Http\Controllers\Admin\ChallengeController::class, 'toggleStatus'])
                ->name('challenges.toggleStatus');


            Route::get('/challenge/sessions', [\App\Http\Controllers\Admin\ChallengeSessionController::class, 'index'])->name('challenge.sessions.index');
            Route::get('/challenge/sessions/{id}', [\App\Http\Controllers\Admin\ChallengeSessionController::class, 'show'])->name('challenge.sessions.show');




            //orders
            Route::prefix('orders')->name('orders.')->group(function () {
                Route::get('/', [OrderController::class, 'index'])->name('index');
                Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
                Route::get('/{order}', [OrderController::class, 'show'])->name('show'); // عرض تفاصيل الطلب
                Route::get('/export/excel', [OrderController::class, 'exportExcel'])->name('exportExcel');
            });



            // Settings
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('settings/update', [SettingsController::class, 'update'])->name('settings.update');

            //PaymentMethod
            Route::resource('payment-methods', PaymentMethodController::class);
            Route::post('payment-methods/toggle-status/{id}', [PaymentMethodController::class, 'toggleStatus'])->name('payment-methods.toggleStatus');

            //contact-us
            Route::get('contact-us', [ContactUsController::class, 'index'])->name('contact-us.index');
            Route::delete('contact-us/{id}', [ContactUsController::class, 'destroy'])->name('contact-us.destroy');

            //faq
            Route::resource('faqs', FaqController::class);
            Route::post('faqs/toggle-status/{id}', [FaqController::class, 'toggleStatus'])->name('faqs.toggleStatus');

            Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class);
            Route::post('coupons/toggle-status/{id}', [\App\Http\Controllers\Admin\CouponController::class, 'toggleStatus'])->name('coupons.toggleStatus');


            //notifications
            Route::get('notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
            Route::get('notifications/create', [\App\Http\Controllers\Admin\NotificationController::class, 'create'])->name('notifications.create');
            Route::post('notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'store'])->name('notifications.store');


        });
    });

