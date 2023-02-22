<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_histories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id')->nullable();
            $table->foreign('chat_id')
                ->references('id')
                ->on('chat')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->bigInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('user')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->bigInteger('message_id')->index();
            $table->timestamp('date');
            $table->text('text')->nullable();
            $table->text('entities')->nullable();
            $table->text('reply_markup')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_histories');
    }
}
