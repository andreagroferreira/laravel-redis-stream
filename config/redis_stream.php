<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Redis Stream Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Redis Stream package.
    | You can configure the default stream, consumer group, and consumer name,
    | as well as various performance options like polling interval and 
    | maximum stream length.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Stream Settings
    |--------------------------------------------------------------------------
    */
    
    // The default stream name to use
    'stream' => env('REDIS_STREAM_NAME', 'default_stream'),
    
    // The default consumer group name to use
    'consumer_group' => env('REDIS_STREAM_CONSUMER_GROUP', 'default_group'),
    
    // The default consumer name to use
    'consumer_name' => env('REDIS_STREAM_CONSUMER_NAME', 'default_consumer'),
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    
    // Maximum number of entries to keep in the stream
    // Set to null for unlimited (not recommended in production)
    'max_length' => env('REDIS_STREAM_MAX_LENGTH', 1000000),
    
    // Whether to use exact maxlen trimming (slower) or approximate (faster)
    // true = exact, false = approximate (~)
    'use_exact_maxlen' => env('REDIS_STREAM_EXACT_MAXLEN', false),
    
    // Time to wait between polls in seconds
    'poll_interval' => env('REDIS_STREAM_POLL_INTERVAL', 1),
    
    // Number of retries for failed message processing
    'retry_limit' => env('REDIS_STREAM_RETRY_LIMIT', 3),
    
    // Number of messages to read at once
    'batch_size' => env('REDIS_STREAM_BATCH_SIZE', 10),
    
    // Redis connection name to use
    'connection' => env('REDIS_STREAM_CONNECTION', 'streams'),

    /*
    |--------------------------------------------------------------------------
    | Custom Streams
    |--------------------------------------------------------------------------
    |
    | Here you can define specific named streams for your application.
    | Each stream defined with the stream_ prefix will be automatically
    | registered as a singleton producer with the service container.
    |
    */
    
    // Mirakl integration streams
    'stream_mirakl_categories' => env('STREAM_MIRAKL_CATEGORIES', 'mirakl_categories'),
    'stream_mirakl_attributes' => env('STREAM_MIRAKL_ATTRIBUTES', 'mirakl_attributes'),
    'stream_mirakl_attributes_values' => env('STREAM_MIRAKL_ATTRIBUTES_VALUES', 'mirakl_attributes_values'),
    'stream_mirakl_products_send' => env('STREAM_MIRAKL_PRODUCTS_SEND', 'mirakl_products_send'),
    'stream_mirakl_offers' => env('STREAM_MIRAKL_OFFERS', 'mirakl_offers'),
    'stream_mirakl_products_receive' => env('STREAM_MIRAKL_PRODUCTS_RECEIVE', 'mirakl_products_receive'),
    'stream_mirakl_shops' => env('STREAM_MIRAKL_SHOPS', 'mirakl_shops'),
];
