<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpaceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('space', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('type');
            $table->string('title', 200);
            $table->string('desc', 10000);
            $table->unsignedTinyInteger('others_read')->default(1);
            $table->unsignedTinyInteger('others_write')->default(0);
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
        Schema::dropIfExists('space');
    }
}
