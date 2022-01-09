<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attachment', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('space_id');
            $table->unsignedInteger('node_id');
            $table->unsignedInteger('article_id')->default(0);
            $table->string('original_filename', 300);
            $table->string('store_filename', 300);
            $table->string('extension', 50);
            $table->unsignedInteger('size');
            $table->unsignedInteger('chunk_count')->default(0);
            $table->unsignedInteger('chunk_progress')->default(0);
            $table->unsignedInteger('chunk_size')->default(0);
            $table->unsignedTinyInteger('chunk_finished')->default(0);
            $table->unsignedInteger('uploader');
            $table->timestamp('ctime')->useCurrent();
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
        Schema::dropIfExists('attachment');
    }
}
