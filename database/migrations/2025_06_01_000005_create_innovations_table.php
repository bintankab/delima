<?php

use App\Enums\JenisInovasi;
use App\Enums\TahapanInovasi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('innovations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('inisiator')->nullable()->index();
            $table->foreignId('category_id')->nullable()->constrained('innovation_categories')->nullOnDelete();
            $table->foreignId('assigned_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->longText('latar_belakang')->nullable();
            $table->longText('manfaat')->nullable();
            $table->string('jenis_inovasi')->default(JenisInovasi::NonDigital->value)->index(); // digital, non_digital, teknologi
            $table->string('tahapan')->default(TahapanInovasi::Inisiasi->value)->index();
            $table->string('link_video')->nullable();
            $table->unsignedSmallInteger('tahun');
            $table->string('status')->default('draft');
            // draft, submitted, under_review, reviewed, revision_needed, published, rejected, archived
            $table->string('origin')->default('submission'); // submission, archive
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('testing_at')->nullable();
            $table->timestamp('implementation_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('innovations');
    }
};
