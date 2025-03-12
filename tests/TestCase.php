<?php

namespace WizardingCode\RedisStream\Tests;

use Illuminate\Support\Facades\Redis;
use Orchestra\Testbench\TestCase as BaseTestCase;
use WizardingCode\RedisStream\RedisStreamServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * Clean up the testing environment before the next test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Redis::connection('streams')->flushDB();
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Redis::connection('streams')->flushDB();
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup Redis configuration
        $app['config']->set('redis.default', 'default');
        $app['config']->set('redis.connections.default', [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => 0,
        ]);

        // Setup streams connection
        $app['config']->set('redis.connections.streams', [
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port'     => env('REDIS_PORT', 6379),
            'database' => 1,
            'client'   => env('REDIS_CLIENT', 'phpredis'), // Use phpredis for CI testing
        ]);

        // Setup Redis Streams configuration
        $app['config']->set('redis_stream.stream', env('REDIS_STREAM_NAME', 'test_stream'));
        $app['config']->set('redis_stream.consumer_group', env('REDIS_STREAM_CONSUMER_GROUP', 'test_group'));
        $app['config']->set('redis_stream.consumer_name', env('REDIS_STREAM_CONSUMER_NAME', 'test_consumer'));
        $app['config']->set('redis_stream.max_length', 1000);
        $app['config']->set('redis_stream.poll_interval', 1);
        $app['config']->set('redis_stream.retry_limit', 3);
        $app['config']->set('redis_stream.batch_size', 10);
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            RedisStreamServiceProvider::class,
        ];
    }
}