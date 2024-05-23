<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
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
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::get('/auth/refresh-token', [AuthController::class, 'refreshToken'])->middleware('auth:sanctum', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value);
