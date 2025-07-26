<?php

use \Ottosmops\Settings\Setting;
use \Ottosmops\Settings\Exceptions\NoKeyIsFound;

if (! function_exists('setting')) {
    /**
     * Get a setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key, $default = null)
    {
        try {
            return Setting::getValue($key, $default);
        } catch (NoKeyIsFound $e) {
            return $default;
        }
    }
}

if (! function_exists('settingAsString')) {
    /**
     * Get a setting value as string
     *
     * @param string $key
     * @param mixed $default
     * @return string|null
     */
    function settingAsString($key, $default = null)
    {
        try {
            return Setting::getValueAsString($key, $default);
        } catch (NoKeyIsFound $e) {
            return $default;
        }
    }
}
