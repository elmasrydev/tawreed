<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 20);
            $table->uuid('reference')->unique();
            $table->string('provider_intention_id')->nullable();
            $table->string('provider_transaction_id')->nullable()->unique();
            $table->decimal('amount_egp', 10, 2);
            $table->decimal('vat_egp', 10, 2)->default(0);
            $table->decimal('total_egp', 10, 2);
            $table->string('status', 20)->default('pending');
            $table->string('method', 30)->nullable();
            $table->string('failure_reason')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
