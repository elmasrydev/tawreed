<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('business_type_id')->constrained();
            $table->foreignId('governorate_id')->constrained();
            $table->string('company_name');
            $table->string('company_address')->nullable();
            $table->string('job_title')->nullable();
            $table->string('commercial_reg_no')->nullable();
            $table->string('tax_card_no')->nullable();
            $table->timestamps();

            $table->index(['business_type_id', 'governorate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_profiles');
    }
};
