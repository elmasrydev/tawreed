<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('status', 20)->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['supplier_profile_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_documents');
    }
};
