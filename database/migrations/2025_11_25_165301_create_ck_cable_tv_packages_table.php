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
        Schema::create('ck_cable_tv_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ck_cable_tv_id')->constrained()->cascadeOnDelete();
            $table->string('cable_tv');
            $table->string('package_code');
            $table->string('description');
            $table->decimal('price', 10, 2);
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
        Schema::dropIfExists('ck_cable_tv_packages');
    }
};
