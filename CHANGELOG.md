# Changelog

All notable changes to `ottosmops/settings` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2026-10-17

### Removed
- Laravel 10 and 11 support

### Security
- Require `laravel/framework` ^12.69 or ^13.30, the first releases with fixes for the CRLF injection in the default email rule, the temporary signed URL path confusion and the XSS on the debug page

## [2.1.1] - 2026-10-17

### Fixed
- PHP 8.4 deprecation: explicit nullable parameters in `NoKeyIsFound::__construct()`

## [2.1.0] - 2026-10-16

### Added
- Laravel 13 support (`laravel/framework` ^13.0, `orchestra/testbench` ^11.0, PHPUnit 12)

## [2.0.0] - 2024-07-26

### Added
- Enhanced caching system with configurable cache TTL and key prefix
- Artisan commands for managing settings:
  - `settings:list` - List all settings with optional scope filtering
  - `settings:set` - Create or update settings from command line
  - `settings:remove` - Remove settings with confirmation
  - `settings:cache:flush` - Flush settings cache
- New `Setting::set()` method for creating or updating settings
- `Setting::getByScope()` method for retrieving settings by scope
- `Setting::clearCache()` alias for cache management
- Comprehensive GitHub Actions CI/CD pipeline
- Better exception handling with detailed error messages
- Database indexes for improved performance
- Support for Laravel 9, 10, and 11
- Enhanced configuration with environment variable support
- Comprehensive test suite with 26 tests and 65 assertions
- Separate migration for updates to improve upgrade experience

### Changed
- Improved exception handling in `NoKeyIsFound` exception with specific key information
- Better type declarations and return types throughout the codebase
- Enhanced validation system with proper error handling
- Migration updated to use proper boolean type for `editable` field
- Service provider improvements with better asset publishing
- Updated composer.json with modern Laravel version constraints
- Improved helper functions with exception handling

### Fixed
- Fixed potential null pointer exceptions in `isEditable()` method
- Improved validation rule handling for better reliability
- Fixed cache key management with configurable prefixes

### Security
- Enhanced validation to prevent type confusion attacks
- Improved input sanitization in validation methods

## [Previous Versions]
- See git history for changes in previous versions
