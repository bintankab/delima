<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlaceholderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super-admin|admin-opd|reviewer'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Placeholder routes — kerangka navigasi untuk fitur yang akan
        // dibangun bertahap (CRUD penuh menyusul per modul).
        $placeholders = [
            'innovations.index' => 'Etalase Inovasi',
            'innovations.archive.create' => 'Input Arsip Inovasi Lama',
            'innovation-categories.index' => 'Kategori Inovasi',
            'risets.index' => 'Daftar Riset',
            'risets.archive.create' => 'Input Arsip Riset Lama',
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
