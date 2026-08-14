<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The json column type rejects encrypted values (not valid JSON).
        // Change to text so encrypted:array cast can store the cipher string.
        Schema::table('providers', function (Blueprint $table): void {
            $table->text('credentials')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table): void {
            $table->json('credentials')->nullable()->change();
        });
    }
};
