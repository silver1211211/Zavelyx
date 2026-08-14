<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Refund tracking — null means no refund issued yet
            $table->string('refund_status', 20)->nullable()->after('remains');
            $table->decimal('refund_amount', 20, 8)->nullable()->after('refund_status');
            $table->timestamp('refunded_at')->nullable()->after('refund_amount');

            // Last time this order was polled from the provider API
            $table->timestamp('last_synced_at')->nullable()->after('refunded_at');

            // Efficient lookup for refund processor: "find canceled/partial with no refund yet"
            $table->index(['status', 'refund_status'], 'orders_status_refund_idx');
            $table->index('last_synced_at', 'orders_last_synced_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_refund_idx');
            $table->dropIndex('orders_last_synced_idx');
            $table->dropColumn(['refund_status', 'refund_amount', 'refunded_at', 'last_synced_at']);
        });
    }
};
