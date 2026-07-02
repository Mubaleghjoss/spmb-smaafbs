<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan field tambahan untuk formulir PPDB sesuai referensi
     */
    public function up(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            // Data kelahiran tambahan
            $table->string('provinsi_lahir')->nullable()->after('tempat_lahir');
            
            // Data fisik
            $table->decimal('tinggi_badan', 5, 1)->nullable()->after('agama');
            $table->decimal('berat_badan', 5, 1)->nullable()->after('tinggi_badan');
            $table->decimal('lingkar_kepala', 5, 1)->nullable()->after('berat_badan');
            
            // Data tambahan siswa
            $table->string('hobi')->nullable()->after('lingkar_kepala');
            $table->string('cita_cita')->nullable()->after('hobi');
            $table->string('prestasi')->nullable()->after('cita_cita');
            $table->integer('jumlah_saudara')->nullable()->after('prestasi');
            
            // Alamat detail
            $table->string('alamat_kelurahan')->nullable()->after('alamat');
            $table->string('alamat_kecamatan')->nullable()->after('alamat_kelurahan');
            $table->string('alamat_kota')->nullable()->after('alamat_kecamatan');
            $table->string('alamat_provinsi')->nullable()->after('alamat_kota');
            $table->string('desa')->nullable()->after('alamat_provinsi');
            $table->string('daerah')->nullable()->after('desa');
            $table->string('kelompok')->nullable()->after('daerah');
            
            // Telepon tambahan
            $table->string('telp_rumah')->nullable()->after('telepon');
            
            // Tanggal daftar
            $table->date('tanggal_daftar')->nullable()->after('nisn');
            
            // File dokumen
            $table->string('file_kk')->nullable()->after('tanggal_daftar');
            $table->string('file_akta')->nullable()->after('file_kk');
            $table->string('file_ijazah')->nullable()->after('file_akta');
            $table->string('file_bpjs')->nullable()->after('file_ijazah');
            $table->string('file_ktp_ibu')->nullable()->after('file_bpjs');
            $table->string('file_ktp_ayah')->nullable()->after('file_ktp_ibu');
            $table->string('foto')->nullable()->after('file_ktp_ayah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('formulir_spmb', function (Blueprint $table) {
            $table->dropColumn([
                'provinsi_lahir',
                'tinggi_badan',
                'berat_badan',
                'lingkar_kepala',
                'hobi',
                'cita_cita',
                'prestasi',
                'jumlah_saudara',
                'alamat_kelurahan',
                'alamat_kecamatan',
                'alamat_kota',
                'alamat_provinsi',
                'desa',
                'daerah',
                'kelompok',
                'telp_rumah',
                'tanggal_daftar',
                'file_kk',
                'file_akta',
                'file_ijazah',
                'file_bpjs',
                'file_ktp_ibu',
                'file_ktp_ayah',
                'foto',
            ]);
        });
    }
};
