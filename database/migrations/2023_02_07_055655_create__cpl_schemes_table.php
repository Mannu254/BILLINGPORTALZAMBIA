<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCplSchemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('_cpl_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('scheme_name');
            $table->integer('cost_code');
            $table->integer('calcbase');
            $table->integer('rate');
            $table->integer('vat');          
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
        Schema::dropIfExists('_cpl_schemes');
    }
}
