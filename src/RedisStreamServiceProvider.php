<?php

namespace RedisStream;

use Illuminate\Support\ServiceProvider;

class RedisStreamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RedisStreamProducer::class, function ($app) {
            return new RedisStreamProducer(config('redis_stream.stream'));
        });

        $this->app->singleton(RedisStreamConsumer::class, function ($app) {
            return new RedisStreamConsumer(
                config('redis_stream.stream'),
                config('redis_stream.consumer_group'),
                config('redis_stream.consumer_name')
            );
        });

        $this->mergeConfigFrom(
            __DIR__ . '/../config/redis_stream.php',
            'redis_stream'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/redis_stream.php' => config_path('redis_stream.php'),
        ]);
    }
}
