<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Enums\TokenAbility;
use PHPUnit\Framework\Attributes\Group;

Route::controller(AuthController::class)
    ->prefix('auth')
    ->group(function () {
        Route::middleware('guest:sanctum')
            ->group(function () {
                Route::post('signup', 'signup')->name('auth.signup');
                Route::post('email/verification', 'sendVerificationCode');
                Route::post('email/verify', 'verifyEmail');
                Route::post('login', 'login')->name('auth.login');
            });
        Route::middleware('auth:sanctum')
            ->group(function () {
                Route::post('logout', 'logout')->name('auth.logout');
            });
    });

Route::get('/auth/refresh-token', [AuthController::class, 'refreshToken'])->middleware('auth:sanctum', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value);


/*Route::get('api/auth/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNListner();

    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1']);*/
