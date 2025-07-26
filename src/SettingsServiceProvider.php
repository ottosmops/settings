<?php

namespace Ottosmops\Settings;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/settings.php', 'settings');

        $this->publishes([
            __DIR__ . '/../config/settings.php' => config_path('settings.php'),
        ], 'settings-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/0000_00_00__create_settings_table.php' =>
                database_path('migrations/'. date('Y_m_d_His', time()) . '_create_settings_table.php')
        ], 'settings-migrations');

        // Publish both config and migrations with a single tag
        $this->publishes([
            __DIR__ . '/../config/settings.php' => config_path('settings.php'),
            __DIR__ . '/../database/migrations/0000_00_00__create_settings_table.php' =>
                database_path('migrations/'. date('Y_m_d_His', time()) . '_create_settings_table.php'),
            __DIR__ . '/../database/migrations/0000_00_01__update_settings_table_add_indexes.php' =>
                database_path('migrations/'. date('Y_m_d_His', time() + 1) . '_update_settings_table_add_indexes.php')
        ], 'settings');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Ottosmops\Settings\Console\Commands\SettingsList::class,
                \Ottosmops\Settings\Console\Commands\SettingsSet::class,
                \Ottosmops\Settings\Console\Commands\SettingsRemove::class,
                \Ottosmops\Settings\Console\Commands\SettingsFlushCache::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->singleton('settings', function ($app) {
            return new Setting();
        });
    }
}
