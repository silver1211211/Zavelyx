<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // credit | debit
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->string('note')->nullable();
            $table->string('admin_user')->default('admin'); // who made the adjustment
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_adjustments');
    }
};
