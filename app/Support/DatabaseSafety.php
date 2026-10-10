<?php

namespace App\Support;

use Illuminate\Contracts\Foundation\Application;
use RuntimeException;

final class DatabaseSafety
{
    private const BLOCKED_PRODUCTION_COMMANDS = ['model:prune', 'schema:dump'];

    public static function enforce(Application $app, array $argv): void
    {
        $command = self::artisanCommand($argv);

        if ($app->environment('production')
            && ($command === 'migrate'
                || str_starts_with((string) $command, 'migrate:')
                || str_starts_with((string) $command, 'db:')
                || in_array($command, self::BLOCKED_PRODUCTION_COMMANDS, true))) {
            throw new RuntimeException(
                "Blocked database maintenance command [{$command}] in production. "
                .'Schema changes require an owner-reviewed, isolated deployment procedure.'
            );
        }

        if (self::isTestProcess($argv)) {
            self::assertIsolatedTestDatabase($app);
        }
    }

    public static function assertIsolatedTestDatabase(Application $app): void
    {
        $connection = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$connection}.database");
        $url = (string) $app['config']->get("database.connections.{$connection}.url", '');
        $confirmed = filter_var($app['config']->get('database.test_database_isolated', false), FILTER_VALIDATE_BOOL);
        $testingDirectory = realpath($app->storagePath('framework/testing')) ?: $app->storagePath('framework/testing');
        $databasePath = $database !== ':memory:' ? realpath($database) : false;
        $safeSqlitePath = $databasePath !== false
            && str_starts_with(
                str_replace('\\', '/', $databasePath),
                rtrim(str_replace('\\', '/', $testingDirectory), '/').'/'
            );

        if (! $app->environment('testing')
            || ! $confirmed
            || $connection !== 'sqlite'
            || $url !== ''
            || ($database !== ':memory:' && ! $safeSqlitePath)) {
            throw new RuntimeException(
                'Test execution blocked: use APP_ENV=testing, ZAVELYX_TEST_DATABASE_ISOLATED=true, '
                .'and SQLite :memory: (or a disposable file under storage/framework/testing).'
            );
        }
    }

    public static function artisanCommand(array $argv): ?string
    {
        foreach ($argv as $index => $argument) {
            if (basename((string) $argument) === 'artisan') {
                return isset($argv[$index + 1]) ? (string) $argv[$index + 1] : null;
            }
        }

        return null;
    }

    private static function isTestProcess(array $argv): bool
    {
        $command = self::artisanCommand($argv);
        $binary = strtolower(basename((string) ($argv[0] ?? '')));

        return $command === 'test'
            || str_contains($binary, 'phpunit')
            || str_contains($binary, 'pest')
            || defined('PHPUNIT_COMPOSER_INSTALL');
    }
}
