<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories');
            $table->foreignId('unit_id')->constrained();
            $table->foreignId('governorate_id')->constrained();
            $table->string('title');
            $table->text('specs');
            $table->decimal('quantity', 14, 3);
            $table->date('delivery_date');
            $table->string('supply_type', 20)->default('one_time');
            $table->string('recurrence_note')->nullable();
            $table->date('quote_deadline');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('quotes_count')->default(0);
            $table->decimal('best_price', 14, 2)->nullable();
            $table->foreignId('awarded_quote_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'quote_deadline']);
            $table->index(['category_id', 'governorate_id', 'status']);
            $table->index(['buyer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};
