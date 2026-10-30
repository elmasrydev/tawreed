<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buyers now give a request a title and name the product separately.
     * Existing requests were entered under a "Product name" label, so their
     * title is also their product name.
     */
    public function up(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('title');
        });

        DB::table('rfqs')->update(['product_name' => DB::raw('title')]);
    }

    public function down(): void
    {
        Schema::table('rfqs', function (Blueprint $table) {
            $table->dropColumn('product_name');
        });
    }
};
