<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add new columns to tickets ───────────────────────────────────
        Schema::table('tickets', function (Blueprint $table): void {
            $table->boolean('pinned')->default(false)->after('last_replied_at');
            $table->timestamp('first_response_at')->nullable()->after('pinned');
            $table->timestamp('resolved_at')->nullable()->after('first_response_at');
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
            $table->timestamp('admin_viewed_at')->nullable()->after('closed_at');
        });

        // ── 2. Add is_internal flag to ticket_replies ───────────────────────
        Schema::table('ticket_replies', function (Blueprint $table): void {
            $table->boolean('is_internal')->default(false)->after('is_staff');
        });

        // ── 3. Migrate existing status values to new workflow names ─────────
        DB::table('tickets')->where('status', 'open')->update(['status' => 'new']);
        DB::table('tickets')->where('status', 'in_progress')->update(['status' => 'in_review']);
        DB::table('tickets')->where('status', 'waiting')->update(['status' => 'waiting_for_user']);

        // ── 4. Create ticket_events (timeline) table ────────────────────────
        Schema::create('ticket_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('type', 64);             // created, viewed, admin_replied, …
            $table->string('actor_type', 16)->default('system'); // user | admin | system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->text('description');
            $table->json('metadata')->nullable();    // {"old":"new","new":"in_review"} etc.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_events');

        Schema::table('ticket_replies', function (Blueprint $table): void {
            $table->dropColumn('is_internal');
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['pinned', 'first_response_at', 'resolved_at', 'closed_at', 'admin_viewed_at']);
        });

        DB::table('tickets')->where('status', 'new')->update(['status' => 'open']);
        DB::table('tickets')->where('status', 'in_review')->update(['status' => 'in_progress']);
        DB::table('tickets')->where('status', 'waiting_for_user')->update(['status' => 'waiting']);
    }
};
