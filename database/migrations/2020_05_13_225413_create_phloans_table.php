<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhloansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('phloans', function (Blueprint $table) {
            $table->id();
            $table->string('authid');
            $table->string('title');
            $table->text('description');
            $table->string('type');
            $table->string('initial_approved_by');
            $table->string('final_approved_by');
            $table->string('initial_approved_date');
            $table->string('final_approved_date');
            $table->string('created_by');
            $table->integer('status');
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
        Schema::dropIfExists('phloans');
    }
}
