<?php

namespace Ottosmops\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use Ottosmops\Settings\Exceptions\NoKeyIsFound;

class Setting extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $table;

    protected $casts = [
        'editable' => 'boolean',
        'value' => 'array',
        'default' => 'array'
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = config('settings.table', 'settings');
        parent::__construct($attributes);
    }

    /**
     * Get the editable status
     *
     * @param  string  $value
     * @return bool
     */
    public function getEditableAttribute($value)
    {
        if (!isset($value)) {
            return true;
        }

        return (bool) $value;
    }

    /**
     * Check if setting is editable
     *
     * @param  string  $value
     * @return bool
     */
    public static function isEditable(string $key) : bool
    {
        return Setting::where('key', $key)->first()->editable;
    }

    /**
     * Cast a value to the expected type ($this->type)
     * Possible types: array, integer, boolean, string
     *
     * @param  mixed $value
     * @return mixed
     */
    public function getValueAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        switch ($this->type) {
            case 'arr':
            case 'array':
                return json_decode($value, true);
            case 'int':
            case 'integer':
                return intval($value);
            case 'bool':
            case 'boolean':
                if ($value === "false") {
                    return false;
                }
                return boolval($value);
            default:
                return trim($value, '"');
        }
    }

    /**
     * Get a type casted setting value
     *
     * @param  string $key
     * @param  mixed  $default is returned if value is empty (except boolean false)
     * @return mixed
     */
    public static function getValue(string $key, $default = null)
    {
        if (!self::has($key)) {
            throw new NoKeyIsFound();
        }

        if (self::hasValue($key)) {
            return \Ottosmops\Settings\Setting::allSettings()[$key]['value'];
        }

        return $default;
    }

    /**
    * Check if setting exists
    *
    * @param $key
    * @return bool
    */
    public static function has(string $key) : bool
    {
        return (boolean) isset(self::allSettings()[$key]);
    }

    /**
     * If a setting has a value (also boolean false, integer 0 and an empty string are values)
     *
     * @param  string  $key
     * @return boolean
     */
    public static function hasValue(string $key) : bool
    {
        if (self::has($key) && isset(Setting::allSettings()[$key]['value'])) {
            $value = Setting::allSettings()[$key]['value'];
            return !empty($value) || $value === false || $value === 0 || $value === '';
        }

        return false;
    }

    /**
     * Set a new value
     * @param string  $key
     * @param mixed  $value    // string, integer, boolean or array
     * @param boolean
     */
    public static function setValue(string $key, $value = null, $validate = true)
    {
        if (!self::has($key)) {
            throw new NoKeyIsFound();
        }

        $setting = self::find($key);

        if ($validate && !$setting->validateNewValue($value)) {
            throw new ValidationException(null);
        }

        $setting->value = $value;

        return $setting->save();
    }


    /**
     * Remove a setting
     *
     * @param $key
     * @return bool
     */
    public static function remove(string $key)
    {
        if (self::has($key)) {
            return self::find($key)->delete();
        }

        throw new NoKeyIsFound();
    }

    /**
     * Get all the settings
     *
     * @return mixed
     */
    public static function allSettings() : array
    {
        return Cache::rememberForever('settings.all', function () {
            return $settings = self::all()->keyBy('key')->toArray();

            foreach ($settings as $setting) {
                $array[$setting->key] = $setting->value;
            }

            return $array;
        });
    }

    /**
     * Helper function: Validate a value against its type and its rules.
     * @param  mixed $value
     * @return bool
     */
    public function validateNewValue($value) : bool
    {
        return !Validator::make([$this->key => $value], self::getValidationRules())->fails();
    }

    /**
     * Get the validation rules for setting fields
     *
     * @return array
     */
    public static function getValidationRules() : array
    {
        if ('mysql' === \DB::connection()->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            return Cache::rememberForever('settings.rules', function () {
                return Setting::select(\DB::raw('concat(rules, "|", type) as rules, key'))
                            ->pluck('rules', 'key')
                            ->toArray();
            });
        }


        return Cache::rememberForever('settings.rules', function () {
            return Setting::select(\DB::raw("printf('%s|%s', rules, type) as rules, key"))
                            ->pluck('rules', 'key')
                            ->toArray();
        });
    }

    /**
     * Flush the cache
     */
    public static function flushCache()
    {
        Cache::forget('settings.all');
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleted(function () {
            self::flushCache();
        });
        static::updated(function () {
            self::flushCache();
        });
        static::created(function () {
            self::flushCache();
        });
    }
}
