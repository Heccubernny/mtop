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
        Schema::create('ck_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('request_id')->nullable();
            $table->string('order_id')->nullable();
            $table->string('provider')->nullable();
            $table->string('type');
            $table->string('network');
            $table->string('plan');
            $table->string('mobile');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            $table->longText('additional_info')->nullable();
            $table->longText('response_body')->nullable();
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
        Schema::dropIfExists('ck_transactions');
    }
};
