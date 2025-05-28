<p align="center"><img width="391" height="83" src="/art/logo.svg" alt="Logo KartmnaX Telescope"></p>

<p align="center">
<a href="#"><img src="https://github.com/laravel/telescope/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="#"><img src="https://img.shields.io/packagist/dt/laravel/telescope" alt="Total Downloads"></a>
<a href="#"><img src="https://img.shields.io/packagist/v/laravel/telescope" alt="Latest Stable Version"></a>
<a href="#"><img src="https://img.shields.io/packagist/l/laravel/telescope" alt="License"></a>
</p>

## Introduction

KartmnaX Telescope is an elegant debug assistant for the Laravel framework, building on the original Laravel Telescope. In addition to all the features of Telescope, KartmnaX Telescope introduces support for S3 storage and enhanced environment-based configuration, making it suitable for distributed and cloud-native environments.

<p align="center">
<img src="https://laravel.com/assets/img/examples/Screen_Shot_2018-10-09_at_1.47.23_PM.png">
</p>

## Key Features

- All features of Laravel Telescope
- **S3 Storage Support**: Store Telescope entries in AWS S3 (or any compatible object storage)
- **Custom Environment Tags**: Add static and dynamic tags to every entry for better filtering and multi-tenant support
- **Production Enablement**: Optionally enable Telescope in production with fine-grained control

## S3 Storage Usage

To use S3 as the storage backend for KartmnaX Telescope, set the following in your `.env`:

```env
TELESCOPE_DRIVER=s3
TELESCOPE_S3_DISK=s3           # The Laravel disk to use (default: s3)
TELESCOPE_S3_DIRECTORY=telescope # The directory/prefix in the bucket (default: telescope)
```

Ensure your `config/filesystems.php` is configured for your S3 disk.

## Environment Variables

KartmnaX Telescope can be configured using the following environment variables:

```env
TELESCOPE_ENABLED=true                # Master switch
TELESCOPE_DOMAIN=                     # Subdomain for Telescope
TELESCOPE_PATH=telescope              # URI path
TELESCOPE_DRIVER=database|s3          # Storage driver
TELESCOPE_S3_DISK=s3                  # S3 disk name
TELESCOPE_S3_DIRECTORY=telescope      # S3 directory/prefix
TELESCOPE_QUEUE_CONNECTION=           # Queue connection
TELESCOPE_QUEUE=                      # Queue name
TELESCOPE_QUEUE_DELAY=10              # Queue delay (seconds)
TELESCOPE_BATCH_WATCHER=true
TELESCOPE_CACHE_WATCHER=true
TELESCOPE_CLIENT_REQUEST_WATCHER=true
TELESCOPE_COMMAND_WATCHER=true
TELESCOPE_DUMP_WATCHER=true
TELESCOPE_DUMP_WATCHER_ALWAYS=false
TELESCOPE_EVENT_WATCHER=true
TELESCOPE_EXCEPTION_WATCHER=true
TELESCOPE_GATE_WATCHER=true
TELESCOPE_JOB_WATCHER=true
TELESCOPE_LOG_WATCHER=true
TELESCOPE_MAIL_WATCHER=true
TELESCOPE_MODEL_WATCHER=true
TELESCOPE_NOTIFICATION_WATCHER=true
TELESCOPE_QUERY_WATCHER=true
TELESCOPE_REDIS_WATCHER=true
TELESCOPE_REQUEST_WATCHER=true
TELESCOPE_RESPONSE_SIZE_LIMIT=64
TELESCOPE_SCHEDULE_WATCHER=true
TELESCOPE_VIEW_WATCHER=true
TELESCOPE_CUSTOM_STATIC_TAG=service   # Custom static tag for all entries
TELESCOPE_CUSTOM_DYNAMIC_TAG=site_token # Class name for dynamic tag
TELESCOPE_ENABLED_IN_PROD=false       # Enable in production
```

## Custom Tags

You can attach custom tags to every entry using the following configuration:

- `TELESCOPE_CUSTOM_STATIC_TAG`: A static string tag (e.g., service name)
- `TELESCOPE_CUSTOM_DYNAMIC_TAG`: A class name resolved from the container, whose value will be used as a tag (e.g., tenant/site token)

## Production Usage

To enable KartmnaX Telescope in production, set:

```env
TELESCOPE_ENABLED_IN_PROD=true
```

Access is still protected by the authorization gate.

## Official Documentation

For general usage, see the [Laravel Telescope documentation](https://laravel.com/docs/telescope). For S3 and advanced configuration, refer to this README.

## Contributing

Thank you for considering contributing to KartmnaX Telescope! Please see the [Laravel contribution guide](https://laravel.com/docs/contributions).

## Code of Conduct

Please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/telescope/security/policy) on how to report security vulnerabilities.

## License

KartmnaX Telescope is open-sourced software licensed under the [MIT license](LICENSE.md).
