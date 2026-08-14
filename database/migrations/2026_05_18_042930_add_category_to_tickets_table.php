<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('category')->default('general')->after('subject');
            $table->unsignedBigInteger('assigned_to')->nullable()->after('category');
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('last_replied_at')->nullable()->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['category', 'assigned_to', 'last_replied_at']);
        });
    }
};
