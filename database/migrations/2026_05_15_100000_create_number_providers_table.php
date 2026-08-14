<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('driver')->default('fivesim'); // fivesim | future drivers
            $table->text('credentials');                  // encrypted JSON: {api_key}
            $table->decimal('markup_percent', 8, 4)->default(0); // e.g. 20 = 20%
            $table->unsignedTinyInteger('priority')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_providers');
    }
};
