<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('waste_picks', function (Blueprint $table) {
            $table->enum('waste_type', ['household_waste', 'bulk_waste', 'recyclables'])->change();
        });
    }

    public function down()
    {
        Schema::table('waste_picks', function (Blueprint $table) {
            $table->tinyInteger('waste_type')->comment('1=solid, 2=liquid')->change();
        });
    }
};
