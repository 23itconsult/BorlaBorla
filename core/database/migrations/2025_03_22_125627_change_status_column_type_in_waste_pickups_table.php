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
        Schema::table('waste_pickups', function (Blueprint $table) {
            $table->enum('status', [
                'pickup_pending',    // 0
                'pickup_completed',  // 1
                'pickup_active',     // 2
                'pickup_running',    // 3
                'pickup_canceled'    // 9
            ])->default('pickup_pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waste_pickups', function (Blueprint $table) {
            $table->string('status')->default('pickup_pending')->change();
        });
    }
};
