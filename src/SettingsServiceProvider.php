<?php

namespace Ottosmops\Settings;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/settings.php' => config_path('settings.php'),
        ], 'config');

        if (! class_exists('CreateSettingsTable')) {
            $this->publishes([
                    __DIR__ . '/../database/migrations/create_settings_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()). '_create_settings_table.php')
            ], 'migrations');
        }
    }

    public function register()
    {

    }
}
