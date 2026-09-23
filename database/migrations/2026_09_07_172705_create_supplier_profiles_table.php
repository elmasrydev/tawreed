<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('commercial_reg_no');
            $table->string('tax_number');
            $table->string('facility_address')->nullable();
            $table->text('activity_description')->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedSmallInteger('years_active')->nullable();
            $table->string('verification_status', 20)->default('pending');
            $table->text('verification_note')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('completed_deals_count')->default(0);
            $table->timestamps();

            $table->index('verification_status');
        });

        Schema::create('supplier_governorate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();

            $table->unique(['supplier_profile_id', 'governorate_id'], 'supplier_governorate_unique');
        });

        Schema::create('category_supplier_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->unique(['supplier_profile_id', 'category_id'], 'supplier_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_supplier_profile');
        Schema::dropIfExists('supplier_governorate');
        Schema::dropIfExists('supplier_profiles');
    }
};
