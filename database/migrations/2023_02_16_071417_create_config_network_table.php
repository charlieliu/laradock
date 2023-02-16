<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigNetworkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('config_network', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique();
            $table->string('zh_name');
            $table->string('en_name');
            $table->unsignedTinyInteger('is_open');
            $table->string('link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('config_network');
    }
}
