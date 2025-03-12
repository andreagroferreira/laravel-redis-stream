<?php

namespace WizardingCode\RedisStream\Adapters;

use Illuminate\Support\Facades\Redis;
use WizardingCode\RedisStream\Exceptions\ConnectionException;

class PhpRedisAdapter implements RedisAdapterInterface
{
    /**
     * Get the Redis connection
     *
     * @param string|null $connection The Redis connection name
     * @return mixed
     */
    public function connection(?string $connection = null)
    {
        return Redis::connection($connection);
    }
    
    /**
     * Add a message to a stream
     *
     * @param string $stream The stream key
     * @param string $id The message ID ('*' for auto-generation)
     * @param array $message The message fields and values
     * @param array $options Additional options
     * @return string The message ID
     */
    public function xadd(string $stream, string $id, array $message, array $options = []): string
    {
        $params = [];
        
        // Handle MAXLEN option for stream trimming
        if (isset($options['MAXLEN'])) {
            $params[] = 'MAXLEN';
            
            if ($options['MAXLEN'][0] === '~') {
                $params[] = '~';
            }
            
            $params[] = $options['MAXLEN'][1];
        }
        
        // Add the ID
        $params[] = $id;
        
        // Add the message fields and values
        foreach ($message as $field => $value) {
            $params[] = $field;
            $params[] = $value;
        }
        
        return $this->connection('streams')->xAdd($stream, ...$params);
    }
    
    /**
     * Delete a stream
     *
     * @param string $stream The stream key
     * @return int Number of streams deleted
     */
    public function del(string $stream): int
    {
        return $this->connection('streams')->del($stream);
    }
    
    /**
     * Get stream messages in a range
     *
     * @param string $stream The stream key
     * @param string $start The start ID
     * @param string $end The end ID
     * @param int|null $count Maximum number of messages to return
     * @return array The messages
     */
    public function xrange(string $stream, string $start, string $end, ?int $count = null): array
    {
        $params = [$stream, $start, $end];
        
        if ($count !== null) {
            $params[] = 'COUNT';
            $params[] = $count;
        }
        
        $result = $this->connection('streams')->xRange(...$params);
        
        // Format the result to match the predis format
        $formatted = [];
        if ($result) {
            foreach ($result as $id => $message) {
                $formattedMessage = [];
                for ($i = 0; $i < count($message); $i += 2) {
                    $formattedMessage[$message[$i]] = $message[$i + 1];
                }
                $formatted[$id] = $formattedMessage;
            }
        }
        
        return $formatted;
    }
    
    /**
     * Create a consumer group
     *
     * @param string $command The XGROUP command (CREATE, DESTROY, etc.)
     * @param string $stream The stream key
     * @param string $group The group name
     * @param string $id The ID to start consuming from
     * @param bool $mkstream Whether to create the stream if it doesn't exist
     * @return mixed
     */
    public function xgroup(string $command, string $stream, string $group, string $id, bool $mkstream = false)
    {
        $params = [$command, $stream, $group, $id];
        
        if ($mkstream && strtoupper($command) === 'CREATE') {
            $params[] = 'MKSTREAM';
        }
        
        return $this->connection('streams')->xGroup(...$params);
    }
    
    /**
     * Read messages from a stream as a consumer group
     *
     * @param string $group The group name
     * @param string $consumer The consumer name
     * @param array $streams The streams and IDs to read from
     * @param int $count Maximum number of messages to return
     * @param int|null $block Milliseconds to block for
     * @return array The messages
     */
    public function xreadgroup(string $group, string $consumer, array $streams, int $count, ?int $block = null): array
    {
        $params = ['GROUP', $group, $consumer, 'COUNT', $count];
        
        if ($block !== null) {
            $params[] = 'BLOCK';
            $params[] = $block;
        }
        
        $params[] = 'STREAMS';
        
        // Add the stream keys
        $streamKeys = array_keys($streams);
        $params = array_merge($params, $streamKeys);
        
        // Add the IDs
        $streamIds = array_values($streams);
        $params = array_merge($params, $streamIds);
        
        $result = $this->connection('streams')->xReadGroup(...$params);
        
        // Format the result to match the predis format
        $formatted = [];
        if ($result) {
            foreach ($result as $streamKey => $messages) {
                $formatted[$streamKey] = [];
                foreach ($messages as $id => $message) {
                    $formattedMessage = [];
                    for ($i = 0; $i < count($message); $i += 2) {
                        $formattedMessage[$message[$i]] = $message[$i + 1];
                    }
                    $formatted[$streamKey][$id] = $formattedMessage;
                }
            }
        }
        
        return $formatted;
    }
    
