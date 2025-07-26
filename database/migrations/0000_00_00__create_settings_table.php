<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingsTable extends Migration
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
            $table->boolean('editable')->default(true);
            $table->text('rules')->nullable(); // Changed to text for longer validation rules
            $table->text('description')->nullable();

            // Add indexes for better performance
            $table->index('scope');
            $table->index('type');
            $table->index(['scope', 'type']);
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
