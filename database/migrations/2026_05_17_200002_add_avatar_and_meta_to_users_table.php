<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('phone', 32)->nullable()->after('avatar');
            $table->string('country', 64)->nullable()->after('phone');
            $table->string('timezone', 64)->nullable()->after('country');
            $table->enum('account_level', ['basic', 'verified', 'premium', 'vip'])->default('basic')->after('timezone');
            $table->timestamp('last_active_at')->nullable()->after('account_level');
            $table->json('preferences')->nullable()->after('last_active_at'); // theme, currency, notif prefs, etc.
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'phone', 'country', 'timezone', 'account_level', 'last_active_at', 'preferences']);
        });
    }
};
