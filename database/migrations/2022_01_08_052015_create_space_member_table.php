<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpaceMemberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('space_member', function (Blueprint $table) {
            $table->unsignedInteger('space_id');
            $table->unsignedInteger('uid');
            $table->unsignedInteger('role');
            $table->timestamp('ctime')->useCurrent();
            $table->timestamp('mtime')->useCurrentOnUpdate();
            $table->unsignedTinyInteger('deleted')->default(0);
            
            $table->primary(['space_id', 'uid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('space_member');
    }
}
