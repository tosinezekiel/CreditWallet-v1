<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResourcerequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resourcerequests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('type');
            $table->integer('amount')->nullable();
            $table->string('initial_approved_by')->nullable();
            $table->string('final_approved_by')->nullable();
            $table->string('initial_approved_date')->nullable();
            $table->string('final_approved_date')->nullable();
            $table->string('authid');
            $table->integer('status')->default(0);
            $table->timestamps();

            $table->foreign('authid')->references('authid')->on('adminlogin')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resourcerequests');
    }
}
