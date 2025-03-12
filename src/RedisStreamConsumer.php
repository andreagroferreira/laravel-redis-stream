<?php

namespace RedisStream;

use Closure;
use Illuminate\Support\Facades\Redis;

readonly class RedisStreamConsumer
{
    public function __construct(
        public string $stream,
        public string $group,
        public string $consumer,
        public int $interval = 1
    ) {}

    final public function consume(Closure $callback): void
    {
        try {
            Redis::connection('streams')->xgroup('CREATE', $this->stream, $this->group, '0', true);
        } catch (\Exception $e) {
            dump($e->getMessage());
        }

        while (true) {
            $messages = Redis::connection('streams')->xreadgroup($this->group, $this->consumer, [$this->stream => '>'], 1);
            if ($messages) {
                foreach ($messages as $stream => $entries) {
                    foreach ($entries as $id => $message) {
                        $data = json_decode($message['message'], true, 512, JSON_THROW_ON_ERROR);
                        $callback($data);
                        Redis::connection('streams')->xack($this->stream, $this->group, [$id]);
                    }
                }
            }
            sleep($this->interval);
        }
    }
}
