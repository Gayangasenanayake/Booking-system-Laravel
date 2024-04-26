<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('price_tiers', function (Blueprint $table) {
            $table->date('effective_from_date')->nullable();
            $table->date('effective_to_date')->nullable();
            $table->integer('minimum_number_of_participants')->nullable();
            $table->integer('maximum_number_of_participants')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_tiers', function (Blueprint $table) {
            //
        });
    }
};
