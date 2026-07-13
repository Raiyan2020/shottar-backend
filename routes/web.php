<?php


use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Laravel\Telescope\Telescope;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


//test
Route::get('/test', function () {
    return view('test');
});




Route::group(
    ['prefix' => LaravelLocalization::setLocale(), 'middleware' => ['localize']], // يمكن أن يكون middleware مختلف حسب إعداداتك
    function () {
        Route::get('/', function () {
            $user = Auth::guard('admin')->user();

            if (!$user) {
                return redirect()->route('admin.login');
            }

            // تحقق من الدور
            if ($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->hasRole('teacher')) {
                return redirect()->route('teacher.dashboard');
            }

            // إذا ما عنده أي دور معروف رجعه للّوجين
            return redirect()->route('admin.login');
        });

    }
);

Route::middleware(['auth:admin', 'role:teacher'])
    ->prefix('teacher')->name('teacher.')
    ->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\DashboardTeacherController::class, 'index'])->name('dashboard');
        Route::post('/logout', [\App\Http\Controllers\Admin\AuthController::class, 'logout'])->name('logout');


        // الاقسام
        Route::prefix('subjects/{subject}/sections')->name('subjects.sections.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\LessonSectionController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\LessonSectionController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\LessonSectionController::class, 'store'])->name('store');
            Route::get('/{section}/edit', [\App\Http\Controllers\Admin\LessonSectionController::class, 'edit'])->name('edit');
            Route::put('/{section}', [\App\Http\Controllers\Admin\LessonSectionController::class, 'update'])->name('update');
            Route::delete('/{section}', [\App\Http\Controllers\Admin\LessonSectionController::class, 'destroy'])->name('destroy');
        });
        Route::post('sections/{section}/toggle-status', [\App\Http\Controllers\Admin\LessonSectionController::class, 'toggleStatus'])->name('sections.toggleStatus');
        Route::post('subjects/{subject}/sections/reorder', [\App\Http\Controllers\Admin\LessonSectionController::class, 'sort'])->name('sections.reorder');

        // المواد (الدروس والملاحظات)
        Route::prefix('subjects/{subject}/materials')->name('subjects.materials.')->group(function () {
            Route::get('/{type}', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'index'])->name('index'); // type = lesson | note
            Route::get('/{type}/create', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'create'])->name('create');
            Route::post('/{type}', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'store'])->name('store');
            Route::get('/{material}/edit', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'edit'])->name('edit');
            Route::put('/{material}', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'update'])->name('update');
            Route::delete('/{material}', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'destroy'])->name('destroy');
        });
        Route::post('subjects/materials/toggle-status/{id}', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'toggleStatus'])->name('subjects.materials.toggleStatus');
        Route::post('subjects/materials/toggle-free/{id}', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'toggleIsFrees'])->name('subjects.materials.toggleIsFree');

        Route::post('materials/{type}/{section}/reorder', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'sort'])->name('materials.reorder');

        Route::get('/vimeo-upload-video', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'getVimeoUploadUrlVideo']);

        Route::prefix('subjects/{subject}/sections/{section}/challenges')->name('subjects.sections.challenges.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ChallengeController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\ChallengeController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\ChallengeController::class, 'store'])->name('store');
            Route::get('/{challenge}/edit', [\App\Http\Controllers\Admin\ChallengeController::class, 'edit'])->name('edit');
            Route::put('/{challenge}', [\App\Http\Controllers\Admin\ChallengeController::class, 'update'])->name('update');
            Route::delete('/{challenge}', [\App\Http\Controllers\Admin\ChallengeController::class, 'destroy'])->name('destroy');
        });
        //challenge
        Route::get('/challenge/sessions', [\App\Http\Controllers\Admin\ChallengeSessionController::class, 'index'])->name('challenge.sessions.index');



    });
Route::post('/vimeo-upload-url', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'getUploadUrl']);
Route::get('/vimeo-info', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'getVideoInfo']);

//Route::post('/vimeo-save', [\App\Http\Controllers\Admin\CourseMaterialController::class, 'saveVideo']);


