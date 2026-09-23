<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name', 120);
            $table->string('author_role_ar', 160)->nullable();
            $table->string('author_role_en', 160)->nullable();
            $table->string('location_ar', 120)->nullable();
            $table->string('location_en', 120)->nullable();
            $table->text('quote_ar');
            $table->text('quote_en');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
