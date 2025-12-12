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
        // 1. Tabel Utama: PENGGUNA (Dulu 'users')
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // Kita ubah 'name' jadi 'nama' biar full Indo
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // --- Custom Fields (Sesuai Request) ---
            $table->enum('role', ['admin', 'guru', 'siswa'])->default('siswa');
            $table->string('nisn_nip')->unique()->nullable();
            $table->string('foto_profil')->nullable();

            // Relasi ke Tabel Kelas (Pastikan file migration ini dijalankan SETELAH tabel kelas dibuat)
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();

            $table->rememberToken();
            $table->timestamps();
        });

        // 2. Tabel Token Reset Password (Bawaan Laravel, nama biarkan default/sesuaikan config)
        // Kita indonesiakan jadi 'token_reset_password' tapi nanti harus ubah config auth.
        // Agar AMAN dan TIDAK ERROR di sistem bawaan, kita biarkan nama tabel sistem ini bahasa inggris.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 3. Tabel Sessions (Bawaan Laravel)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // Kita arahkan user_id ke tabel pengguna (secara logika aja, tanpa constraint keras biar aman)
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengguna');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
