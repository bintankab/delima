<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisasi_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisasi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('jabatan')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->unique(['organisasi_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisasi_user');
    }
};
