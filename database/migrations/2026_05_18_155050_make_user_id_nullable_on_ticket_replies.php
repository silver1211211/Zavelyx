<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table): void {
            // Allow null user_id for staff/system replies that don't have a linked user account
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
