<?php

declare(strict_types=1);

use App\Presentation\Http\Controllers\V1\AlertRuleController;
use App\Presentation\Http\Controllers\V1\MonitoredCoinController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::apiResource('/monitored-coins', MonitoredCoinController::class);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('/alert-rules', AlertRuleController::class);
        Route::get('/user', function (\Illuminate\Http\Request $request) {
            return $request->user();
        });
    });
});
