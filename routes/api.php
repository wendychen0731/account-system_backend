<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\StatisticsController; // 加入這一行

// 公開路由：不需要驗證
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 需要驗證 Token 的路由
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/transactions/filter', [TransactionController::class, 'filterByMonth']);
    Route::get('/transactions/summary', [TransactionController::class, 'summary']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);

    // 確保正確引用 StatisticsController
    Route::get('/statistics/daily-summary', [StatisticsController::class, 'dailySummary']);
});
