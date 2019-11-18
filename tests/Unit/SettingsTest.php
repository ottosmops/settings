<?php

namespace Ottosmops\Settings\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ottosmops\Settings\Setting;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
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

    /** @test */
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

    /** @test */
    public function test_set_setting_integer()
    {
        Setting::create(['key' => 'test_integer', 'value' => 344, 'type' => 'integer', 'rules' => 'nullable|integer']);
        $actual = setting('test_integer');
        $this->assertIsInt($actual);
        $this->assertEquals(344, $actual);

        $rules = Setting::getValidationRules();
        $validator = \Validator::make(['test_integer' => setting('test_integer')], $rules);
        $this->assertFalse($validator->fails());
    }

    /** @test */
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

    /** @test */
    public function test_has_value()
    {
        Setting::create(['key' => 'false', 'value' => false, 'type' => 'boolean', 'rules' => 'nullable|bool']);
        Setting::create(['key' => 'true', 'value' => true, 'type' => 'boolean', 'rules' => 'nullable|bool']);
        Setting::create(['key' => 'no_value', 'type' => 'boolean', 'rules' => 'nullable|bool']);

        $this->assertTrue(Setting::hasValue('false'));
        $this->assertTrue(Setting::hasValue('true'));
        $this->assertFalse(Setting::hasValue('no_value'));
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function test_exception_no_key_is_found_get_value()
    {
        Setting::create(['key' => 'test', 'type' => 'string', 'rules' => 'nullable|string']);
        $this->expectException(\Ottosmops\Settings\Exceptions\NoKeyIsFound::class);
        Setting::getValue('test2');
    }

    /** @test */
    public function test_exception_no_key_is_found_set_value()
    {
        $setting = Setting::create(['key' => 'test', 'type' => 'string', 'rules' => 'nullable|string']);
        $this->expectException(\Ottosmops\Settings\Exceptions\NoKeyIsFound::class);
        setting('test2');
    }

    /** @test */
    public function test_remove()
    {
        Setting::create(['key' => 'test1', 'type' => 'string', 'rules' => 'nullable|string']);
        Setting::create(['key' => 'test2', 'type' => 'string', 'rules' => 'nullable|string']);
        Setting::create(['key' => 'test3', 'type' => 'string', 'rules' => 'nullable|string']);
        Setting::remove('test2');
        $this->expectException(\Ottosmops\Settings\Exceptions\NoKeyIsFound::class);
        setting('test2');
        $actual = count(Setting::all());
        $this->assertEquals(2, $actual);
    }

    /** @test */
    public function test_remove_no_key()
    {
        Setting::create(['key' => 'test1', 'type' => 'string', 'rules' => 'nullable|string']);
        Setting::create(['key' => 'test2', 'type' => 'string', 'rules' => 'nullable|string']);
        Setting::create(['key' => 'test3', 'type' => 'string', 'rules' => 'nullable|string']);
        $this->expectException(\Ottosmops\Settings\Exceptions\NoKeyIsFound::class);
        Setting::remove('test5');
    }

    /** @test */
    public function test_editable_is_boolean()
    {
        Setting::create(['key' => 'test1', 'type' => 'string', 'rules' => 'nullable|bool', 'editable' => 1]);
        $this->assertIsBool(Setting::find('test1')->editable);
    }

    /** test */
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

    /** test */
    public function test_bool_value_with_no_rules()
    {
        $setting = Setting::create(['key' => 'true', 'type' => 'boolean']);
        $value = true;
        Setting::setValue('true', $value);
        $this->assertTrue(Setting::getValue('true'));
        $this->assertIsBool(Setting::getValue('true'));
    }

    /** @test */
    public function test_set_value_validation()
    {
        Setting::create(['key' => 'test', 'type' => 'int', 'rules' => 'nullable|integer']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Setting::setValue('test', 'string');
    }

    /** @test */
    public function test_create_a_setting_without_rules()
    {
        Setting::create(['key' => 'test', 'type' => 'integer']);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        Setting::setValue('test', 'string');
        Setting::setValue('test', 333);
        $this->assertEquals('string', setting('test'));
    }

     /** @test */
    public function test_get_value_as_string()
    {
        Setting::create(['key' => 'integer', 'type' => 'integer']);
        Setting::setValue('integer', 333);
        $this->assertIsString(settingAsString('integer'));

        Setting::create(['key' => 'array', 'type' => 'array']);
        Setting::setValue('array', ['hübertus', 'antonius']);
        $this->assertIsString(settingAsString('array'));

        Setting::create(['key' => 'bool', 'type' => 'bool']);
        Setting::setValue('bool', true);
        $this->assertIsString(settingAsString('bool'));
    }

    /** @test */
    public function test_regex()
    {
        $regex = '#\d{3}/[0-9]#';
        Setting::create(['key' => 'regex', 'type' => 'string']);
        Setting::setValue('regex', $regex);
        $this->assertEquals($regex, setting('regex'));
    }

    /** @test */
    public function test_value_mutator()
    {
        $this->expectException(\UnexpectedValueException::class);
        $regex = '#\d{3}/[0-9]#';
        Setting::create(['key' => 'regex', 'type' => 'bla']);
        Setting::setValue('regex', $regex);
    }
}
