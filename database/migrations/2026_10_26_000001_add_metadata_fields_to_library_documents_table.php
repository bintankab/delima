<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_documents', function (Blueprint $table) {
            $table->string('nomor_dokumen')->nullable()->after('title');
            $table->string('penyusun')->nullable()->after('nomor_dokumen');
            $table->unsignedSmallInteger('tahun_terbit')->nullable()->after('penyusun');
            $table->date('tanggal_terbit')->nullable()->after('tahun_terbit');
        });
    }

    public function down(): void
    {
        Schema::table('library_documents', function (Blueprint $table) {
            $table->dropColumn(['nomor_dokumen', 'penyusun', 'tahun_terbit', 'tanggal_terbit']);
        });
    }
};
