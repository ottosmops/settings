<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SettingsTable extends Migration
{
    protected $table;

    public function __construct()
    {
        $this->table = config('settings.table', 'settings');
    }

    /**
     * Run the migrations.
     * key
     * value
     * scope
     * context
     * editable (superuser, admin, editor)
     * type/validation rule (string, array, integer)
     * comment/description
     * default
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type'); // string, integer, array, bool
            $table->string('scope')->nullable();
            $table->tinyInteger('editable')->nullable();
            $table->string('rules')->nullable();
            $table->text('descritpion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}
