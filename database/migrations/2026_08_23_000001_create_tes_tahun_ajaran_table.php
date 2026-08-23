<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel penghubung: penugasan Tes ke Tahun Ajaran (periode).
 *
 * Tujuan: satu tes (bank soal) bisa dipakai di banyak tahun ajaran. Admin
 * menentukan "periode ini pakai tes mana". Peserta hanya melihat tes yang
 * ditugaskan ke tahun ajaran-nya.
 *
 * ATURAN FALLBACK (disepakati): tes yang BELUM ditugaskan ke tahun ajaran mana
 * pun dianggap berlaku untuk SEMUA periode (perilaku lama), agar tes existing
 * tidak hilang dari peserta.
 *
 * Aman: hanya CREATE TABLE, tidak menyentuh/menghapus data yang ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tes_tahun_ajaran')) {
            return;
        }

        Schema::create('tes_tahun_ajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tes_id')->constrained('tes')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tes_id', 'tahun_ajaran_id']);
            $table->index('tahun_ajaran_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_tahun_ajaran');
    }
};
