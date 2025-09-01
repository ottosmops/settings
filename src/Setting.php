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
     * @param  string  $key
     * @return bool
     * @throws NoKeyIsFound
     */
    public static function isEditable(string $key) : bool
    {
        if (!self::has($key)) {
            throw new NoKeyIsFound($key);
        }

        $setting = Setting::where('key', $key)->first();
        return $setting ? $setting->editable : true;
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
     * @throws NoKeyIsFound
     */
    public static function getValue(string $key, $default = null)
    {
        if (!self::has($key)) {
            throw new NoKeyIsFound($key);
        }

        if (self::hasValue($key)) {
            return \Ottosmops\Settings\Setting::allSettings()[$key]['value'];
        }

        return $default;
    }

    public static function getValueAsString(string $key, $default = null)
    {
        if (!self::has($key)) {
            throw new NoKeyIsFound($key);
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
     * @param bool $validate
     * @return bool
     * @throws NoKeyIsFound
     */
    public static function setValue(string $key, $value = null, bool $validate = true): bool
    {
        if (!self::has($key)) {
            throw new NoKeyIsFound($key);
        }

        $setting = self::find($key);

        if ($setting->type === 'boolean' && is_string($value)) {
            $value = ($value === 'false') ? false : $value;
            $value = ($value === 'true')  ? true  : $value;
        }
        if ($setting->type === 'integer' && is_string($value) && ctype_digit($value)) {
            $value = (int) $value;
        }

        if ($validate) {
            $setting->validateNewValue($value, true);
        }

        $setting->value = $value;

        return $setting->save();
    }


    /**
     * Remove a setting
     *
     * @param string $key
     * @return bool
     * @throws NoKeyIsFound
     */
    public static function remove(string $key): bool
    {
        if (self::has($key)) {
            return self::find($key)->delete();
        }

        throw new NoKeyIsFound($key);
    }

    /**
     * Get settings by scope
     *
     * @param string $scope
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByScope(string $scope)
    {
        return static::where('scope', $scope)->get();
    }

    /**
     * Create or update a setting
     *
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return static
     */
    public static function set(string $key, $value = null, array $attributes = [])
    {
        $setting = static::firstOrNew(['key' => $key]);

        if ($setting->exists && isset($attributes['type']) && $setting->type !== $attributes['type']) {
            throw new \InvalidArgumentException("Cannot change type of existing setting '{$key}' from '{$setting->type}' to '{$attributes['type']}'");
        }

        $setting->fill(array_merge([
            'type' => 'string',
            'editable' => true,
        ], $attributes));

        if ($value !== null) {
            $setting->value = $value;
        }

        $setting->save();

        return $setting;
    }

    /**
     * Get all the settings
     *
     * @return array
     */
    public static function allSettings() : array
    {
        if (! static::$all_settings) {
            $cacheKey = config('settings.cache.key_prefix', 'settings') . '.all';
            $cacheTtl = config('settings.cache.ttl');
            $cacheEnabled = config('settings.cache.enabled', true);

            if ($cacheEnabled) {
                static::$all_settings = $cacheTtl
                    ? Cache::remember($cacheKey, $cacheTtl, function () {
                        return self::all()->keyBy('key')->toArray();
                    })
                    : Cache::rememberForever($cacheKey, function () {
                        return self::all()->keyBy('key')->toArray();
                    });
            } else {
                static::$all_settings = self::all()->keyBy('key')->toArray();
            }
        }

        return static::$all_settings;
    }

    /**
     * Helper function: Validate a value against its type and its rules.
     * @param  mixed $value
     * @param  bool $throwValidationException
     * @return bool
     * @throws ValidationException
     */
    public function validateNewValue($value, bool $throwValidationException = false) : bool
    {
        $valueString = $this->formatValueForMessage($value);
        
        if ($this->type === 'regex') {
            $validator = Validator::make(
                [$this->key => $value], 
                [$this->key => 'string'],
                [
                    $this->key . '.string' => "Setting '{$this->key}' must be a string. Given value: {$valueString} (type: " . gettype($value) . ")"
                ]
            );

            if ($validator->fails() && $throwValidationException) {
                throw new ValidationException($validator);
            }

            return !$validator->fails();
        }

        $rules = self::getValidationRules();
        $rule = $rules[$this->key] ?? 'nullable';
        
        $validator = Validator::make(
            [$this->key => $value], 
            [$this->key => $rule],
            $this->getCustomValidationMessages($value, $rule)
        );

        if ($validator->fails() && $throwValidationException) {
            throw new ValidationException($validator);
        }

        return !$validator->fails();
    }

    /**
     * Get custom validation messages for better error reporting
     *
     * @param mixed $value
     * @param string $rule
     * @return array
     */
    private function getCustomValidationMessages($value, string $rule): array
    {
        $valueString = $this->formatValueForMessage($value);
        $typeString = gettype($value);
        
        return [
            $this->key . '.required' => "Setting '{$this->key}' is required but was not provided.",
            $this->key . '.string' => "Setting '{$this->key}' must be a string. Given value: {$valueString} (type: {$typeString})",
            $this->key . '.integer' => "Setting '{$this->key}' must be an integer. Given value: {$valueString} (type: {$typeString})",
            $this->key . '.boolean' => "Setting '{$this->key}' must be a boolean. Given value: {$valueString} (type: {$typeString})",
            $this->key . '.array' => "Setting '{$this->key}' must be an array. Given value: {$valueString} (type: {$typeString})",
            $this->key . '.numeric' => "Setting '{$this->key}' must be numeric. Given value: {$valueString} (type: {$typeString})",
            $this->key . '.email' => "Setting '{$this->key}' must be a valid email address. Given value: {$valueString}",
            $this->key . '.url' => "Setting '{$this->key}' must be a valid URL. Given value: {$valueString}",
            $this->key . '.min' => "Setting '{$this->key}' is too small. Given value: {$valueString}",
            $this->key . '.max' => "Setting '{$this->key}' is too large. Given value: {$valueString}",
            $this->key . '.in' => "Setting '{$this->key}' has an invalid value. Given value: {$valueString}. Expected one of the allowed values from rule: {$rule}",
        ];
    }

    /**
     * Format value for error messages
     *
     * @param mixed $value
     * @return string
     */
    private function formatValueForMessage($value): string
    {
        if (is_null($value)) {
            return 'null';
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_array($value)) {
            $items = array_slice($value, 0, 3);
            $formattedItems = [];
            
            foreach ($items as $item) {
                if (is_array($item)) {
                    $formattedItems[] = '[array]';
                } elseif (is_object($item)) {
                    $formattedItems[] = '[object]';
                } elseif (is_string($item) && strlen($item) > 20) {
                    $formattedItems[] = '"' . substr($item, 0, 17) . '..."';
                } elseif (is_string($item)) {
                    $formattedItems[] = '"' . $item . '"';
                } else {
                    $formattedItems[] = (string) $item;
                }
            }
            
            return '[' . implode(', ', $formattedItems) . (count($value) > 3 ? '...' : '') . ']';
        }
        
        if (is_object($value)) {
            return 'object(' . get_class($value) . ')';
        }
        
        $stringValue = (string) $value;
        return strlen($stringValue) > 50 ? substr($stringValue, 0, 47) . '...' : $stringValue;
    }

    /**
     * Get the validation rules for setting fields
     *
     * @return array
     */
    public static function getValidationRules() : array
    {
        $cacheKey = config('settings.cache.key_prefix', 'settings') . '.rules';
        $cacheTtl = config('settings.cache.ttl');
        $cacheEnabled = config('settings.cache.enabled', true);

        if (!$cacheEnabled) {
            return self::buildValidationRules();
        }

        return $cacheTtl
            ? Cache::remember($cacheKey, $cacheTtl, function () {
                return self::buildValidationRules();
            })
            : Cache::rememberForever($cacheKey, function () {
                return self::buildValidationRules();
            });
    }

    /**
     * Build validation rules array
     *
     * @return array
     */
    private static function buildValidationRules(): array
    {
        if ('mysql' === \DB::connection()->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            return Setting::select(\DB::raw('concat_ws("|", rules, type) as rules'))
                        ->select('key')
                        ->get()
                        ->pluck('rules', 'key')
                        ->toArray();
        }

        return Setting::select(\DB::raw("printf('%s|%s', rules, type) as rules, `key`"))
                        ->pluck('rules', 'key')
                        ->toArray();
    }

    /**
     * Flush the cache
     */
    public static function flushCache()
    {
        $cachePrefix = config('settings.cache.key_prefix', 'settings');
        Cache::forget($cachePrefix . '.all');
        Cache::forget($cachePrefix . '.rules');

        // Also clear the static variable
        static::$all_settings = null;
    }

    /**
     * Alias for flushCache()
     */
    public static function clearCache()
    {
        return static::flushCache();
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
