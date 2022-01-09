<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('article_id')->index('article_id');
            $table->string('version', 20);
            $table->string('title', 200);
            $table->mediumText('body');
            $table->mediumText('search');
            $table->string('ext', 2048)->default('');
            $table->unsignedInteger('author');
            $table->timestamp('stime');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('article_history');
    }
}
