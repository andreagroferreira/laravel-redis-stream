<?php

namespace WizardingCode\RedisStream;

use Illuminate\Support\Facades\Redis;
use WizardingCode\RedisStream\Adapters\PhpRedisAdapter;
use WizardingCode\RedisStream\Adapters\PredisAdapter;
use WizardingCode\RedisStream\Adapters\RedisAdapterInterface;
use WizardingCode\RedisStream\Exceptions\ConnectionException;

class RedisAdapterManager
{
    /**
     * Create a Redis adapter based on the driver in use
     *
     * @return RedisAdapterInterface
     * @throws ConnectionException
     */
    public static function create(): RedisAdapterInterface
    {
        $driver = Redis::connection('streams')->client();
        $driverName = get_class($driver);
        
        // PhpRedis adapter
        if ($driverName === 'Redis' || str_contains($driverName, 'PhpRedis')) {
            return new PhpRedisAdapter();
        }
        
        // Predis adapter
        if (str_contains($driverName, 'Predis')) {
            return new PredisAdapter();
        }
        
        throw new ConnectionException("Unsupported Redis driver: {$driverName}. Supported drivers are PhpRedis and Predis.");
    }
}