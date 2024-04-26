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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->text('subject');
            $table->text('body');
            $table->string('attachment_url')->nullable();
            $table->text('reply_email')->nullable();
            $table->time('from');
            $table->time('to');
            $table->integer('days');
            $table->string('after_or_before');
            $table->foreignId('activity_id')->references('id')->on('activities');
            $table->boolean('is_deleted')->default(false);
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
        Schema::dropIfExists('messages');
    }
};
