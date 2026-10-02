<?php

use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\CheckIn\ScanCheckInController;
use App\Http\Controllers\Api\V1\Exams\ConvocationDownloadController;
use App\Http\Controllers\Api\V1\Exams\MyExamsController;
use App\Http\Controllers\Api\V1\Schedules\GroupScheduleController;
use App\Http\Controllers\Api\V1\Schedules\MyScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function (): void {
    Route::post('auth/login', LoginController::class)->name('api.v1.auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', LogoutController::class)->name('api.v1.auth.logout');
        Route::get('auth/user', CurrentUserController::class)->name('api.v1.auth.user');

        Route::prefix('schedules')->group(function (): void {
            Route::get('my-schedule', MyScheduleController::class)->name('api.v1.schedules.mine');
            Route::get('group/{id}', GroupScheduleController::class)->name('api.v1.schedules.group');
        });

        Route::prefix('exams')->group(function (): void {
            Route::get('my-exams', MyExamsController::class)->name('api.v1.exams.mine');
            Route::get('convocation/{id}/download', ConvocationDownloadController::class)->name('api.v1.exams.convocation.download');
        });

        Route::post('check-in/scan', ScanCheckInController::class)->name('api.v1.check-in.scan');
    });
});
