<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/unauthorized', function () {
    return view('auth.unauthorized');
})->name('unauthorized');

// Google OAuth routes
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
    // Graceful GET logout handler (e.g., when session expired and user hits /logout)
    Route::get('/logout', [GoogleAuthController::class, 'logout'])->name('logout.get');

// Protected routes
Route::middleware(['auth', 'auth.user'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::match(['get', 'post'], '/groups/search', [GroupController::class, 'search'])->name('groups.search');
    Route::post('/groups/download', [GroupController::class, 'downloadCsv'])->name('groups.download');
    Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');
});
