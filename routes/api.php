<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'get'])->name('users.get');
Route::post('/users', [UserController::class, 'create'])->name('users.create');

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login', [UserController::class, 'login'])->name('users.login');
    Route::patch('/users/username', [UserController::class, 'updateUsername'])->name('users.update-username');
    Route::patch('/users/email', [UserController::class, 'updateEmail'])->name('users.update-email');
    Route::patch('/users/password', [UserController::class, 'updatePassword'])->name('users.update-password');
    Route::delete('/users', [UserController::class, 'delete'])->name('users.delete');
});
