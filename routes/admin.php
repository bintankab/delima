<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InnovationCategoryController;
use App\Http\Controllers\Admin\PlaceholderController;
use App\Http\Controllers\Admin\RisetArchiveController;
use App\Http\Controllers\Admin\RisetCategoryController;
use App\Http\Controllers\Admin\RisetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super-admin|admin-opd|reviewer'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('innovation-categories', InnovationCategoryController::class)
            ->except(['show'])
            ->middleware('role:super-admin');

        Route::resource('riset-categories', RisetCategoryController::class)
            ->except(['show'])
            ->middleware('role:super-admin');

        Route::get('risets', [RisetController::class, 'index'])->name('risets.index');
        Route::get('risets/{riset}', [RisetController::class, 'show'])->name('risets.show');

        // Placeholder routes — kerangka navigasi untuk fitur yang akan
        // dibangun bertahap (CRUD penuh menyusul per modul).
        $placeholders = [
            'innovations.index' => 'Etalase Inovasi',
            'innovations.archive.create' => 'Input Arsip Inovasi Lama',
            'competitions.index' => 'Daftar Kompetisi',
            'competition-submissions.index' => 'Submission Kompetisi',
            'ideas.index' => 'Ide Inovasi',
            'consultations.index' => 'Klinik Inovasi',
            'news.index' => 'Berita',
            'events.index' => 'Agenda',
            'announcements.index' => 'Pengumuman',
            'library-documents.index' => 'Pustaka',
            'faqs.index' => 'FAQ',
            'organisasis.index' => 'Organisasi/OPD',
            'users.index' => 'Pengguna',
        ];

        foreach ($placeholders as $name => $title) {
            Route::get(
                '/'.str_replace('.', '/', $name),
                fn () => app(PlaceholderController::class)->show($title)
            )->name($name);
        }
    });

Route::middleware(['auth', 'role:super-admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('risets/archive/create', [RisetArchiveController::class, 'create'])
            ->name('risets.archive.create');
        Route::post('risets/archive', [RisetArchiveController::class, 'store'])
            ->name('risets.archive.store');

        Route::get('risets/{riset}/edit', [RisetController::class, 'edit'])->name('risets.edit');
        Route::put('risets/{riset}', [RisetController::class, 'update'])->name('risets.update');
        Route::delete('risets/{riset}', [RisetController::class, 'destroy'])->name('risets.destroy');
        Route::delete('risets/{riset}/force', [RisetController::class, 'forceDestroy'])
            ->name('risets.force-destroy')
            ->withTrashed();
    });
