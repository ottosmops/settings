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
    ];

    protected function asJson($value, $flags = 0)
    {
        return json_encode($value, $flags | JSON_UNESCAPED_UNICODE);
    }

    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = self::normalizeType($value);
    }

    /**
     * Map a type alias (arr, int, bool) to its canonical name
     *
     * @param  string  $type
     * @return string
     * @throws \UnexpectedValueException
     */
    public static function normalizeType($type): string
    {
        switch ($type) {
            case 'arr':
            case 'array':
                return 'array';
            case 'int':
            case 'integer':
                return 'integer';
            case 'bool':
            case 'boolean':
                return 'boolean';
            case 'regex':
                return 'regex';
            case 'string':
                return 'string';
            default:
                throw new \UnexpectedValueException($type);
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
    public static function isEditable(string $key): bool
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
     * @throws \UnexpectedValueException
     */
    public function getValueAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        switch ($this->type) {
            case 'arr':
            case 'array':
                $decoded = json_decode($value, true);
                if (!is_array($decoded)) {
                    throw new \UnexpectedValueException("Expected array for key '{$this->key}', got " . gettype($decoded));
                }
                return $decoded;
            case 'int':
            case 'integer':
                // Values stored as JSON strings ("12", "-5") before setValue() cast them
                $decoded = json_decode($value, true);
                return intval(is_string($decoded) ? $decoded : $value);
            case 'bool':
            case 'boolean':
                // Values stored as JSON strings ("0", "") before setValue() cast them
                $decoded = json_decode($value, true);
                if (is_string($decoded)) {
                    $value = $decoded;
                }
                $cast = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                return $cast ?? boolval($value);
            case 'regex':
                if (!is_string($value)) {
                    throw new \UnexpectedValueException("Expected string for regex key '{$this->key}', got " . gettype($value));
                }
                $decoded = json_decode($value, true);
                $value = is_string($decoded) ? $decoded : trim($value, '"');
                // Values stored before 2.3.0 use placeholders for backslash and slash
                $value = str_replace('ç€π', '\\', $value);
                return str_replace('@∆ª', '/', $value);
            case 'string':
                $decoded = json_decode($value, true);
                if (!is_string($decoded) && $decoded !== null) {
                    throw new \UnexpectedValueException("Expected string for key '{$this->key}', got " . gettype($decoded));
                }
                // Fall back to the raw value if it was not stored as JSON
                if ($decoded === null && $value !== 'null') {
                    return trim($value, '"');
                }
                return $decoded;
            default:
                if (!is_string($value)) {
                    throw new \UnexpectedValueException("Expected string for key '{$this->key}', got " . gettype($value));
                }
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
            default:
                return (string) $value;
        }
    }

    /**
     * Get a type casted setting value
     *
     * @param  string $key
     * @param  mixed  $default is returned if value is null
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

        return $setting->value === null ? $default : $setting->valueAsString;
    }

    /**
     * Check if setting exists
     *
     * @param $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return (bool) isset(self::allSettings()[$key]);
    }

    /**
     * If a setting has a value (also boolean false, integer 0 and an empty string are values)
     *
     * @param  string  $key
     * @return boolean
     */
    public static function hasValue(string $key): bool
    {
        return self::has($key) && isset(Setting::allSettings()[$key]['value']);
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

        // Form input arrives as string: "0", "1", "", "true", "false", "on", "off", ...
        // Unknown strings are kept as they are, so validation can reject them
        if ($setting->type === 'boolean' && is_string($value)) {
            $cast = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $value = $cast ?? $value;
        }
        // Form input arrives as string: "12", "-5", "" (empty field clears the setting)
        // Unknown strings are kept as they are, so validation can reject them
        if ($setting->type === 'integer' && is_string($value)) {
            $cast = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            $value = trim($value) === '' ? null : ($cast ?? $value);
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

        if ($setting->exists && isset($attributes['type']) && $setting->type !== self::normalizeType($attributes['type'])) {
            throw new \InvalidArgumentException("Cannot change type of existing setting '{$key}' from '{$setting->type}' to '{$attributes['type']}'");
        }

        if (!$setting->exists) {
            $attributes = array_merge([
                'type' => 'string',
                'editable' => true,
            ], $attributes);
        }

        $setting->fill($attributes);

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
    public static function allSettings(): array
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
    public function validateNewValue($value, bool $throwValidationException = false): bool
    {
        // Clearing a setting is allowed unless its rules require a value
        if ($value === null && !in_array('required', array_map('trim', explode('|', (string) $this->rules)), true)) {
            return true;
        }

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

        $rule = self::combineRules($this->rules, $this->type) ?: 'nullable';

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
    public static function getValidationRules(): array
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
        return Setting::query()
            ->toBase()
            ->get(['key', 'rules', 'type'])
            ->mapWithKeys(function ($setting) {
                return [$setting->key => self::combineRules($setting->rules, $setting->type)];
            })
            ->toArray();
    }

    /**
     * Combine the rules of a setting with its type: rules|type
     *
     * @param  string|null  $rules
     * @param  string|null  $type
     * @return string
     */
    private static function combineRules(?string $rules, ?string $type): string
    {
        // A regex setting holds a pattern, validated as a string
        $type = $type === 'regex' ? 'string' : $type;

        // Rule parts are trimmed: «in:a,b |string» refused «b»
        $parts = array_map('trim', explode('|', (string) $rules));

        return implode('|', array_filter([...$parts, $type], fn ($part) => $part !== null && $part !== ''));
    }

    /**
     * Forget the settings loaded in this process, but keep the shared cache.
     * Long-running processes (queue workers, Octane) call this before each
     * job or request to pick up changes made by other processes.
     */
    public static function forgetLoadedSettings()
    {
        static::$all_settings = null;
    }

    /**
     * Flush the cache
     */
    public static function flushCache()
    {
        $cachePrefix = config('settings.cache.key_prefix', 'settings');
        Cache::forget($cachePrefix . '.all');
        Cache::forget($cachePrefix . '.rules');

        static::forgetLoadedSettings();
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
