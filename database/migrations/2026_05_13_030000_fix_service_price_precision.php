<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->decimal('cost_price', 18, 8)->default(0)->change();
            $table->decimal('selling_price', 18, 8)->default(0)->change();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('amount', 18, 8)->change();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->decimal('cost_price', 18, 2)->default(0)->change();
            $table->decimal('selling_price', 18, 2)->default(0)->change();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('amount', 18, 2)->change();
        });
    }
};
