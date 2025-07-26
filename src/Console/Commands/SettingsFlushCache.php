<?php

namespace Ottosmops\Settings\Console\Commands;

use Illuminate\Console\Command;
use Ottosmops\Settings\Setting;

class SettingsFlushCache extends Command
{
    protected $signature = 'settings:cache:flush';
    protected $description = 'Flush the settings cache';

    public function handle()
    {
        Setting::flushCache();
        $this->info('Settings cache flushed successfully.');

        return 0;
    }
}
