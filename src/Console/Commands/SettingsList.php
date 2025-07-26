<?php

namespace Ottosmops\Settings\Console\Commands;

use Illuminate\Console\Command;
use Ottosmops\Settings\Setting;

class SettingsList extends Command
{
    protected $signature = 'settings:list {--scope= : Filter by scope}';
    protected $description = 'List all settings';

    public function handle()
    {
        $scope = $this->option('scope');

        $query = Setting::query();

        if ($scope) {
            $query->where('scope', $scope);
        }

        $settings = $query->orderBy('key')->get();

        if ($settings->isEmpty()) {
            $this->info('No settings found.');
            return;
        }

        $headers = ['Key', 'Value', 'Type', 'Scope', 'Editable', 'Description'];
        $rows = [];

        foreach ($settings as $setting) {
            $rows[] = [
                $setting->key,
                $this->formatValue($setting->value, $setting->type),
                $setting->type,
                $setting->scope ?? '-',
                $setting->editable ? 'Yes' : 'No',
                str_limit($setting->description ?? '-', 50),
            ];
        }

        $this->table($headers, $rows);
    }

    private function formatValue($value, $type)
    {
        if ($value === null) {
            return 'null';
        }

        if ($type === 'array') {
            return json_encode($value);
        }

        if ($type === 'boolean') {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
