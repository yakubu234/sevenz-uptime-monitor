<?php

use App\Http\Controllers\Api\MonitorController;
use App\Http\Controllers\Api\MonitorHistoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('monitors')->group(function (): void {
    Route::get('/', [MonitorController::class, 'index']);
    Route::post('/', [MonitorController::class, 'store']);
    Route::get('/{id}/history', [MonitorHistoryController::class, 'index']);
});
