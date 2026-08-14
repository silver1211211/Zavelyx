<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('number_providers', function (Blueprint $table) {
            $table->json('last_test_result')->nullable()->after('last_synced_at');
            $table->timestamp('last_tested_at')->nullable()->after('last_test_result');
        });
    }

    public function down(): void
    {
        Schema::table('number_providers', function (Blueprint $table) {
            $table->dropColumn(['last_test_result', 'last_tested_at']);
        });
    }
};
