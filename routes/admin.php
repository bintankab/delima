<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InnovationArchiveController;
use App\Http\Controllers\Admin\InnovationCategoryController;
use App\Http\Controllers\Admin\InnovationController;
use App\Http\Controllers\Admin\LibraryDocumentController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\OrganisasiController;
use App\Http\Controllers\Admin\PlaceholderController;
use App\Http\Controllers\Admin\RisetArchiveController;
use App\Http\Controllers\Admin\RisetCategoryController;
use App\Http\Controllers\Admin\RisetController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super-admin|admin-opd|bupati|dprd|asn|publik'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('innovations/create', [InnovationController::class, 'create'])->name('innovations.create');
        Route::post('innovations', [InnovationController::class, 'store'])->name('innovations.store');
    });

Route::middleware(['auth', 'role:admin-opd|bupati|dprd|asn|publik'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('innovations/mine', [InnovationController::class, 'mine'])->name('innovations.mine');
        Route::get('innovations/mine/{innovation}', [InnovationController::class, 'showMine'])->name('innovations.mine.show');
        Route::get('innovations/mine/{innovation}/edit', [InnovationController::class, 'editMine'])->name('innovations.mine.edit');
        Route::put('innovations/mine/{innovation}', [InnovationController::class, 'updateMine'])->name('innovations.mine.update');
        Route::delete('innovations/mine/{innovation}', [InnovationController::class, 'destroyMine'])->name('innovations.mine.destroy');
    });

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

        Route::resource('organisasis', OrganisasiController::class)
            ->except(['show'])
            ->middleware('role:super-admin');

        Route::resource('users', UserController::class)
            ->except(['show'])
            ->middleware('role:super-admin|admin-opd');

        Route::resource('library-documents', LibraryDocumentController::class)
            ->except(['show'])
            ->middleware('role:super-admin|admin-opd');

        Route::get('risets', [RisetController::class, 'index'])->name('risets.index');
        Route::get('risets/{riset}', [RisetController::class, 'show'])->name('risets.show');

        Route::get('innovations', [InnovationController::class, 'index'])->name('innovations.index');
        Route::get('innovations/{innovation}', [InnovationController::class, 'show'])->name('innovations.show');

        // Placeholder routes — kerangka navigasi untuk fitur yang akan
        // dibangun bertahap (CRUD penuh menyusul per modul).
        $placeholders = [
            'competitions.index' => 'Daftar Kompetisi',
            'competition-submissions.index' => 'Submission Kompetisi',
            'ideas.index' => 'Ide Inovasi',
            'consultations.index' => 'Klinik Inovasi',
            'news.index' => 'Berita',
            'events.index' => 'Agenda',
            'announcements.index' => 'Pengumuman',
            'faqs.index' => 'FAQ',
        ];

        foreach ($placeholders as $name => $title) {
            Route::get(
                '/'.str_replace('.', '/', $name),
                fn () => app(PlaceholderController::class)->show($title)
            )->name($name);
        }
    });

Route::middleware(['auth', 'role:super-admin|reviewer'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::post('innovations/{innovation}/approve', [InnovationController::class, 'approve'])->name('innovations.approve');
        Route::post('innovations/{innovation}/reject', [InnovationController::class, 'reject'])->name('innovations.reject');
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

        Route::get('media-library', [MediaLibraryController::class, 'index'])->name('media-library.index');
        Route::post('media-library', [MediaLibraryController::class, 'store'])->name('media-library.store');

        Route::get('innovations/archive/create', [InnovationArchiveController::class, 'create'])
            ->name('innovations.archive.create');
        Route::post('innovations/archive', [InnovationArchiveController::class, 'store'])
            ->name('innovations.archive.store');

        Route::get('innovations/{innovation}/edit', [InnovationController::class, 'edit'])->name('innovations.edit');
        Route::put('innovations/{innovation}', [InnovationController::class, 'update'])->name('innovations.update');
        Route::delete('innovations/{innovation}', [InnovationController::class, 'destroy'])->name('innovations.destroy');
        Route::delete('innovations/{innovation}/force', [InnovationController::class, 'forceDestroy'])
            ->name('innovations.force-destroy')
            ->withTrashed();
    });
