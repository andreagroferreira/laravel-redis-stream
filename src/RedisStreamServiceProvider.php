<?php

namespace RedisStream;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class RedisStreamServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the main producer class
        $this->app->singleton(RedisStreamProducer::class, function ($app) {
            return new RedisStreamProducer(
                config('redis_stream.stream'),
                config('redis_stream.max_length'),
                config('redis_stream.use_exact_maxlen', false)
            );
        });

        // Register the consumer class
        $this->app->singleton(RedisStreamConsumer::class, function ($app) {
            return new RedisStreamConsumer(
                config('redis_stream.stream'),
                config('redis_stream.consumer_group'),
                config('redis_stream.consumer_name'),
                config('redis_stream.poll_interval', 1),
                config('redis_stream.retry_limit', 3),
                config('redis_stream.batch_size', 1)
            );
        });

        // Register custom stream producers
        $this->registerStreamProducers();

        // Merge the package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/redis_stream.php',
            'redis_stream'
        );

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ConsumeStreamCommand::class,
                Console\MakeProducerCommand::class,
                Console\MakeConsumerCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__ . '/../config/redis_stream.php' => config_path('redis_stream.php'),
        ], 'redis-stream-config');

        // Register scheduled tasks
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            // Add scheduled tasks here if needed
        });
    }

    /**
     * Register custom stream producers based on config
     *
     * @return void
     */
    protected function registerStreamProducers(): void
    {
        // Get custom streams from config
        $customStreams = collect(config('redis_stream'))->filter(function ($value, $key) {
            return str_starts_with($key, 'stream_') && is_string($value);
        });

        // Register each custom stream as a named singleton
        foreach ($customStreams as $key => $streamName) {
            $this->app->singleton("redis_stream.producer.$key", function ($app) use ($streamName) {
                return new RedisStreamProducer(
                    $streamName,
                    config('redis_stream.max_length'),
                    config('redis_stream.use_exact_maxlen', false)
                );
            });
        }
    }
}
