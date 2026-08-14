<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table): void {
            $table->string('markup_type')->default('percentage')->after('priority');
            $table->decimal('markup_value', 10, 4)->default(0)->after('markup_type');
            $table->string('balance')->nullable()->after('markup_value');
            $table->timestamp('last_synced_at')->nullable()->after('balance');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table): void {
            $table->dropColumn(['markup_type', 'markup_value', 'balance', 'last_synced_at']);
        });
    }
};
