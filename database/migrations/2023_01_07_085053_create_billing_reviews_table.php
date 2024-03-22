<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_reviews', function (Blueprint $table) {
            $table->id();
            $table->Biginteger('asset_id');
            $table->BigInteger('DCLink');
            $table->string('ucSABillingAsset',50);
            $table->Biginteger('mono_pmr');
            $table->Biginteger('mono_cmr');
            $table->Biginteger('color_pmr');
            $table->Biginteger('color_cmr');

            $table->Biginteger('scn_pmr');
            $table->Biginteger('scn_cmr');
            $table->BigInteger('user_id');


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
        Schema::dropIfExists('billing_reviews');
    }
}
