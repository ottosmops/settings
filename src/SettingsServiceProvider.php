<?php

namespace Ottosmops\Settings;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../database/migrations/' => base_path('/database/migrations'),
            __DIR__.'/../config/settings.php' => config_path('settings.php'),
        ]);
    }

    public function register()
    {

    }
}
