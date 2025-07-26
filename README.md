# Laravel Settings Package

[![Software License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)
[![Code Coverage](https://scrutinizer-ci.com/g/ottosmops/settings/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ottosmops/settings/?branch=master)
[![Tests](https://github.com/ottosmops/settings/actions/workflows/run-tests.yml/badge.svg)](https://github.com/ottosmops/settings/actions/workflows/run-tests.yml/badge.svg)
[![Packagist Downloads](https://img.shields.io/packagist/dt/ottosmops/settings.svg?style=flat-square)](https://packagist.org/packages/ottosmops/settings)

A robust and feature-rich settings package for Laravel applications with caching, validation, and type casting.

## Requirements

- PHP >= 8.1
- Laravel >= 9.0

## Installation

Install the package via Composer:

```bash
composer require ottosmops/settings
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag=settings
```

Or publish them separately:

```bash
# Publish only configuration
php artisan vendor:publish --tag=settings-config

# Publish only migrations
php artisan vendor:publish --tag=settings-migrations
```

Run the migrations:

```bash
php artisan migrate
```

## Upgrading from 1.x to 2.0

If you're upgrading from version 1.x, follow these steps:

### 1. Update your composer.json

```bash
composer require ottosmops/settings:^2.0
```

### 2. Publish new migrations (if you haven't customized existing ones)

```bash
# Backup your current settings data first!
php artisan vendor:publish --provider="Ottosmops\Settings\SettingsServiceProvider" --tag="settings-migrations" --force
```

### 3. Run the new migrations

```bash
php artisan migrate
```

### 4. Update configuration (optional)

The new version includes enhanced configuration options. Publish the new config if you want to use the new features:

```bash
php artisan vendor:publish --provider="Ottosmops\Settings\SettingsServiceProvider" --tag="settings-config" --force
```

### 5. Clear cache

```bash
php artisan settings:flush-cache
# or
php artisan cache:clear
```

### Breaking Changes in 2.0

- **PHP 8.1+ required** (was PHP 7.4+)
- **Laravel 9+ required** (was Laravel 6+)
- Enhanced type safety may affect dynamic value access
- New migration adds database indexes (performance improvement)  
- Cache keys have been updated (automatic cache invalidation)

All existing APIs remain backward compatible.

## Configuration

The package publishes a configuration file to `config/settings.php` where you can customize:

- Database table name
- Cache settings
- Default settings

## Features

- **Type casting**: Automatic casting to string, integer, boolean, array, and regex types
- **Validation**: Built-in validation using Laravel's validation rules
- **Caching**: Automatic caching for improved performance
- **Scoping**: Organize settings with scopes
- **Editability control**: Mark settings as editable or read-only
- **Helper functions**: Convenient helper functions for quick access
- **Artisan commands**: Manage settings via command line

## Usage

### Creating Settings

```php
use Ottosmops\Settings\Setting;

// Create a simple string setting
Setting::create([
    'key' => 'app_name',
    'value' => 'My Application',
    'type' => 'string',
    'description' => 'Application name'
]);

// Create with validation rules
Setting::create([
    'key' => 'max_users',
    'type' => 'integer',
    'rules' => 'required|integer|min:1|max:1000',
    'description' => 'Maximum number of users'
]);

// Create with scope
Setting::create([
    'key' => 'theme_color',
    'value' => '#3498db',
    'type' => 'string',
    'scope' => 'appearance',
    'rules' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
    'description' => 'Primary theme color'
]);
```

### Setting Values

```php
// Set a value (with validation)
Setting::setValue('max_users', 50);

// Set without validation
Setting::setValue('max_users', 50, false);

// The value will be automatically cast to the defined type
Setting::setValue('is_active', 'true'); // Becomes boolean true
Setting::setValue('config_array', '["item1", "item2"]'); // Becomes array
```

### Getting Values

```php
// Using the static method
$appName = Setting::getValue('app_name');
$maxUsers = Setting::getValue('max_users', 100); // with default

// Using helper function (recommended for views)
$appName = setting('app_name');
$maxUsers = setting('max_users', 100); // with default

// Get as string representation
$configAsString = settingAsString('config_array');
```

### Supported Types

| Type | Description | Example |
|------|-------------|---------|
| `string` | Text values | `"Hello World"` |
| `integer` | Whole numbers | `42` |
| `boolean` | True/false values | `true`, `false` |
| `array` | JSON arrays | `["item1", "item2"]` |
| `regex` | Regular expressions | `#\d{3}/[0-9]#` |

### Validation

Settings support Laravel's validation rules:

```php
Setting::create([
    'key' => 'email',
    'type' => 'string',
    'rules' => 'required|email',
]);

// This will throw ValidationException
Setting::setValue('email', 'invalid-email');
```

### Scopes

Organize related settings with scopes:

```php
// Create settings with scopes
Setting::create(['key' => 'bg_color', 'scope' => 'theme', ...]);
Setting::create(['key' => 'font_size', 'scope' => 'theme', ...]);

// Filter by scope in your application
$themeSettings = Setting::where('scope', 'theme')->get();
```

### Caching

The package automatically caches all settings for performance. The cache is cleared when settings are created, updated, or deleted.

### Checking Settings

```php
// Check if a setting exists
if (Setting::has('app_name')) {
    // Setting exists
}

// Check if a setting has a value
if (Setting::hasValue('app_name')) {
    // Setting exists and has a value
}

// Check if a setting is editable
if (Setting::isEditable('app_name')) {
    // Setting can be modified
}
```

### Removing Settings

```php
Setting::remove('old_setting');
```

## Artisan Commands

### List all settings

```bash
php artisan settings:list

# Filter by scope
php artisan settings:list --scope=theme
```

### Set a setting value

```bash
# Create or update a setting
php artisan settings:set app_name "My Application" --type=string --description="App name"

# Set with validation rules
php artisan settings:set max_users 100 --type=integer --rules="required|integer|min:1"

# Set array value
php artisan settings:set features '["feature1", "feature2"]' --type=array
```

## Environment Configuration

You can override configuration values using environment variables:

```env
SETTINGS_TABLE=custom_settings
SETTINGS_CACHE_ENABLED=true
SETTINGS_CACHE_PREFIX=app_settings
```

## Best Practices

1. **Use descriptive keys**: Use clear, descriptive names for your settings
2. **Set appropriate types**: Always specify the correct type for proper casting
3. **Add validation rules**: Use validation to ensure data integrity
4. **Use scopes**: Group related settings with scopes
5. **Use helper functions**: Use `setting()` helper in views for cleaner code
6. **Handle exceptions**: Wrap setting operations in try-catch blocks for production

## Error Handling

The package throws `NoKeyIsFound` exceptions when trying to access non-existent settings. The helper functions automatically handle these exceptions and return the default value:

```php
// This throws NoKeyIsFound if 'missing_key' doesn't exist
Setting::getValue('missing_key');

// This returns null if 'missing_key' doesn't exist
setting('missing_key');
```

## Testing

Run the test suite:

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email the maintainer instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Inspired by

- [saqueib/db-settings](https://github.com/saqueib/db-settings)
- [coderstape/laravel-package-development](https://coderstape.com/series/1-laravel-package-development)
