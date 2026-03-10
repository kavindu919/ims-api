<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CupboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('user-me');

    Route::get('/users', [UserController::class, 'getAllUsers'])->name('users.getallusers');
    Route::post('/users', [UserController::class, 'createUser'])->name('users.create');
    Route::post('/user/get', [UserController::class, 'getUser'])->name('user-get');
    Route::post('/user/update', [UserController::class, 'updateUser'])->name('users.update');

    Route::get('/cupboards', [CupboardController::class, 'getAllCupboards'])->name('cupboards.getall');
    Route::post('/cupboards/create', [CupboardController::class, 'createCupboards'])->name('cupboards.create');
    Route::post('/cupboard/get', [CupboardController::class, 'getCupboardWithPlaces'])->name('cupboard.get');
    Route::post('/cupboard/update', [CupboardController::class, 'updateCupboard'])->name('cupboard.update');
    Route::post('/cupboard/delete', [CupboardController::class, 'deleteCupboard'])->name('cupboard.delete');
});
