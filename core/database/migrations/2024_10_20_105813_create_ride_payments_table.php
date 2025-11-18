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
        Schema::create('ride_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('ride_id')->unsigned()->default(0);
            $table->integer('rider_id')->unsigned()->default(0);
            $table->integer('driver_id')->unsigned()->default(0);
            $table->decimal('amount', 28, 8)->default(0);
            $table->boolean('payment_type')->comment('gateway=1;cash= 2;wallet=3');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ride_payments');
    }
};
