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
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel pengguna
            $table->foreignId('pengguna_id')->constrained('pengguna')->cascadeOnDelete();

            $table->date('tanggal')->index(); // Biar query laporan cepat
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();

            // Lokasi & Foto
            $table->decimal('latitude_masuk', 10, 8)->nullable();
            $table->decimal('longitude_masuk', 11, 8)->nullable();
            $table->string('foto_masuk')->nullable();

            // Status & Keterangan
            $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Alpa'])->default('Alpa');

            // Kolom Cerdas (Untuk Laporan)
            $table->boolean('terlambat')->default(false);
            $table->integer('menit_keterlambatan')->default(0);

            // Untuk Izin/Sakit
            $table->text('catatan')->nullable();
            $table->string('bukti_izin')->nullable(); // Foto surat

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
