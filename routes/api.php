<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TransactionController;

// 公開路由：不需要驗證
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 需要驗證 Token 的路由
Route::middleware('auth:sanctum')->group(function () {
    // 使用者相關
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // 交易相關
    Route::get('/transactions', [TransactionController::class, 'index']);        // 取得目前使用者所有交易記錄
    Route::post('/transactions', [TransactionController::class, 'store']);         // 新增一筆交易記錄
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);      // 取得單筆交易記錄
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);      // 更新交易記錄
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);  // 刪除交易記錄

    // 每月統計：例如 /api/summary?month=2025-02
    Route::get('/summary', [TransactionController::class, 'summary']);
});
