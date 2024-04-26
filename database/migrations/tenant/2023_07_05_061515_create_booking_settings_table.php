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
        Schema::create('booking_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_available_to_book')->default(false);
            $table->string('calender_style')->default('week-normal-view');
            $table->foreignId('activity_id')->references('id')->on('activities')->cascadeOnDelete();
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
        Schema::dropIfExists('booking_settings');
    }
};
