<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSearchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('search', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('space_id');
            $table->unsignedTinyInteger('object_type');
            $table->unsignedInteger('object_id');
            $table->string('object_title', 200);
            $table->mediumText('object_content');
            $table->unsignedTinyInteger('object_deleted')->default(0);
            $table->unsignedTinyInteger('space_deleted')->default(0);
            $table->timestamp('ctime')->useCurrent();
            $table->timestamp('mtime')->useCurrentOnUpdate();
            
            $table->unique(['space_id', 'object_type', 'id'], 'space_id_object_type_object_id');
            $table->fulltext(['object_title', 'object_content']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('search');
    }
}
