<?php

use App\Support\DatabaseSafety;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;

function safetyApplication(string $environment, array $databaseConfig = []): Application
{
    $app = new Application(dirname(__DIR__, 2));
    $app->instance('env', $environment);
    $app->instance('config', new Repository(['database' => $databaseConfig]));

    return $app;
}

it('blocks migration and destructive database commands in production', function ($command) {
    $app = safetyApplication('production');

    expect(fn () => DatabaseSafety::enforce($app, ['artisan', $command]))
        ->toThrow(RuntimeException::class, 'Blocked database maintenance command');
})->with(['migrate', 'migrate:fresh', 'migrate:rollback', 'db:show', 'db:wipe', 'db:seed', 'model:prune', 'schema:dump']);

it('allows non destructive application commands in production', function () {
    $app = safetyApplication('production');

    DatabaseSafety::enforce($app, ['artisan', 'orders:update']);

    expect(true)->toBeTrue();
});

it('accepts only explicitly confirmed in-memory sqlite for tests', function () {
    $safe = safetyApplication('testing', [
        'default' => 'sqlite',
        'test_database_isolated' => true,
        'connections' => ['sqlite' => ['database' => ':memory:', 'url' => '']],
    ]);
    DatabaseSafety::assertIsolatedTestDatabase($safe);

    $unsafe = safetyApplication('testing', [
        'default' => 'mysql',
        'test_database_isolated' => true,
        'connections' => ['mysql' => ['database' => 'production', 'url' => 'mysql://production']],
    ]);

    expect(fn () => DatabaseSafety::assertIsolatedTestDatabase($unsafe))
        ->toThrow(RuntimeException::class, 'Test execution blocked');
});
