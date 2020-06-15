<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvestmentstartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('investmentstarts', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');
            $table->integer('duration');
            $table->string('referal_code')->nullable();
            $table->string('investment_start_date');
            $table->integer('savings_id');
            $table->integer('next_interest');
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
        Schema::dropIfExists('investmentstarts');
    }
}
