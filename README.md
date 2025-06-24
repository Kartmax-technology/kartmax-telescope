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
# Enable/Disable Telescope
TELESCOPE_ENABLED=true

# Storage Configuration
TELESCOPE_DRIVER=s3
TELESCOPE_S3_DISK=s3           # The Laravel disk to use (default: s3)
TELESCOPE_S3_DIRECTORY=telescope # The directory/prefix in the bucket (default: telescope)

# Custom Tags Configuration
TELESCOPE_CUSTOM_STATIC_TAG='your service name'    # Static tag for all entries (e.g., service name)
TELESCOPE_CUSTOM_DYNAMIC_TAG='any dynamic app instance'  # Dynamic tag for entries (e.g., instance ID)

# Production and Performance Settings
TELESCOPE_ENABLED_IN_PROD=true  # Enable Telescope in production environment
TELESCOPE_CACHE_TTL=60         # Cache TTL in seconds for Telescope entries
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
