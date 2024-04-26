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
        Schema::create('widget_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->references('id')->on('widgets')->cascadeOnDelete();
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
        Schema::dropIfExists('widget_activities');
    }
};
