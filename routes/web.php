<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/riset', [PublicController::class, 'riset'])->name('riset');
Route::get('/riset/{riset:slug}', [PublicController::class, 'risetShow'])->name('riset.show');
Route::get('/riset/{riset:slug}/dokumen/{media}', [PublicController::class, 'risetDocument'])->name('riset.document');
Route::get('/inovasi', [PublicController::class, 'inovasi'])->name('inovasi');
Route::get('/inovasi/{innovation:slug}', [PublicController::class, 'inovasiShow'])->name('inovasi.show');
Route::get('/inovasi/{innovation:slug}/dokumen/{media}', [PublicController::class, 'inovasiDocument'])->name('inovasi.document');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/admin.php';
