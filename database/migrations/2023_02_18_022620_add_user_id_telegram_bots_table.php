<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdTelegramBotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::table('telegram_bots', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('user')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('telegram_bots', function (Blueprint $table) {
            // in here i want to check column_one and column_two exists or not
            $table->dropColumn('user_id');
        });
    }
}
