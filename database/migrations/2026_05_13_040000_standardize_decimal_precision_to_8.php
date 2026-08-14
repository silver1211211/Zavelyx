<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wallets
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('balance', 20, 8)->default(0)->change();
            $table->decimal('ledger_balance', 20, 8)->default(0)->change();
        });

        // Transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 20, 8)->change();
            $table->decimal('balance_before', 20, 8)->change();
            $table->decimal('balance_after', 20, 8)->change();
        });

        // Balance adjustments
        Schema::table('balance_adjustments', function (Blueprint $table) {
            $table->decimal('amount', 20, 8)->change();
            $table->decimal('balance_before', 20, 8)->change();
            $table->decimal('balance_after', 20, 8)->change();
        });

        // Services (standardize to 20,8)
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('cost_price', 20, 8)->nullable()->change();
            $table->decimal('selling_price', 20, 8)->change();
        });

        // Orders
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('amount', 20, 8)->change();
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('balance', 18, 2)->default(0)->change();
            $table->decimal('ledger_balance', 18, 2)->default(0)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 18, 2)->change();
            $table->decimal('balance_before', 18, 2)->change();
            $table->decimal('balance_after', 18, 2)->change();
        });

        Schema::table('balance_adjustments', function (Blueprint $table) {
            $table->decimal('amount', 18, 2)->change();
            $table->decimal('balance_before', 18, 2)->change();
            $table->decimal('balance_after', 18, 2)->change();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->decimal('cost_price', 18, 8)->nullable()->change();
            $table->decimal('selling_price', 18, 8)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('amount', 18, 8)->change();
        });
    }
};
