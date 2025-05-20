<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

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

// Public routes
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');
    
    // Payment API (Protected by API token and Role middleware)
    Route::middleware('role:admin,finance')->group(function () {
        Route::get('/payments', [App\Http\Controllers\API\PaymentController::class, 'index'])->name('api.payments.index');
        Route::get('/payments/{payment}', [App\Http\Controllers\API\PaymentController::class, 'show'])->name('api.payments.show');
    });
});
