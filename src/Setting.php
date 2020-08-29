<?php

namespace Ottosmops\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use Ottosmops\Settings\Exceptions\NoKeyIsFound;

class Setting extends Model
{
    protected $keyType = 'string';

    protected $guarded = [];

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $table;

    static $all_settings;

    protected $casts = [
        'editable' => 'boolean',
        'value' => 'array',
        'default' => 'array'
    ];

    protected function asJson($value)
    {
        if (is_string($value) && $this->type == 'regex') {
            $value = str_replace('\\', 'ç€π', $value);
            $value = str_replace('/', '@∆ª', $value);
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function setTypeAttribute($value)
    {
        switch ($value) {
            case 'arr':
            case 'array':
                $this->attributes['type'] = 'array';
                break;
            case 'int':
            case 'integer':
                $this->attributes['type'] = 'integer';
                break;
            case 'bool':
            case 'boolean':
                $this->attributes['type'] = 'boolean';
                break;
            case 'regex':
                $this->attributes['type'] = 'regex';
                break;
            case 'string':
                $this->attributes['type'] = 'string';
                break;
            default:
                throw new \UnexpectedValueException($value);
        }
    }

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
            case 'regex':
                $value = str_replace('ç€π', '\\', $value);
                $value = str_replace('@∆ª', '/', $value);
                return trim($value, '"');
            case 'string':
                $value = json_decode($value, true);
            default:
                return trim($value, '"');
        }
    }

    public function getValueAsStringAttribute()
    {
        if ($this->value === null) {
            return '';
        }

        $type = $this->type ?: gettype($this->value);
        $value = $this->value;
        switch ($type) {
            case 'array':
            case 'object':
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            case 'integer':
                return (string) $value;
            case 'boolean':
                if ($value === false) {
                    return "false";
                }
                return "true";
            case 'regex':
                $value = str_replace('ç€π', '\\', $value);
                $value = str_replace('@∆ª', '/', $value);
            case 'string':
                $value =  trim($value, '"');
            default:
                return (string) $value;
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

    public static function getValueAsString(string $key, $default = null)
    {
        if (!self::has($key)) {
            throw new NoKeyIsFound();
        }

        $setting = static::where('key', $key)->first();

        return $setting->valueAsString ?: $default;
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

        if ($setting->type === 'boolean' && is_string($value)) {
            $value = ($value === 'false') ? false : $value;
            $value = ($value === 'true')  ? true  : $value;
        }
        if ($setting->type === 'integer' && is_string($value) && ctype_digit($value)) {
            $value = (int) $value;
        }

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
        if (! static::$all_settings) {
            static::$all_settings = Cache::rememberForever('settings.all', function () {
                return self::all()->keyBy('key')->toArray();
            });
        }

        return static::$all_settings;
    }

    /**
     * Helper function: Validate a value against its type and its rules.
     * @param  mixed $value
     * @return bool
     */
    public function validateNewValue($value) : bool
    {
        if ($this->type === 'regex') {
            $validator = Validator::make([$this->key => $value], [$this->key => 'string']);
            return !$validator->fails();
        }
        $validator = Validator::make([$this->key => $value], self::getValidationRules());

        return !$validator->fails();
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
                return Setting::select(\DB::raw('concat_ws("|", rules, type) as rules'))
                            ->select('key')
                            ->get()
                            ->toArray();
            });
        }

        return Cache::rememberForever('settings.rules', function () {
            return Setting::select(\DB::raw("printf('%s|%s', rules, type) as rules, `key`"))
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
        Cache::forget('settings.rules');
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
            static::$all_settings = [];
        });
        static::updated(function () {
            self::flushCache();
            static::$all_settings = [];
        });
        static::created(function () {
            self::flushCache();
            static::$all_settings = [];
        });
    }
}
