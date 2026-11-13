<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/transactions');
Route::view('/login', 'admin.login')->name('login');
Route::post('/login', [AdminController::class, 'login'])->middleware('throttle:5,1');
Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/logout', [AdminController::class, 'logout']);
    Route::get('/admin/transactions', [AdminController::class, 'index']);
    Route::get('/admin/transactions/create', [AdminController::class, 'create']);
    Route::post('/admin/transactions', [AdminController::class, 'store']);
    Route::get('/admin/transactions/{booking}', [AdminController::class, 'show']);
    Route::patch('/admin/transactions/{booking}', [AdminController::class, 'update']);
    Route::delete('/admin/transactions/{booking}', [AdminController::class, 'destroy']);
    Route::post('/admin/transactions/{booking}/reconcile', [AdminController::class, 'reconcile']);
});
