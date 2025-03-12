<?php

use WizardingCode\RedisStream\Exceptions\RedisStreamException;
use WizardingCode\RedisStream\Exceptions\ConnectionException;
use WizardingCode\RedisStream\Exceptions\PublishException;
use WizardingCode\RedisStream\Exceptions\ConsumeException;
use WizardingCode\RedisStream\Exceptions\MessageProcessingException;

it('creates base redis stream exception', function () {
    // Act
    $exception = new RedisStreamException("Test error");
    
    // Assert
    expect($exception)->toBeInstanceOf(\Exception::class);
    expect($exception->getMessage())->toBe("Test error");
});

it('creates connection exception', function () {
    // Act
    $exception = new ConnectionException("Connection failed");
    
    // Assert
    expect($exception)->toBeInstanceOf(RedisStreamException::class);
    expect($exception->getMessage())->toBe("Connection failed");
});

it('creates publish exception with stream name', function () {
    // Act
    $exception = new PublishException("test_stream", "Failed to publish");
    
    // Assert
    expect($exception)->toBeInstanceOf(RedisStreamException::class);
    expect($exception->getMessage())->toBe("Failed to publish message to stream 'test_stream': Failed to publish");
});

it('creates publish exception with default message', function () {
    // Act
    $exception = new PublishException("test_stream");
    
    // Assert
    expect($exception)->toBeInstanceOf(RedisStreamException::class);
    expect($exception->getMessage())->toBe("Failed to publish message to stream 'test_stream'");
});

it('creates consume exception with all parameters', function () {
    // Act
    $exception = new ConsumeException(
        "test_stream",
        "test_group",
        "test_consumer",
        "Error consuming"
    );
    
    // Assert
    expect($exception)->toBeInstanceOf(RedisStreamException::class);
    expect($exception->getMessage())->toBe(
        "Failed to consume messages from stream 'test_stream' (group: test_group, consumer: test_consumer): Error consuming"
    );
});

it('creates message processing exception with message ID', function () {
    // Act
    $exception = new MessageProcessingException(
        "test_stream",
        "1234-0",
        2,
        "Processing failed"
    );
    
    // Assert
    expect($exception)->toBeInstanceOf(RedisStreamException::class);
    expect($exception->getMessage())->toBe(
        "Failed to process message 1234-0 from stream 'test_stream' (attempt 2): Processing failed"
    );
    expect($exception->stream)->toBe("test_stream");
    expect($exception->messageId)->toBe("1234-0");
    expect($exception->attempt)->toBe(2);
});