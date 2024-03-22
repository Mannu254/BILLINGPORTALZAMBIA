<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthlyreadingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monthlyreadings', function (Blueprint $table) {
            $table->id();
            $table->BigInteger('user_id');
            $table->string('customer_name');
            $table->string('serial_no', 64);
            $table->string('asset_code', 191)->nullable();
            $table->string('billing_cycle')->nullable();
            $table->string('branch')->nullable();
            $table->string('physical_area',191)->nullable();
            $table->date('reading_date');
            $table->string('mode_collection',191)->nullable();
            $table->string('description',191)->nullable();
            $table->BigInteger('mono_cmr')->nullable();
            $table->BigInteger('color_cmr')->nullable();

            $table->BigInteger('copies_mono')->nullable();
            $table->BigInteger('copies_col')->nullable();

            $table->BigInteger('a3mono_cmr')->nullable();
            $table->BigInteger('a3color_cmr')->nullable();
            $table->BigInteger('scan_cmr')->nullable();
            $table->string('remarks',191)->nullable();            
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monthlyreadings');
    }
}
