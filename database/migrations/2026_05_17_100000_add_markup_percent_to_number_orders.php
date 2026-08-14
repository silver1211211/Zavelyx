<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('number_orders', function (Blueprint $table) {
            // Snapshot the markup % at purchase time so admin can see exact profit breakdown
            $table->decimal('markup_percent', 8, 4)->default(0)->after('provider_cost');
        });
    }

    public function down(): void
    {
        Schema::table('number_orders', function (Blueprint $table) {
            $table->dropColumn('markup_percent');
        });
    }
};
