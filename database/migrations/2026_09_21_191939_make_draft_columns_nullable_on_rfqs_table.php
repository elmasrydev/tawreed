<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A buyer may save an unfinished request as a draft with only a title, so the
 * fields the wizard asks for later must accept nulls. Publishing still
 * validates every field, so open requests are always complete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->change();
            $table->foreignId('unit_id')->nullable()->change();
            $table->foreignId('governorate_id')->nullable()->change();
            $table->text('specs')->nullable()->change();
            $table->decimal('quantity', 14, 3)->nullable()->change();
            $table->date('delivery_date')->nullable()->change();
            $table->date('quote_deadline')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
            $table->foreignId('unit_id')->nullable(false)->change();
            $table->foreignId('governorate_id')->nullable(false)->change();
            $table->text('specs')->nullable(false)->change();
            $table->decimal('quantity', 14, 3)->nullable(false)->change();
            $table->date('delivery_date')->nullable(false)->change();
            $table->date('quote_deadline')->nullable(false)->change();
        });
    }
};
