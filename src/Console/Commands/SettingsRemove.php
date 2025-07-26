<?php

namespace Ottosmops\Settings\Console\Commands;

use Illuminate\Console\Command;
use Ottosmops\Settings\Setting;
use Ottosmops\Settings\Exceptions\NoKeyIsFound;

class SettingsRemove extends Command
{
    protected $signature = 'settings:remove {key} {--force : Skip confirmation}';
    protected $description = 'Remove a setting';

    public function handle()
    {
        $key = $this->argument('key');
        $force = $this->option('force');

        try {
            if (!Setting::has($key)) {
                $this->error("Setting '{$key}' does not exist.");
                return 1;
            }

            if (!$force && !$this->confirm("Are you sure you want to remove setting '{$key}'?")) {
                $this->info('Operation cancelled.');
                return 0;
            }

            Setting::remove($key);
            $this->info("Setting '{$key}' removed successfully.");

        } catch (NoKeyIsFound $e) {
            $this->error("Setting '{$key}' not found.");
            return 1;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
