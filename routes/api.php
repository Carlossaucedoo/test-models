<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'get'])->name('users.get');
Route::post('/users', [UserController::class, 'create'])->name('users.create');

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login', [UserController::class, 'login'])->name('users.login');
    Route::patch('/users/name', [UserController::class, 'updateName'])->name('users.update-name');
});
