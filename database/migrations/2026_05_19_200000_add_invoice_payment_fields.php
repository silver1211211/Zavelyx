<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            // Invoice-specific fields for coin-locked invoices
            $table->string('gateway_type', 20)->nullable()->after('gateway');      // 'hosted' | 'invoice'
            $table->string('qr_code_url', 500)->nullable()->after('payment_url'); // QR from gateway or generated
            $table->timestamp('expires_at')->nullable()->after('qr_code_url');    // invoice expiry
            $table->unsignedSmallInteger('confirmations')->default(0)->after('expires_at');

            $table->index('gateway_type');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            $table->dropIndex(['gateway_type']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['gateway_type', 'qr_code_url', 'expires_at', 'confirmations']);
        });
    }
};
