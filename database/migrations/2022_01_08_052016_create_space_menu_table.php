<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpaceMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('space_menu', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('space_id');
            $table->string('title', 100);
            $table->unsignedInteger('type');
            $table->unsignedInteger('object_id');
            $table->string('extend', 200)->default('');
            $table->unsignedTinyInteger('other_read')->default(0);
            $table->unsignedTinyInteger('other_write')->default(0);
            $table->timestamp('ctime')->useCurrent();
            $table->timestamp('mtime')->useCurrentOnUpdate();
            $table->unsignedTinyInteger('deleted')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('space_menu');
    }
}
