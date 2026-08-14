<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            // Blockchain / on-chain data
            $table->string('blockchain_hash', 128)->nullable()->after('gateway_payment_id');
            $table->string('network', 40)->nullable()->after('pay_currency');

            // Actual received amounts (crypto gateway reports what actually arrived)
            $table->decimal('amount_received', 28, 18)->nullable()->after('pay_amount');
            $table->decimal('usd_value', 18, 8)->nullable()->after('amount_received');

            // Webhook / callback metadata
            $table->string('ip_address', 45)->nullable()->after('gateway_payload');
            $table->timestamp('callback_received_at')->nullable()->after('credited_at');
            $table->timestamp('processed_at')->nullable()->after('callback_received_at');

            // Retry tracking
            $table->unsignedSmallInteger('retry_count')->default(0)->after('processed_at');
            $table->text('failure_reason')->nullable()->after('retry_count');

            // Composite indexes for admin queries
            $table->index(['gateway', 'status'], 'pi_gateway_status');
            $table->index(['status', 'credited_at'], 'pi_status_credited');
            $table->index('callback_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            $table->dropIndex('pi_gateway_status');
            $table->dropIndex('pi_status_credited');
            $table->dropIndex(['callback_received_at']);

            $table->dropColumn([
                'blockchain_hash',
                'network',
                'amount_received',
                'usd_value',
                'ip_address',
                'callback_received_at',
                'processed_at',
                'retry_count',
                'failure_reason',
            ]);
        });
    }
};
