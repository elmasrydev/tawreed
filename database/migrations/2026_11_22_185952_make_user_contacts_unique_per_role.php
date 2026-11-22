<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One person may hold a buyer account and a supplier account with the same
 * email and phone, so both are unique per role rather than globally.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropUnique(['phone']);

            $table->unique(['email', 'role']);
            $table->unique(['phone', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email', 'role']);
            $table->dropUnique(['phone', 'role']);

            $table->unique('email');
            $table->unique('phone');
        });
    }
};
