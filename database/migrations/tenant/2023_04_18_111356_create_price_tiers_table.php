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
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('price', 8, 2);
            $table->float('advertised_price', 8, 2);
            $table->boolean('is_deleted')->default(false);
            $table->foreignId('activity_id')->references('id')->on('activities');
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
        Schema::dropIfExists('price_tiers');
    }
};
