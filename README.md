# Redis Stream Package for Laravel

A powerful, reliable Redis Streams implementation for Laravel applications. This package makes it easy to work with Redis Streams for event-driven applications, message processing, and real-time data pipelines.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/andreagroferreira/redis-stream.svg?style=flat-square)](https://packagist.org/packages/andreagroferreira/redis-stream)
[![Total Downloads](https://img.shields.io/packagist/dt/andreagroferreira/redis-stream.svg?style=flat-square)](https://packagist.org/packages/andreagroferreira/redis-stream)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![PHP Version Support](https://img.shields.io/packagist/php-v/andreagroferreira/redis-stream.svg?style=flat-square)](https://packagist.org/packages/andreagroferreira/redis-stream)
[![Laravel Version Support](https://img.shields.io/badge/Laravel-9.x%20|%2010.x%20|%2012.x-brightgreen.svg?style=flat-square)](https://packagist.org/packages/andreagroferreira/redis-stream)
[![GitHub forks](https://img.shields.io/github/forks/andreagroferreira/laravel-redis-stream.svg?style=social&label=Fork&maxAge=2592000)](https://github.com/andreagroferreira/laravel-redis-stream)
[![GitHub stars](https://img.shields.io/github/stars/andreagroferreira/laravel-redis-stream.svg?style=social&label=Star&maxAge=2592000)](https://github.com/andreagroferreira/laravel-redis-stream)

## Features

- ✅ Proper exception handling with typed exceptions

- 🚀 Simple producer/consumer API for Redis Streams
- ♻️ Compatible with both phpredis and predis drivers
- 🔄 Automatic consumer group management
- 🔁 Retry handling for failed messages
- 📊 Batch publishing support for high-throughput applications
- 🛑 Graceful shutdown support
- ⚙️ Configurable stream trimming (MAXLEN)
- 📟 Built-in Artisan command for consuming streams

## Installation

You can install the package via composer:

```bash
composer require andreagroferreira/redis-stream
```

### Redis Driver Requirements

This package supports both `phpredis` (the PHP extension) and `predis` (PHP library) drivers:

**For PHP Redis Extension (recommended for production):**
```bash
# Install the PHP Redis Extension
pecl install redis
# Add "extension=redis.so" to your php.ini
```

**For Predis Library:**
```bash
# Install Predis library
composer require predis/predis
```

The package will automatically detect which driver you're using.

After installation, publish the configuration file:

```bash
php artisan vendor:publish --provider="WizardingCode\RedisStream\RedisStreamServiceProvider"
```

## Configuration

Configure your Redis connection for streams in your `.env` file:

```
REDIS_STREAM_CONNECTION=streams
REDIS_STREAM_NAME=default_stream
REDIS_STREAM_CONSUMER_GROUP=default_group
REDIS_STREAM_CONSUMER_NAME=default_consumer
REDIS_STREAM_MAX_LENGTH=1000000
REDIS_STREAM_POLL_INTERVAL=1
REDIS_STREAM_RETRY_LIMIT=3
REDIS_STREAM_BATCH_SIZE=10
```

You can also define custom streams in the `redis_stream.php` config file.

## Usage

### Exception Handling

This package provides specific exception types for better error handling:

- `RedisStreamException`: Base exception class for all Redis Stream errors
- `ConnectionException`: Thrown when Redis connection fails
- `PublishException`: Thrown when message publishing fails
- `ConsumeException`: Thrown when message consumption fails
- `MessageProcessingException`: Thrown when message processing fails

Example of handling exceptions:

```php
use WizardingCode\RedisStream\Exceptions\ConnectionException;
use WizardingCode\RedisStream\Exceptions\PublishException;

try {
    $producer->publish('user.created', $userData);
} catch (ConnectionException $e) {
    // Handle connection issues
    Log::error("Redis connection error: " . $e->getMessage());
    // Maybe retry or queue for later
} catch (PublishException $e) {
    // Handle publishing issues
    Log::error("Failed to publish message: " . $e->getMessage());
} catch (Exception $e) {
    // Handle other errors
    Log::error("Unexpected error: " . $e->getMessage());
}
```

### Basic Example

```php
<?php

// Publishing messages
$producer = app(WizardingCode\RedisStream\RedisStreamProducer::class);
$messageId = $producer->publish('user.created', [
    'user_id' => 1234,
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Consuming messages (in a console command or job)
$consumer = app(WizardingCode\RedisStream\RedisStreamConsumer::class);
$consumer->consume(function($data, $messageId) {
    $event = $data['event'];
    $payload = $data['payload'];
    
    // Process the message
    match($event) {
        'user.created' => $this->processUserCreation($payload),
        'user.updated' => $this->processUserUpdate($payload),
        default => $this->processUnknownEvent($event, $payload)
    };
    
    // Message is auto-acknowledged if no exception is thrown
});
```

### Using the Artisan Command

```bash
# Basic usage with default settings
php artisan redis-stream:consume

# Advanced usage with all options
php artisan redis-stream:consume \
    --stream=my_stream \
    --group=my_group \
    --consumer=consumer1 \
    --handler="App\\Handlers\\MyStreamHandler" \
    --interval=5 \
    --batch=50 \
    --retries=5
```

### Creating a Custom Handler

```php
<?php

namespace App\Handlers;

class MyStreamHandler
{
    public function handle(array $data, string $messageId): void
    {
        $event = $data['event'];
        $payload = $data['payload'];
        
        // Your custom handling logic
        logger()->info("Processing event: {$event}");
        
        // Process based on event type
        match($event) {
            'order.created' => $this->processOrder($payload),
            'payment.completed' => $this->processPayment($payload),
            default => $this->handleUnknown($event, $payload)
        };
    }
    
    protected function processOrder(array $data): void
    {
        // Process order logic
    }
    
    protected function processPayment(array $data): void
    {
        // Process payment logic
    }
    
    protected function handleUnknown(string $event, array $data): void
    {
        logger()->warning("Unknown event type: {$event}");
    }
}
```

### Batch Publishing

For high-throughput scenarios, you can publish messages in batches:

```php
$producer = app(WizardingCode\RedisStream\RedisStreamProducer::class);

$messages = [
    [
        'event' => 'user.created',
        'payload' => ['user_id' => 1, 'name' => 'User 1'],
    ],
    [
        'event' => 'user.created',
        'payload' => ['user_id' => 2, 'name' => 'User 2'],
    ],
    [
        'event' => 'user.created',
        'payload' => ['user_id' => 3, 'name' => 'User 3'],
    ],
];

$messageIds = $producer->publishBatch($messages);
```

### Stream Trimming

To manage stream size:

```php
$producer = app(WizardingCode\RedisStream\RedisStreamProducer::class);

// Trim to approximately 10,000 items (fast)
$deleted = $producer->trim(10000);

// Trim to exactly 10,000 items (slower)
$deleted = $producer->trim(10000, true);
```

## Creating Custom Stream Producers

You can access named stream producers defined in your config:

```php
// Get a specific producer for a named stream
$ordersProducer = app('redis_stream.producer.stream_orders');
$ordersProducer->publish('order.created', ['order_id' => 12345]);
```

## Generating Custom Producers and Consumers

This package provides Artisan commands to quickly scaffold custom producer and consumer classes:

### Creating a Producer

```bash
php artisan redis-stream:make-producer OrderProducer --stream=orders
```

This will generate an `OrderProducer` class that extends the base functionality of `RedisStreamProducer`.

### Creating a Consumer

```bash
php artisan redis-stream:make-consumer OrderConsumer --stream=orders --group=orders_processing --command
```

The `--command` flag will also generate a dedicated Artisan command to run this consumer:

```bash
php artisan redis-stream:orders
```

### Consumer Implementation Example

After generating a consumer, you can customize the `handleMessage` method to process specific event types:

```php
protected function handleMessage(array $data, string $messageId): void
{
    $event = $data['event'] ?? 'unknown';
    $payload = $data['payload'] ?? [];
    
    Log::info("Processing {$event} message {$messageId}");
    
    try {
        // Handle different event types
        match ($event) {
            'order.created' => $this->processNewOrder($payload),
            'order.updated' => $this->processOrderUpdate($payload),
            'order.cancelled' => $this->processOrderCancellation($payload),
            default => $this->handleUnknownEvent($event, $payload, $messageId),
        };
    } catch (\Exception $e) {
        Log::error("Failed to process {$event} message {$messageId}: " . $e->getMessage());
        throw $e; // Rethrow to let the consumer handle retries
    }
}

private function processNewOrder(array $orderData): void
{
    // Process new order logic
}
```

## Testing

This package uses Pest PHP for testing:

```bash
composer test
```

For code coverage:

```bash
composer test-coverage
```

> **Note**: To run the full test suite including feature tests, you need to have Redis installed and configured locally. The feature tests interact with an actual Redis server to verify the Redis Stream functionality.

The tests include both unit tests for exceptions and feature tests for Redis Stream interactions. In our CI setup, we only verify basic functionality to avoid Redis configuration issues in automated environments.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](SECURITY.md) on how to report security vulnerabilities.

## Credits

- [André Ferreira](https://github.com/andreagroferreira)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
