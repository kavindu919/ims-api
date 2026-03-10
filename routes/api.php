<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json(['status' => 'ok']);
});

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [UserController::class, 'logout'])->name('user-logout');
    Route::get('/me', [UserController::class, 'me'])->name('user-me');
});
