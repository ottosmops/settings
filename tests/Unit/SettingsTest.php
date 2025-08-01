<?php

namespace Ottosmops\Settings\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ottosmops\Settings\Setting;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_setting_string()
    {
        Setting::create(['key' =>'test_string', 'value' => 'value', 'type' => 'string', 'rules' => 'nullable|string']);
        $actual = setting('test_string');
        $this->assertIsString($actual);
        $this->assertEquals('value', $actual);

        $actual = setting('test_string');
        $this->assertIsString($actual);
        $this->assertEquals('value', $actual);

        $rules = Setting::getValidationRules();
        $validator = \Validator::make(['test_string' => setting('test_string')], $rules);
        $this->assertFalse($validator->fails());
    }

    public function test_set_setting_string2()
    {
        $setting = Setting::create(['key' => 'test_string', 'value' => 'value', 'type' => 'string', 'rules' => 'nullable|string']);
        $this->assertEquals('value', setting('test_string'));
        $setting->value = 'value2';
        $setting->save();
        $actual = setting('test_string');
        $this->assertIsString($actual);
        $this->assertEquals('value2', $actual);
    }

    public function test_set_setting_integer()
    {
        Setting::create(['key' => 'test_integer', 'value' => 344, 'type' => 'integer', 'rules' => 'nullable|integer']);
        $actual = setting('test_integer');
        $this->assertIsInt($actual);
        $this->assertEquals(344, $actual);

        $rules = Setting::getValidationRules();
        $validator = \Validator::make(['test_integer' => setting('test_integer')], $rules);
        $this->assertFalse($validator->fails());

        Setting::setValue('test_integer', 345);
        $actual = setting('test_integer');
        $this->assertIsInt($actual);
        $this->assertEquals(345, $actual);
    }

    public function test_set_setting_boolean()
    {
        Setting::create(['key' => 'test_boolean', 'value' => false, 'type' => 'bool', 'rules' => 'nullable|bool']);
        $actual = setting('test_boolean');
        $this->assertIsBool($actual);
        $this->assertFalse($actual);

        $rules = Setting::getValidationRules();
        $validator = \Validator::make(['test_boolean' => setting('test_boolean')], $rules);
        $this->assertFalse($validator->fails());

        Setting::create(['key' => 'test_boolean2', 'value' => true, 'type' => 'boolean', 'rules' => 'nullable|bool']);
        $actual = setting('test_boolean2');
        $this->assertIsBool($actual);
        $this->assertTrue($actual);

        Setting::setValue('test_boolean2', false);
        $this->assertFalse(setting('test_boolean2'));
        Setting::setValue('test_boolean2', true);
        $this->assertTrue(setting('test_boolean2'));
    }

    public function test_has_value()
    {
        Setting::create(['key' => 'false', 'value' => false, 'type' => 'boolean', 'rules' => 'nullable|bool']);
        Setting::create(['key' => 'true', 'value' => true, 'type' => 'boolean', 'rules' => 'nullable|bool']);
        Setting::create(['key' => 'no_value', 'type' => 'boolean', 'rules' => 'nullable|bool']);

        $this->assertTrue(Setting::hasValue('false'));
        $this->assertTrue(Setting::hasValue('true'));
        $this->assertFalse(Setting::hasValue('no_value'));
    }

    public function test_set_and_get_setting_array()
    {
        $array = ['hans' => 'dampf', 'in' => 'allen', 'gassen'];
        Setting::create(['key' => 'test_array', 'type' => 'arr', 'rules' => 'array']);
        Setting::setValue('test_array', $array);
        $actual = setting('test_array');
        $this->assertIsArray($actual);
        $this->assertEquals($actual, $array);

        $rules = Setting::getValidationRules();
        $validator = \Validator::make(['test_array' => setting('test_array')], $rules);
        $this->assertFalse($validator->fails());
    }

    public function test_is_editable()
    {
        $value = 'hans';
        Setting::create(['key' => 'test', 'value' => $value, 'type' => 'string', 'rules' => 'nullable|string']);
        $this->assertTrue(Setting::isEditable('test'));

        $setting = Setting::find('test');
        $setting->update(['editable' => 0]);
        $this->assertFalse(Setting::isEditable('test'));

        Setting::find('test')->update(['editable' => 1]);
        $this->assertTrue(Setting::isEditable('test'));
    }

    public function test_default()
    {
        $value = 'hans';
        // value is set
        Setting::create(['key' => 'test', 'value' => $value, 'type' => 'string', 'rules' => 'nullable|string']);
        $this->assertEquals($value, Setting::getValue('test', 'hi'));

        // method parameter default has precedence
        Setting::create(['key' => 'test2', 'value' => null, 'type' => 'string', 'rules' => 'nullable|string']);
        $this->assertEquals('hi', setting('test2', 'hi'));
    }

    public function test_exception_no_key_is_found_get_value()
    {
        Setting::create(['key' => 'test', 'type' => 'string', 'rules' => 'nullable|string']);
        $this->expectException(\Ottosmops\Settings\Exceptions\NoKeyIsFound::class);
        Setting::getValue('test2');
    }

    public function test_exception_no_key_is_found_set_value()
    {
        Setting::create(['key' => 'test', 'type' => 'string', 'rules' => 'nullable|string']);
        // The setting() helper function returns null when key is not found, not throws exception
        $result = setting('test2');
        $this->assertNull($result);
    }

    public function test_remove()
    {
        Setting::create(['key' => 'test1', 'type' => 'string', 'rules' => 'nullable|string']);
        Setting::create(['key' => 'test2', 'type' => 'string', 'rules' => 'nullable|string']);
        Setting::create(['key' => 'test3', 'type' => 'string', 'rules' => 'nullable|string']);
        Setting::remove('test2');

        // The setting() helper function returns null when key is not found
        $result = setting('test2');
        $this->assertNull($result);

        $actual = count(Setting::all());
        $this->assertEquals(2, $actual);
    }

    public function test_remove_no_key()
    {
        Setting::create(['key' => 'test1', 'type' => 'string', 'rules' => 'nullable|string']);
        Setting::create(['key' => 'test2', 'type' => 'string', 'rules' => 'nullable|string']);
        Setting::create(['key' => 'test3', 'type' => 'string', 'rules' => 'nullable|string']);
        $this->expectException(\Ottosmops\Settings\Exceptions\NoKeyIsFound::class);
        Setting::remove('test5');
    }

    public function test_editable_is_boolean()
    {
        Setting::create(['key' => 'test1', 'type' => 'string', 'rules' => 'nullable|bool', 'editable' => 1]);
        $this->assertIsBool(Setting::find('test1')->editable);
    }

    public function test_validate_new_value()
    {
        $setting = Setting::create(['key' => 'string', 'type' => 'string', 'rules' => 'nullable|string']);
        $value = 'string value';
        $this->assertTrue($setting->validateNewValue($value));
        $value = 22;
        $this->assertFalse($setting->validateNewValue($value));
        Setting::setValue('string', 'neuer string value');
        $this->assertEquals('neuer string value', Setting::getValue('string'));
    }

    public function test_bool_value_with_no_rules()
    {
        $setting = Setting::create(['key' => 'true', 'type' => 'boolean']);
        $value = true;
        Setting::setValue('true', $value);
        $this->assertTrue(Setting::getValue('true'));
        $this->assertIsBool(Setting::getValue('true'));
    }

    public function test_set_value_validation()
    {
        Setting::create(['key' => 'test', 'type' => 'int', 'rules' => 'nullable|integer']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Setting::setValue('test', 'string');
    }

    public function test_create_a_setting_without_rules()
    {
        Setting::create(['key' => 'test', 'type' => 'integer']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Setting::setValue('test', 'string');
        Setting::setValue('test', 333);
        $this->assertEquals('string', setting('test'));
    }

    public function test_get_value_as_string()
    {
        Setting::create(['key' => 'integer', 'type' => 'integer']);
        Setting::setValue('integer', 333);
        $this->assertIsString(settingAsString('integer'));
        $this->assertTrue("333" === settingAsString('integer'));

        Setting::create(['key' => 'array', 'type' => 'array']);
        Setting::setValue('array', ['hübertus', 'antonius']);
        $this->assertIsString(settingAsString('array'));

        Setting::create(['key' => 'bool', 'type' => 'bool']);
        Setting::setValue('bool', true);
        $this->assertIsString(settingAsString('bool'));
    }

    public function test_regex()
    {
        $regex = '#\d{3}/[0-9]#';
        Setting::create(['key' => 'regex', 'type' => 'regex']);
        Setting::setValue('regex', $regex);
        $this->assertEquals($regex, setting('regex'));
    }

    public function test_value_mutator()
    {
        $this->expectException(\UnexpectedValueException::class);
        $regex = '#\d{3}/[0-9]#';
        Setting::create(['key' => 'regex', 'type' => 'bla']);
        Setting::setValue('regex', $regex);
    }

    public function test_string_with_linebreak()
    {
        Setting::create(['key' => 'linebreak', 'type' => 'string']);
        $value = 'My
            Linebreak';
        Setting::setValue('linebreak', $value);
        $expected = $value;
        $this->assertEquals($expected, setting('linebreak'));
    }

    public function test_string_with_linebreak_as_string()
    {
        Setting::create(['key' => 'linebreak', 'type' => 'string']);
        $value = 'My
            Linebreak';
        Setting::setValue('linebreak', $value);
        $expected = $value;
        $this->assertEquals($expected, settingAsString('linebreak'));
    }

    public function test_new_set_method()
    {
        // Test the new set() method
        Setting::set('new_key', 'test_value', ['type' => 'string']);

        $this->assertEquals('test_value', setting('new_key'));
        $this->assertEquals('string', Setting::where('key', 'new_key')->first()->type);
    }

    public function test_get_by_scope()
    {
        Setting::create(['key' => 'scope1_key', 'value' => 'value1', 'type' => 'string', 'scope' => 'admin']);
        Setting::create(['key' => 'scope2_key', 'value' => 'value2', 'type' => 'string', 'scope' => 'user']);
        Setting::create(['key' => 'scope3_key', 'value' => 'value3', 'type' => 'string', 'scope' => 'admin']);

        $adminSettings = Setting::getByScope('admin');
        $this->assertCount(2, $adminSettings);

        $userSettings = Setting::getByScope('user');
        $this->assertCount(1, $userSettings);
    }

    public function test_caching()
    {
        Setting::create(['key' => 'cached_key', 'value' => 'cached_value', 'type' => 'string']);

        // First call should cache the value
        $value1 = setting('cached_key');
        $this->assertEquals('cached_value', $value1);

        // Second call should return cached value
        $value2 = setting('cached_key');
        $this->assertEquals('cached_value', $value2);

        // Test cache clearing functionality
        Setting::clearCache();

        // Should reload from database after cache clear
        $value3 = setting('cached_key');
        $this->assertEquals('cached_value', $value3);
    }

    public function test_validation_rules()
    {
        Setting::create(['key' => 'email_setting', 'value' => 'test@example.com', 'type' => 'string', 'rules' => 'email']);
        Setting::create(['key' => 'number_setting', 'value' => 42, 'type' => 'integer', 'rules' => 'min:0']);

        $rules = Setting::getValidationRules();

        $this->assertArrayHasKey('email_setting', $rules);
        $this->assertArrayHasKey('number_setting', $rules);

        // Rules are combined with type: rules|type
        $this->assertEquals('email|string', $rules['email_setting']);
        $this->assertEquals('min:0|integer', $rules['number_setting']);

        // Test valid data
        $validator = \Validator::make([
            'email_setting' => 'valid@email.com',
            'number_setting' => 100
        ], $rules);

        $this->assertFalse($validator->fails());

        // Test invalid data
        $validator = \Validator::make([
            'email_setting' => 'invalid-email',
            'number_setting' => -5
        ], $rules);

        $this->assertTrue($validator->fails());
    }
}
