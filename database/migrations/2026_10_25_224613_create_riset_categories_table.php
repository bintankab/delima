<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('riset_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::table('risets', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        DB::table('risets')->update(['category_id' => null]);

        Schema::table('risets', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('riset_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('risets', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        DB::table('risets')->update(['category_id' => null]);

        Schema::table('risets', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('innovation_categories')->nullOnDelete();
        });

        Schema::dropIfExists('riset_categories');
    }
};
