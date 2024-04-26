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
        Schema::create('schedule_groups', function (Blueprint $table) {
            $table->id();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('day')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('allocated_slots')->nullable();
            $table->integer('booked_slots')->nullable();
            $table->integer('min_number_of_places')->nullable();
            $table->integer('max_number_of_places')->nullable();
            $table->foreignId('price_tier_id')->nullable()->references('id')->on('price_tiers');
            $table->foreignId('activity_id')->nullable()->references('id')->on('activities');
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_published')->default(true);
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
        Schema::dropIfExists('schedule_groups');
    }
};
