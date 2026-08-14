<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('provider_order_id')->nullable()->after('status')->index();
            $table->unsignedBigInteger('quantity')->nullable()->after('provider_order_id');
            $table->string('link', 1000)->nullable()->after('quantity');
            $table->unsignedBigInteger('start_count')->nullable()->after('link');
            $table->unsignedBigInteger('remains')->nullable()->after('start_count');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['provider_order_id', 'quantity', 'link', 'start_count', 'remains']);
        });
    }
};
