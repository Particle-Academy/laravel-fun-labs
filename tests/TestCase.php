<?php

declare(strict_types=1);

namespace LaravelFunLab\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelFunLab\LFLServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base TestCase for Laravel Fun Lab package tests.
 *
 * Uses Orchestra Testbench to provide a Laravel testing environment
 * for the package without requiring a full Laravel application.
 */
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LFLServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'LFL' => \LaravelFunLab\Facades\LFL::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use SQLite memory
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // LFL config defaults
        $app['config']->set('lfl.table_prefix', 'lfl_');
        $app['config']->set('lfl.migrations', true);
        $app['config']->set('lfl.events.dispatch', true);
    }

    /**
     * Set up the database with test tables.
     */
    protected function setUpDatabase(): void
    {
        // Create a test users table for the Awardable trait tests
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }
}
