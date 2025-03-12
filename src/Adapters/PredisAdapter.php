<?php

namespace RedisStream\Adapters;

use Illuminate\Support\Facades\Redis;
use RedisStream\Exceptions\ConnectionException;

class PredisAdapter implements RedisAdapterInterface
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
        $arguments = [];
        
        // Handle MAXLEN option for stream trimming
        if (isset($options['MAXLEN'])) {
            $arguments['MAXLEN'] = $options['MAXLEN'];
        }
        
        return $this->connection('streams')->xadd($stream, $id, $message, $arguments);
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
        $args = [];
        
        if ($count !== null) {
            $args['COUNT'] = $count;
        }
        
        return $this->connection('streams')->xrange($stream, $start, $end, $args);
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
        if ($mkstream && strtoupper($command) === 'CREATE') {
            return $this->connection('streams')->xgroup($command, $stream, $group, $id, 'MKSTREAM');
        }
        
        return $this->connection('streams')->xgroup($command, $stream, $group, $id);
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
        $args = ['COUNT' => $count];
        
        if ($block !== null) {
            $args['BLOCK'] = $block;
        }
        
        $result = $this->connection('streams')->xreadgroup($group, $consumer, $streams, $args);
        
        return $result ?: [];
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
        return $this->connection('streams')->xack($stream, $group, $ids);
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
        $args = [];
        
        // For detailed information format
        if ($start !== null && $end !== null && $count !== null) {
            $args = [
                'start' => $start,
                'end' => $end,
                'count' => $count,
            ];
            
            if ($consumer !== null) {
                $args['consumer'] = $consumer;
            }
            
            return $this->connection('streams')->xpending($stream, $group, $args);
        }
        
        return $this->connection('streams')->xpending($stream, $group);
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
        $result = $this->connection('streams')->xclaim($stream, $group, $consumer, $minIdleTime, $ids, $options);
        
        return $result ?: [];
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
        if ($maxlen === '~') {
            return $this->connection('streams')->xtrim($stream, 'MAXLEN', '~', $count);
        }
        
        return $this->connection('streams')->xtrim($stream, 'MAXLEN', $count);
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
        $cmdArgs = [$command, $stream];
        
        if (!empty($args)) {
            $cmdArgs = array_merge($cmdArgs, $args);
        }
        
        return $this->connection('streams')->xinfo(...$cmdArgs);
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