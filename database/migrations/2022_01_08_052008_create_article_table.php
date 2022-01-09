<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('space_id');
            $table->unsignedInteger('node_id');
            $table->unsignedInteger('type');
            $table->string('title', 200);
            $table->mediumText('body');
            $table->mediumText('search');
            $table->unsignedInteger('level')->default(0);
            $table->string('ext', 2000)->default('');
            $table->unsignedInteger('author');
            $table->string('version', 20);
            $table->integer('pos');
            $table->timestamp('ctime')->useCurrent();
            $table->timestamp('stime')->useCurrent();
            $table->timestamp('mtime')->useCurrentOnUpdate();
            $table->unsignedTinyInteger('deleted')->default(0);
            
            $table->index(['space_id', 'node_id'], 'space_id_node_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('article');
    }
}