    /**
     * Acknowledge a message
     *
     * @param string $stream The stream key
     * @param string $group The group name
     * @param array $ids The message IDs to acknowledge
     * @return int Number of messages acknowledged
     */
    public function xack(string $stream, string $group, array $ids): int
    {
        return $this->connection('streams')->xAck($stream, $group, $ids);
    }
    
    /**
     * Get pending messages information
     *
     * @param string $stream The stream key
     * @param string $group The group name
     * @param string $start The start ID
     * @param string $end The end ID
     * @param int $count Maximum number of messages to return
     * @param string|null $consumer Filter by consumer
     * @return array Pending messages information
     */
    public function xpending(string $stream, string $group, string $start, string $end, int $count, ?string $consumer = null): array
    {
        $params = [$stream, $group];
        
        // For detailed information format
        if ($start !== null && $end !== null && $count !== null) {
            $params[] = $start;
            $params[] = $end;
            $params[] = $count;
            
            if ($consumer !== null) {
                $params[] = $consumer;
            }
        }
        
        return $this->connection('streams')->xPending(...$params);
    }
    
    /**
     * Claim ownership of pending messages
     *
     * @param string $stream The stream key
     * @param string $group The group name
     * @param string $consumer The consumer name
     * @param int $minIdleTime Minimum idle time in milliseconds
     * @param array $ids The message IDs to claim
     * @param array $options Additional options
     * @return array The claimed messages
     */
    public function xclaim(string $stream, string $group, string $consumer, int $minIdleTime, array $ids, array $options = []): array
    {
        $params = [$stream, $group, $consumer, $minIdleTime];
        $params = array_merge($params, $ids);
        
        // Add options
        foreach ($options as $option => $value) {
            $params[] = $option;
            if ($value !== null) {
                $params[] = $value;
            }
        }
        
        $result = $this->connection('streams')->xClaim(...$params);
        
        // Format the result to match the predis format
        $formatted = [];
        if ($result) {
            foreach ($result as $id => $message) {
                $formattedMessage = [];
                for ($i = 0; $i < count($message); $i += 2) {
                    $formattedMessage[$message[$i]] = $message[$i + 1];
                }
                $formatted[$id] = $formattedMessage;
            }
        }
        
        return $formatted;
    }
    
    /**
     * Trim a stream to a maximum length
     *
     * @param string $stream The stream key
     * @param string $maxlen The maximum length strategy ('~' for approximate)
     * @param int $count The maximum number of elements
     * @return int Number of messages deleted
     */
    public function xtrim(string $stream, string $maxlen, int $count): int
    {
        $params = [$stream, 'MAXLEN'];
        
        if ($maxlen === '~') {
            $params[] = '~';
        }
        
        $params[] = $count;
        
        return $this->connection('streams')->xTrim(...$params);
    }
    
    /**
     * Get stream information
     *
     * @param string $command The XINFO command (STREAM, GROUPS, CONSUMERS)
     * @param string $stream The stream key
     * @param mixed ...$args Additional arguments
     * @return array The stream information
     */
    public function xinfo(string $command, string $stream, ...$args): array
    {
        $params = [$command, $stream];
        
        if (!empty($args)) {
            $params = array_merge($params, $args);
        }
        
        return $this->connection('streams')->xInfo(...$params);
    }
    
    /**
     * Create a pipeline for batch operations
     *
     * @return mixed
     */
    public function pipeline()
    {
        return $this->connection('streams')->pipeline();
    }
}