<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quotes', function (Blueprint $table)
        {
            $table->id();
            $table->string('author', 255);
            $table->string('content', 255);
            $table->timestamps();
        });

        DB::table('quotes')->insert([
            [
                'author'        => '愛迪生',
                'content'       => '失敗為成功之母',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s')
            ],[
                'author'        => '李奧納多‧達文西',
                'content'       => '簡潔是最終的精密',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s')
            ],[
                'author'        => '荷拉斯',
                'content'       => '好的開始是成功的一半',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quotes');
    }
}
