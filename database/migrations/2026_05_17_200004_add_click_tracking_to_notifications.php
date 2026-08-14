<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('broadcast_id')->nullable()->after('user_id');
            $table->boolean('is_clicked')->default(false)->after('is_read');
            $table->timestamp('clicked_at')->nullable()->after('is_clicked');
            $table->index('broadcast_id');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['broadcast_id']);
            $table->dropColumn(['broadcast_id', 'is_clicked', 'clicked_at']);
        });
    }
};
