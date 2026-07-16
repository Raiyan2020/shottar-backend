<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\SemesterController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\MaterialViewController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\DailyChallengeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



Route::get('settings', SettingController::class);
Route::get('payment-method', PaymentMethodController::class);
Route::post('/whatsapp/send-image', [SettingController::class, 'sendImage']);
Route::get('/whatsapp/broadcast-image', [SettingController::class, 'broadcastImage']);


Route::controller(AuthController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login')->name('login');
    Route::post('activateAccount', [AuthController::class, 'activateAccount']);
    Route::post('resendActivation', [AuthController::class, 'resendActivation']);


});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::delete('account', [UserController::class, 'deleteAccount']);
//    //profile
    Route::get('show-profile/{user_id?}', [UserController::class, 'show']);
    Route::post('update-profile', [UserController::class, 'update']);
    //update-user-settings
    Route::post('update-user-settings', [UserController::class, 'updateUserSettings']);

    Route::delete('destroy-users', [UserController::class, 'destroy']);


    Route::post('contact-us' ,ContactUsController::class);

    //faqs
    Route::get('faqs', FaqController::class);

    //grades
    Route::get('grades', GradeController::class);
    //semesters
    Route::get('semesters', SemesterController::class);
    //subjects
    Route::post('subjects', [SubjectController::class, 'index']);
    Route::post('subjects/purchased', [SubjectController::class, 'purchasedSubjects']);

    Route::get('subjects/{id}', [SubjectController::class, 'details']);
    //vimeo_video_details
    Route::get('/video-details/{id}', [SubjectController::class, 'videoDetails']);


    //MaterialView
    Route::post('subjects/material-view', [MaterialViewController::class, 'store']);


    //order
    Route::post('order', [OrderController::class, 'store']);
    //coupon check
    Route::post('coupon/check', [OrderController::class, 'checkCoupon']);


    //Challenge
    Route::get('challenge/{subject_id}',[ChallengeController::class,'ChallengeSubjects']);
    Route::post('challenge/start',[ChallengeController::class,'startChallenge']);
    Route::post('challenge/store-answer',[ChallengeController::class,'storeAnswer']);
    Route::post('challenge/finish', [ChallengeController::class, 'finishChallenge']);
//    Route::post('challenges', [ChallengeController::class, 'finishChallenge']);
    Route::post('challenges/previous', [ChallengeController::class, 'previousChallenges']);
    Route::post('challenge/result', [ChallengeController::class, 'showChallengeResult']);
    Route::get('daily-challenge', [DailyChallengeController::class, 'show']);
    Route::post('daily-challenge/answer', [DailyChallengeController::class, 'answer']);
    Route::get('daily-challenge/history', [DailyChallengeController::class, 'history']);





    //notification
    Route::get('notification', [\App\Http\Controllers\Api\NotificationController::class, 'index']);



});
Route::get('callback/success', [OrderController::class, 'paymentSuccess'])->name('ordersSuccess');
Route::get('callback/error', [OrderController::class, 'paymentError'])->name('ordersError');
