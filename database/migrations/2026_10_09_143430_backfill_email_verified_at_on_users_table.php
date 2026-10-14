<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The one-time code now verifies the email address instead of the phone.
     * Accounts that already passed the phone check keep their access.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->whereNotNull('phone_verified_at')
            ->update(['email_verified_at' => DB::raw('phone_verified_at')]);
    }

    public function down(): void
    {
        //
    }
};
