<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            // Tracks whether admin has read the latest user message (new ticket or user reply)
            $table->boolean('admin_unread')->default(false);
        });

        // Mark all existing new/user_replied tickets as unread for admin
        DB::table('tickets')
            ->whereIn('status', ['new', 'user_replied'])
            ->update(['admin_unread' => true]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn('admin_unread');
        });
    }
};
