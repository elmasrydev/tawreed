<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('buyer')->after('email');
            $table->string('phone', 20)->nullable()->unique()->after('role');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('locale', 5)->default('ar')->after('password');
            $table->string('status', 20)->default('active')->after('locale');
            $table->timestamp('last_seen_at')->nullable()->after('status');
            $table->softDeletes();

            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'status']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'role', 'phone', 'phone_verified_at', 'locale', 'status', 'last_seen_at',
            ]);
        });
    }
};
