<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ck_data_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ck_mobile_network_id')->constrained()->cascadeOnDelete();
            $table->string('plan_code');      // e.g 500.0, 1000.01
            $table->string('description');    // e.g "1GB - 7 days (SME)"
            $table->decimal('price', 10, 2);  // N595.00
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
        Schema::dropIfExists('ck_data_plans');
    }
};
