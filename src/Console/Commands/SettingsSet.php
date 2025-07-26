<?php

namespace Ottosmops\Settings\Console\Commands;

use Illuminate\Console\Command;
use Ottosmops\Settings\Setting;
use Ottosmops\Settings\Exceptions\NoKeyIsFound;

class SettingsSet extends Command
{
    protected $signature = 'settings:set {key} {value} {--type=string} {--scope=} {--rules=} {--description=}';
    protected $description = 'Set a setting value';

    public function handle()
    {
        $key = $this->argument('key');
        $value = $this->argument('value');
        $type = $this->option('type');
        $scope = $this->option('scope');
        $rules = $this->option('rules');
        $description = $this->option('description');

        // Convert string values to appropriate types
        $value = $this->castValue($value, $type);

        try {
            // Check if setting exists
            if (Setting::has($key)) {
                Setting::setValue($key, $value);
                $this->info("Setting '{$key}' updated successfully.");
            } else {
                // Create new setting
                Setting::create([
                    'key' => $key,
                    'value' => $value,
                    'type' => $type,
                    'scope' => $scope,
                    'rules' => $rules,
                    'description' => $description,
                ]);
                $this->info("Setting '{$key}' created successfully.");
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
            case 'int':
                return (int) $value;
            case 'array':
                return json_decode($value, true) ?? [$value];
            default:
                return $value;
        }
    }
}
