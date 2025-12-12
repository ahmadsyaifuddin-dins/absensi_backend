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
        Schema::create('pengaturan_sekolah', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude_kantor', 10, 8); // Titik pusat sekolah
            $table->decimal('longitude_kantor', 11, 8);
            $table->decimal('radius_km', 5, 2)->default(0.05); // 0.05 km = 50 meter
            $table->time('jam_masuk')->default('07:30:00');
            $table->time('jam_pulang')->default('15:00:00');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_sekolah');
    }
};
