# Just another simple settings package for Laravel. 

[![Software License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)
[![Code Coverage](https://scrutinizer-ci.com/g/ottosmops/settings/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ottosmops/settings/?branch=master)

[![Tests](https://github.com/github/docs/actions/workflows/run-tests.yml/badge.svg)(https://github.com/github/docs/actions/workflows/run-tests.yml/badge.svg)]

[![Packagist Downloads](https://img.shields.io/packagist/dt/ottosmops/settings.svg?style=flat-square)](https://packagist.org/packages/ottosmops/settings)

## Installation
```bash 
composer require ottosmops/settings 
```

```php
php artisan vendor:publish // --tag==migrations
```

If you want to rename the database table, you can do this within `config/settings.php`. 

```php 
php artisan migrate 
```

## Description

The package installs a table `settings` and uses an ordinary Model `setting`. So all the standard methods are available. The helper `setting('key')` and the static method `Setting::getValue('key')` use an array which is Cached. If a setting is created, updated or deleted the cached is forgotten.

Along with `key` and `value` there are some more columns: 

- `type` (not nullable!), 
- `scope` (string), 
- `editable` (casted to bool), 
- `rules` (string), 
- `description` (text).

If you set a new value, it is validated against the type and the rules. So the static method `Setting::setValue('key', 'value')` is the prefered way to set values. 

## Usage 

### Create a new setting

```php 
$setting = Setting::create(['key' => 'myKey', 
                            'type' => 'string', // integer, bool or array
                            'scope' => 'mysetting', 
                            'rules' => 'nullable|string', 
                            'description' => 'My description']);

$setting = Setting::setValue('myKey', 'myValue');

echo $setting->value // myValue from the model

// from Cache
echo setting('key'); // myValue

echo Setting::getValue('key'); // myValue

```

### Types
Values can have different types: `string`, `boolean`, `integer`, `array`. To create a new setting you have to specify at least a type:

```php 
Setting::create(['key' => 'your_key', 'type' => 'integer']);
Setting::setValue('your_key', 37);
```

So if you now get a value it is casted to this type.

### Helper
For retrieving a value there is a helper `setting($key, $default= null)` which uses a cached array of settings. 

### Config
You can set another table name in `setting.php`.

### Inspired by 
https://github.com/saqueib/db-settings/blob/master/app/Setting/Setting.php

https://coderstape.com/series/1-laravel-package-development
