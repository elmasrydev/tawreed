<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->decimal('amount_egp', 10, 2);
            $table->decimal('vat_egp', 10, 2)->default(0);
            $table->decimal('total_egp', 10, 2);
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['supplier_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
