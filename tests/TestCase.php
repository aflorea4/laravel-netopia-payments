<?php

namespace Tests;

use Aflorea4\NetopiaPayments\NetopiaPaymentsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
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
            NetopiaPaymentsServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup Netopia config
        $app['config']->set('netopia.signature', 'TEST-SIGNATURE');
        $app['config']->set('netopia.public_key_path', __DIR__ . '/stubs/public.cer');
        $app['config']->set('netopia.private_key_path', __DIR__ . '/stubs/private.key');
        $app['config']->set('netopia.live_mode', false);
        $app['config']->set('netopia.default_currency', 'RON');
    }
}
