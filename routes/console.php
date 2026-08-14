<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Order lifecycle — runs every minute ────────────────────────────────────────

// Poll provider APIs → update pending/processing statuses
Schedule::command('orders:update --limit=200')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/orders-update.log'));

// Issue refunds for canceled/partial/failed orders
Schedule::command('orders:refund --limit=200')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/orders-refund.log'));

// ── Number (SMS/OTP) orders sync ───────────────────────────────────────────────

// Poll 5SIM / number providers for new SMS and status updates
Schedule::command('numbers:sync --limit=200')
    ->everyMinute()
    ->withoutOverlapping(3)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/numbers-sync.log'));

// ── Provider management ────────────────────────────────────────────────────────

// Import/update services from all active providers every 6 hours
Schedule::command('providers:sync')
    ->everySixHours()
    ->withoutOverlapping(30)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/providers-sync.log'));

// Health-check all providers every 30 min; auto-deactivates failing ones
Schedule::command('providers:check')
    ->everyThirtyMinutes()
    ->withoutOverlapping(10)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/providers-check.log'));

// ── Deposit safety net — polls gateways for missed webhooks ──────────────────

// Runs every minute; catches any invoice whose webhook was missed or unreachable.
// --minutes=2 means: only check invoices created ≥ 2 min ago (new invoices are
// covered by frontend live-polling; this command is the offline/closed-tab safety net).
Schedule::command('deposits:poll --minutes=2 --limit=100')
    ->everyMinute()
    ->withoutOverlapping(2)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/deposits-poll.log'));

// ── Currency exchange rates ────────────────────────────────────────────────────

// Sync live exchange rates from open.er-api.com (only runs if live_rates_enabled=1)
Schedule::command('currencies:sync')
    ->everyThirtyMinutes()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/currencies-sync.log'));
