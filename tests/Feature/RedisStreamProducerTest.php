<?php

use WizardingCode\RedisStream\RedisStreamProducer;
use WizardingCode\RedisStream\Exceptions\PublishException;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    // Create a clean Redis stream for testing
    $testStream = config('redis_stream.stream');
    Redis::connection('streams')->del($testStream);
});

afterEach(function () {
    // Clean up after tests
    $testStream = config('redis_stream.stream');
    Redis::connection('streams')->del($testStream);
});

it('can publish a message to the stream', function () {
    // Arrange
    $producer = new RedisStreamProducer(config('redis_stream.stream'));
    $event = 'test.event';
    $payload = ['test' => 'data'];
    
    // Act
    $messageId = $producer->publish($event, $payload);
    
    // Assert
    expect($messageId)->toBeString();
    
    // Verify the message in Redis
    $messages = Redis::connection('streams')->xrange(config('redis_stream.stream'), '-', '+');
    expect($messages)->toHaveCount(1);
    
    $message = json_decode($messages[$messageId]['message'], true);
    expect($message)->toHaveKey('event');
    expect($message)->toHaveKey('payload');
    expect($message)->toHaveKey('timestamp');
    expect($message['event'])->toBe($event);
    expect($message['payload'])->toBe($payload);
});

it('can publish a batch of messages', function () {
    // Arrange
    $producer = new RedisStreamProducer(config('redis_stream.stream'));
    $messages = [
        [
            'event' => 'test.event1',
            'payload' => ['id' => 1]
        ],
        [
            'event' => 'test.event2',
            'payload' => ['id' => 2]
        ],
        [
            'event' => 'test.event3',
            'payload' => ['id' => 3]
        ],
    ];
    
    // Act
    $messageIds = $producer->publishBatch($messages);
    
    // Assert
    expect($messageIds)->toBeArray();
    expect($messageIds)->toHaveCount(3);
    
    // Verify the messages in Redis
    $redisMessages = Redis::connection('streams')->xrange(config('redis_stream.stream'), '-', '+');
    expect($redisMessages)->toHaveCount(3);
});

it('trims stream to max length', function () {
    // Arrange
    $maxLen = 5;
    $producer = new RedisStreamProducer(config('redis_stream.stream'), $maxLen);
    
    // Act - Publish 10 messages
    for ($i = 0; $i < 10; $i++) {
        $producer->publish('test.event', ['id' => $i]);
    }
    
    // Assert - Stream should have approximately maxLen messages
    $messages = Redis::connection('streams')->xrange(config('redis_stream.stream'), '-', '+');
    
    // Since MAXLEN ~ is approximate, we check if it's close to the target
    expect(count($messages))->toBeLessThanOrEqual($maxLen + 2);
});

it('can manually trim stream', function () {
    // Arrange
    $producer = new RedisStreamProducer(config('redis_stream.stream'));
    
    // Add 10 messages
    for ($i = 0; $i < 10; $i++) {
        $producer->publish('test.event', ['id' => $i]);
    }
    
    // Act
    $deleted = $producer->trim(5, true); // Exact trim
    
    // Assert
    expect($deleted)->toBeGreaterThanOrEqual(5);
    
    $messages = Redis::connection('streams')->xrange(config('redis_stream.stream'), '-', '+');
    expect($messages)->toHaveCount(5);
});

it('throws exception with invalid batch data', function () {
    // Arrange
    $producer = new RedisStreamProducer(config('redis_stream.stream'));
    $invalidMessages = [
        [
            // Missing 'event' key
            'payload' => ['id' => 1]
        ]
    ];
    
    // Act & Assert
    expect(fn() => $producer->publishBatch($invalidMessages))
        ->toThrow(\InvalidArgumentException::class);
});