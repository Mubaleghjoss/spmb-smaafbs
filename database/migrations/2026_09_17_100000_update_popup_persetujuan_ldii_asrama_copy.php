<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_COPY = 'Saya bersedia mengikuti peraturan dan ketentuan yang berlaku di SMA AFBS yang berada di bawah naungan LDII (Lembaga Dakwah Islam Indonesia) melalui Yayasan Dar Al Furqon Al Hakim.';

    private const NEW_COPY = 'SMA AFBS yang dikelola oleh Lembaga Dakwah Islam Indonesia (LDII) melalui Yayasan Dar Al Furqon Al Hakim. Bersedia tinggal dan berkegiatan di Asrama.';

    /**
     * Update only the legacy default so an administrator's custom copy remains untouched.
     */
    public function up(): void
    {
        DB::table('pengaturan')
            ->where('kunci', 'popup_persetujuan_teks')
            ->where('nilai', self::OLD_COPY)
            ->update(['nilai' => self::NEW_COPY, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('pengaturan')
            ->where('kunci', 'popup_persetujuan_teks')
            ->where('nilai', self::NEW_COPY)
            ->update(['nilai' => self::OLD_COPY, 'updated_at' => now()]);
    }
};
