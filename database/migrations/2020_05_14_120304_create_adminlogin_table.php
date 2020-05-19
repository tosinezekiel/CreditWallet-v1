<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminloginTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adminlogin', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('authid')->index();
            $table->string('email');
            $table->string('password');
            $table->string('firstname');
            $table->string('lastname');
            $table->string('staff_id');
            $table->string('position');
            $table->string('department');
            $table->text('girotoken');
            $table->string('waveid', 255);
            $table->string('v1_token', 255)->nullable();
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
        Schema::dropIfExists('adminlogin');
    }
}
