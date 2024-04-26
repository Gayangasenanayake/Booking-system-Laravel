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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->references('id')->on('customers');
            $table->date('date');
            $table->time('time');
            $table->string('reference')->default('0');
            $table->string('status')->default('confirm');
            $table->integer('participants')->default(0);
            $table->float('paid')->default(0);
            $table->float('sub_total')->default(0);
            $table->float('tax')->default(0);
            $table->float('total')->default(0);
            $table->boolean('is_refunded')->default(false);
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
        Schema::dropIfExists('bookings');
    }
};
