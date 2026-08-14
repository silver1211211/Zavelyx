<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->boolean('open_in_new_tab')->default(false)->after('action_label');
        });

        Schema::table('notification_broadcasts', function (Blueprint $table) {
            $table->boolean('open_in_new_tab')->default(false)->after('action_label');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('open_in_new_tab');
        });

        Schema::table('notification_broadcasts', function (Blueprint $table) {
            $table->dropColumn('open_in_new_tab');
        });
    }
};
