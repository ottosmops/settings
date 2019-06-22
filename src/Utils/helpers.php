<?php

use \Ottosmops\Settings\Setting;

if (! function_exists('setting')) {
    function setting($key, $default = null)
    {
        return Setting::getValue($key, $default);
    }
}
