<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',60)->comment('用户名称');
            $table->integer('balance')->default(0)->comment('余额');
            $table->integer('total')->default(0)->comment('总额');
            $table->string('mobile',11)->unique()->comment('用户手机号');
            $table->string('password',155)->default(0)->comment('密码');
            $table->string('app_id',60)->default(0)->comment('AppID');
            $table->string('app_secret',60)->comment('appSecret');
            $table->tinyInteger('status') ->default(1)->comment('状态 0 禁用 1正常');
            $table->rememberToken()->comment('记住登陆状态');
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
        Schema::dropIfExists('users');
    }
}
