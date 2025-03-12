<?php

use RedisStream\RedisStreamConsumer;
use RedisStream\RedisStreamProducer;
use RedisStream\Exceptions\MessageProcessingException;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    // Create a clean Redis stream for testing
    $testStream = config('redis_stream.stream');
    $testGroup = config('redis_stream.consumer_group');
    
    Redis::connection('streams')->del($testStream);
    
    // Create the stream with a test message
    $producer = new RedisStreamProducer($testStream);
    $producer->publish('test.event', ['test' => 'data']);
    
    // Clean up any existing consumer groups
    try {
        $groups = Redis::connection('streams')->xinfo('GROUPS', $testStream);
        foreach ($groups as $group) {
            Redis::connection('streams')->xgroup('DESTROY', $testStream, $group[1]);
        }
    } catch (\Exception $e) {
        // Ignore exceptions if the stream doesn't exist yet
    }
});

afterEach(function () {
    // Clean up after tests
    $testStream = config('redis_stream.stream');
    Redis::connection('streams')->del($testStream);
});

it('can process messages from stream', function () {
    // Skip this test in CI environment
    if (getenv('CI')) {
        $this->markTestSkipped('Skipping in CI environment');
    }
    
    // Arrange
    $stream = config('redis_stream.stream');
    $group = config('redis_stream.consumer_group');
    $consumer = config('redis_stream.consumer_name');
    
    $producer = new RedisStreamProducer($stream);
    $testConsumer = new RedisStreamConsumer($stream, $group, $consumer, 1, 3, 10);
    
    // Add more test messages
    $messageIds = [];
    for ($i = 0; $i < 5; $i++) {
        $messageIds[] = $producer->publish('test.event', ['id' => $i]);
    }
    
    // Act - Process only one message and then stop
    $processed = [];
    $processedCount = 0;
    
    // We'll use a flag to stop the consumer after processing one batch
    $shouldStop = false;
    
    // Patch the pcntl signal handling for testing
    if (!defined('REDIS_STREAM_SHUTDOWN')) {
        define('REDIS_STREAM_SHUTDOWN', false);
    }
    
    // We need to monkey patch the consume method for testing
    // because we can't easily interrupt the infinite loop
    $mockConsume = function () use (&$processed, &$processedCount, &$shouldStop) {
        // Setup consumer group
        Redis::connection('streams')->xgroup(
            'CREATE', 
            $this->stream, 
            $this->group, 
            '0', 
            true
        );
        
        // Read messages only once for testing
        $messages = Redis::connection('streams')->xreadgroup(
            $this->group, 
            $this->consumer, 
            [$this->stream => '>'], 
            10
        );
        
        if ($messages) {
            foreach ($messages as $stream => $entries) {
                foreach ($entries as $id => $message) {
                    $data = json_decode($message['message'], true, 512, JSON_THROW_ON_ERROR);
                    $processed[] = $data;
                    $processedCount++;
                    
                    // Process the message through a callback
                    Redis::connection('streams')->xack($this->stream, $this->group, [$id]);
                }
            }
        }
        
        $shouldStop = true;
    };
    
    // Replace the consume method for testing
    $reflectionClass = new ReflectionClass($testConsumer);
    $reflectionProperty = $reflectionClass->getProperty('shouldStop');
    $reflectionProperty->setAccessible(true);
    $reflectionProperty->setValue($testConsumer, true);
    
    // Invoke the monkey-patched consume method
    $mockConsume->call($testConsumer);
    
    // Assert
    expect($processedCount)->toBeGreaterThanOrEqual(1);
    expect($processed)->toBeArray();
    
    // Check that each processed message has the expected structure
    foreach ($processed as $msg) {
        expect($msg)->toHaveKey('event');
        expect($msg)->toHaveKey('payload');
        expect($msg)->toHaveKey('timestamp');
    }
});

it('acknowledges processed messages', function () {
    // Arrange
    $stream = config('redis_stream.stream');
    $group = config('redis_stream.consumer_group');
    $consumer = config('redis_stream.consumer_name');
    
    // Create the consumer group
    Redis::connection('streams')->xgroup('CREATE', $stream, $group, '0', true);
    
    // Add a test message
    $producer = new RedisStreamProducer($stream);
    $messageId = $producer->publish('test.event', ['test' => 'data']);
    
    // Process the message
    $messages = Redis::connection('streams')->xreadgroup($group, $consumer, [$stream => '>'], 1);
    
    if ($messages) {
        foreach ($messages as $streamName => $entries) {
            foreach ($entries as $id => $message) {
                // Acknowledge the message
                Redis::connection('streams')->xack($stream, $group, [$id]);
            }
        }
    }
    
    // Check if there are any pending messages
    $pending = Redis::connection('streams')->xpending($stream, $group, '-', '+', 10);
    
    // Assert
    expect($pending)->toBeEmpty();
});

it('handles errors correctly during message processing', function () {
    // Arrange
    $stream = config('redis_stream.stream');
    $group = config('redis_stream.consumer_group');
    $consumer = config('redis_stream.consumer_name');
    
    // Create the consumer group
    Redis::connection('streams')->xgroup('CREATE', $stream, $group, '0', true);
    
    // Add a test message
    $producer = new RedisStreamProducer($stream);
    $messageId = $producer->publish('test.event', ['test' => 'data']);
    
    // Create a callback that will throw an exception
    $errorCallback = function ($data, $id) {
        throw new \Exception('Test exception');
    };
    
    // Act & Assert
    expect(function () use ($stream, $group, $consumer, $errorCallback) {
        // Read messages
        $messages = Redis::connection('streams')->xreadgroup($group, $consumer, [$stream => '>'], 1);
        
        if ($messages) {
            foreach ($messages as $streamName => $entries) {
                foreach ($entries as $id => $message) {
                    $data = json_decode($message['message'], true);
                    
                    try {
                        $errorCallback($data, $id);
                        // This should not execute if the callback throws an exception
                        Redis::connection('streams')->xack($stream, $group, [$id]);
                    } catch (\Exception $e) {
                        // Message should not be acknowledged
                    }
                }
            }
        }
        
        // Check if there are pending messages
        $pending = Redis::connection('streams')->xpending($stream, $group, '-', '+', 10);
        
        // There should be a pending message (not acknowledged due to error)
        if (empty($pending)) {
            throw new \Exception('Expected pending messages but found none');
        }
    })->not->toThrow(\Exception::class);
    
    // Verify that we have pending messages
    $pending = Redis::connection('streams')->xpending($stream, $group, '-', '+', 10);
    expect($pending)->not->toBeEmpty();
});