<?php

use \Ottosmops\Settings\Setting;

if (! function_exists('setting')) {
    function setting($key, $default = null)
    {
        return Setting::getValue($key, $default);
    }
}

if (! function_exists('settingAsString')) {
    function settingAsString($key, $default = null)
    {
        return Setting::getValueAsString($key, $default);
    }
}
