<?php

namespace RedisStream;

use Exception;
use JsonException;
use Illuminate\Support\Facades\Redis;

readonly class RedisStreamProducer
{
    public function __construct(public string $stream) {}

    /**
     * @throws JsonException
     * @throws Exception
     */
    final public function publish(string $event, string $envelop): string
    {
        try {
            return Redis::connection('streams')->
            xadd($this->stream, '*', ['message' => json_encode([
                'event' => $event,
                'envelop' => $envelop,
                'timestamp' => now()->toDateTimeString(),
            ], JSON_THROW_ON_ERROR)]);
        } catch (Exception $e) {
            throw new \RuntimeException("Error publishing message in Redis Stream $this->stream. Message: " . $e->getMessage());
        }
    }
}
