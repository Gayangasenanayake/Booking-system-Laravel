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
        Schema::create('staff_member_schedule_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_member_id');
            $table->unsignedBigInteger('schedule_group_id');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->foreign('staff_member_id')->references('id')->on('staff_members')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('schedule_group_id')->references('id')->on('schedule_groups')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('staff_member_schedule_groups');
    }
};
