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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('frequency');
            $table->float('price');
            $table->float('original_price');
            $table->text('summery')->nullable();
            $table->text('long_description')->nullable();
            $table->foreignId('activity_id')->nullable()->references('id')->on('activities');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
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
        Schema::dropIfExists('courses');
    }
};
