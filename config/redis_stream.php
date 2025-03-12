<?php

return [
    'stream' => env('REDIS_STREAM_NAME', 'default_stream'),
    'consumer_group' => env('REDIS_STREAM_CONSUMER_GROUP', 'default_group'),
    'consumer_name' => env('REDIS_STREAM_CONSUMER_NAME', 'default_consumer'),
    'stream_mirakl_categories' => env('STREAM_MIRAKL_CATEGORIES', 'mirakl_categories'),
    'stream_mirakl_attributes' => env('STREAM_MIRAKL_ATTRIBUTES', 'mirakl_attributes'),
    'stream_mirakl_attributes_values' => env('STREAM_MIRAKL_ATTRIBUTES_VALUES', 'mirakl_attributes_values'),
    'stream_mirakl_products_send' => env('STREAM_MIRAKL_PRODUCTS_SEND', 'mirakl_products_send'),
    'stream_mirakl_offers' => env('STREAM_MIRAKL_OFFERS', 'mirakl_offers'),
    'stream_mirakl_products_receive' => env('STREAM_MIRAKL_PRODUCTS_RECEIVE', 'mirakl_products_receive'),
    'stream_mirakl_shops' => env('STREAM_MIRAKL_SHOPS', 'mirakl_shops'),
];
