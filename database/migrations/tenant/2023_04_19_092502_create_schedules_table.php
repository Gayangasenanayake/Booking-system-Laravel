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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('allocated_slots')->nullable();
            $table->integer('min_number_of_places')->nullable();
            $table->integer('max_number_of_places')->nullable();
            $table->foreignId('price_tier_id')->nullable()->references('id')->on('price_tiers');
            $table->foreignId('activity_id')->nullable()->references('id')->on('activities');
            $table->integer('booked_slots')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_published')->default(true);
            $table->foreignId('schedule_group_id')->nullable()->references('id')->on('schedule_groups');
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
        Schema::dropIfExists('schedules');
    }
};
