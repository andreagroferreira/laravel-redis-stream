# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-03-12

### Added
- Initial release with comprehensive Redis Streams implementation for Laravel
- RedisStreamProducer for publishing messages to Redis Streams
- RedisStreamConsumer for consuming messages from Redis Streams with consumer groups
- Support for both phpredis and predis Redis drivers
- Batch publishing support for high-throughput applications
- Automatic consumer group management
- Retry handling for failed messages
- Graceful shutdown support
- Configurable stream trimming (MAXLEN)
- Built-in Artisan command for consuming streams
- Artisan commands for generating custom producers and consumers
- Comprehensive exception handling

### Changed
- Changed namespace from `RedisStream\` to `WizardingCode\RedisStream\`