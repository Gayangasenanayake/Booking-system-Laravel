<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_pricing_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->references('id')->on('activities');
            $table->float('base_price', 8, 2)->default(0.00);
            $table->float('advertised_price', 8, 2)->default(0.00);
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
        Schema::dropIfExists('activity_pricing_infos');
    }
};
