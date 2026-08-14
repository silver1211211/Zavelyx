<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DB::listen(function (QueryExecuted $query): void {
            if ($query->time < 750) {
                return;
            }

            Log::warning('Slow database query', [
                'time_ms' => $query->time,
                'sql' => $query->sql,
                'bindings' => $query->bindings,
            ]);
        });

        if ($this->app->environment('production') && parse_url(config('app.url'), PHP_URL_SCHEME) === 'https') {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);
    }
}
