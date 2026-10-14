<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('client_type', 120)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->timestamps();

            $table->index(['supplier_profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
    }
};
