<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TransactionController;

// 公開路由：不需要驗證
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 需要驗證 Token 的路由
Route::middleware('auth:sanctum')->group(function () {
    // 放前面的靜態路由
    Route::get('/transactions/filter', [TransactionController::class, 'filterByMonth']);
    Route::get('/transactions/summary', [TransactionController::class, 'summary']);

    // 再放動態路由與其他基本路由
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
});
