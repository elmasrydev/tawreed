<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total_price', 14, 2);
            $table->decimal('min_order_qty', 14, 3)->nullable();
            $table->string('vat_mode', 20)->default('included');
            $table->decimal('vat_amount', 14, 2)->nullable();
            $table->decimal('delivery_cost', 14, 2)->default(0);
            $table->date('expected_delivery_date');
            $table->unsignedSmallInteger('validity_days')->default(7);
            $table->string('payment_terms')->nullable();
            $table->string('sample_availability')->nullable();
            $table->string('brand_origin')->nullable();
            $table->text('extra_specs')->nullable();
            $table->text('warranty_policy')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('rejection_reason_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('selected_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['rfq_id', 'supplier_id']);
            $table->index(['supplier_id', 'status']);
            $table->index(['rfq_id', 'status']);
        });

        Schema::table('rfqs', function (Blueprint $table) {
            $table->foreign('awarded_quote_id')->references('id')->on('quotes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropForeign(['awarded_quote_id']);
        });

        Schema::dropIfExists('quotes');
    }
};
