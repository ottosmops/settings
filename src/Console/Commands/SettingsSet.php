<?php

namespace Ottosmops\Settings\Console\Commands;

use Illuminate\Console\Command;
use Ottosmops\Settings\Setting;

class SettingsSet extends Command
{
    protected $signature = 'settings:set {key} {value} {--type= : Type of a new setting (default: string)} {--scope=} {--rules=} {--description=}';
    protected $description = 'Set a setting value';

    public function handle()
    {
        $key = $this->argument('key');
        $value = $this->argument('value');
        $type = $this->option('type');
        $scope = $this->option('scope');
        $rules = $this->option('rules');
        $description = $this->option('description');

        try {
            // Check if setting exists
            if (Setting::has($key)) {
                $existingType = Setting::find($key)->type;
                if ($type && Setting::normalizeType($type) !== $existingType) {
                    $this->error("Cannot change type of existing setting '{$key}' from '{$existingType}' to '{$type}'.");
                    return 1;
                }

                Setting::setValue($key, $this->castValue($value, $existingType));
                $this->info("Setting '{$key}' updated successfully.");
            } else {
                // Create new setting
                $setting = new Setting([
                    'key' => $key,
                    'type' => $type ?: 'string',
                    'scope' => $scope,
                    'rules' => $rules,
                    'description' => $description,
                ]);
                $value = $this->castValue($value, $setting->type);
                $setting->validateNewValue($value, true);
                $setting->value = $value;
                $setting->save();
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
                $cast = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($cast === null) {
                    throw new \InvalidArgumentException("'{$value}' is not a boolean (use true/false, 1/0, yes/no, on/off).");
                }
                return $cast;
            case 'integer':
                $cast = filter_var($value, FILTER_VALIDATE_INT);
                if ($cast === false) {
                    throw new \InvalidArgumentException("'{$value}' is not an integer.");
                }
                return $cast;
            case 'array':
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [$value];
            default:
                return $value;
        }
    }
}
