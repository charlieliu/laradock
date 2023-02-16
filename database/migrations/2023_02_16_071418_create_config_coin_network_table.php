<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigCoinNetworkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('config_coin_network', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coin_id')->nullable();
            $table->foreign('coin_id')
                ->references('id')
                ->on('config_coin')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('network_id')->nullable();
            $table->foreign('network_id')
                ->references('id')
                ->on('config_network')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedTinyInteger('is_open');
            $table->unsignedTinyInteger('deposit_open');
            $table->unsignedTinyInteger('withdraw_open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('config_coin_network');
    }
}
