<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreeNodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tree_node', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pid');
            $table->unsignedInteger('type');
            $table->unsignedInteger('pos')->default(0);
            $table->unsignedInteger('space_id');
            $table->unsignedInteger('tree_id');
            $table->string('title', 200);
            $table->string('ext', 2048)->default('');
            $table->string('version', 32);
            $table->timestamp('ctime')->useCurrent();
            $table->timestamp('mtime')->useCurrentOnUpdate();
            $table->unsignedTinyInteger('deleted')->default(0);
            
            $table->index(['space_id', 'tree_id', 'pid'], 'space_id_tree_id_pid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tree_node');
    }
}
