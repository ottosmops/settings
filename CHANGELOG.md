# Changelog

All notable changes to `ottosmops/settings` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.4.2] - 2026-10-29

### Fixed
- Boolean settings set from form input such as `"0"` or `""` were stored as JSON strings and read as `true`; `setValue()` now casts them, and values already stored that way are read correctly (#8)
- Integer settings set from a negative string such as `"-5"`, or created with a string value, were read as `0`; `setValue()` now casts them, and values already stored that way are read correctly
- An empty string clears an integer setting instead of being read as `0`

### Changed
- Boolean settings also accept `yes`, `no`, `on` and `off` in `setValue()`, as `settings:set` already did (#8)

## [2.4.1] - 2026-10-29

### Fixed
- `setValue($key, null)` failed for every setting whose rules lack `nullable`, because the type part (`array`, `integer`, `boolean`) refused null; clearing a setting is now allowed unless its rules contain `required`

## [2.4.0] - 2026-10-29

### Added
- `Setting::forgetLoadedSettings()` reloads the settings in a long-running process without flushing the shared cache

### Fixed
- Queue workers and Octane kept the settings loaded at startup and missed changes made by other processes; they now reload them before every job, request, task and tick
- `settings:set` created new settings without validating the value against their type and rules
- `settings:set` turned an invalid boolean such as `foo` into `false` and an invalid integer into `0`; both are now refused
- `settings:set` cast the value of an existing setting with `--type` (default `string`) instead of the setting's own type, so an existing array setting could not be set; a conflicting `--type` is now refused
- `setValue()` validated against the cached rules instead of the rules of the setting itself
- README: the cache command is `settings:cache:flush`, not `settings:flush-cache`

### Changed
- The config is merged in `register()` instead of `boot()`
- The create migration is an anonymous class and no longer collides with an application class `CreateSettingsTable`

### Removed
- The migration `update_settings_table_add_indexes`, which did nothing; the indexes are part of the create migration
- The unused `defaults` block in the config and the cast for the nonexistent column `default`

## [2.3.1] - 2026-10-29

### Fixed
- Validation rules are trimmed part by part: a rule stored as `in:a,b ` (with a trailing blank) refused its last value `b`, now that 2.3.0 applies the rules at all

## [2.3.0] - 2026-10-29

### Fixed
- Validation rules were never loaded on MySQL, so `setValue()` accepted any value; rules are now built independently of the database driver (also fixes PostgreSQL)
- `setting()` returned the default for the string `"0"` (#7)
- `settingAsString()` returned the default for falsy values such as `"0"`; it now returns the default only if the value is null
- `Setting::set()` reset type and editable flag of an existing setting to `string` and `true`
- `Setting::set()` rejected a type alias (`int`, `bool`, `arr`) of the existing type
- `php artisan settings:list` failed with a call to the removed helper `str_limit()`
- Regex values were stored wrongly if `value` came before `type` in the attributes, and regexes containing `"` could not be read back
- String values lost leading and trailing double quotes

### Changed
- An empty array now counts as a value: `setting()` returns `[]` instead of the default
- Regex values are stored as plain JSON; values stored by earlier versions are still read correctly
- `getValidationRules()` returns `string` instead of `regex` for the type part of regex settings

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
