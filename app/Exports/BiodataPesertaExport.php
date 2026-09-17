<?php

namespace App\Exports;

use App\Models\Peserta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BiodataPesertaExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Peserta::with(['formulirSpmb', 'wawancara', 'tahapanSpmb', 'tahunAjaran', 'gelombangPendaftaran'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No Pendaftaran', 'Nama Lengkap', 'Jenis Kelamin', 'Tahun Ajaran', 'Gelombang', 'Jenis Pendaftaran', 'Kelas Tujuan', 'Kelas Penempatan', 'Status Kuota',
            'Tempat Lahir', 'Provinsi Lahir', 'Tanggal Lahir', 'Asal Sekolah', 'NISN', 'Prestasi',
            'Tinggi Badan (cm)', 'Berat Badan (kg)', 'Lingkar Kepala (cm)', 'Lingkar Dada (cm)', 'Lingkar Pinggang (cm)', 'Panjang Celana/Rok (cm)', 'Ukuran Baju', 'Ukuran Celana',
            'Hobi', 'Cita-cita', 'Nama Ayah', 'Pekerjaan Ayah', 'Pendidikan Ayah', 'HP/WA Ayah', 'Nama Ibu', 'Pekerjaan Ibu', 'Pendidikan Ibu', 'HP/WA Ibu',
            'Kelurahan', 'Kecamatan', 'Kota/Kab', 'Provinsi', 'Telp Rumah', 'HP/WA Siswa', 'Jumlah Saudara', 'Kelompok', 'Desa', 'Daerah',
            'Tanggal Daftar', 'Nama KI Kelompok', 'No. HP KI Kelompok', 'Email', 'Tahap', 'Status Verifikasi',
        ];
    }

    /** @param Peserta $p */
    public function map($p): array
    {
        $f = $p->formulirSpmb;
        $suratOrtu = $p->wawancara?->surat_pernyataan_ortu ?? [];

        return [
            $p->nomor_pendaftaran, $f?->nama_lengkap ?? $p->nama, $f?->jenis_kelamin ?? '-',
            $p->tahunAjaran?->nama ?? '-', $p->gelombangPendaftaran?->nama ?? '-', $p->jenis_pendaftaran_label,
            $p->kelas_tujuan ? 'Kelas '.$p->kelas_tujuan : '-', $p->kelas_penempatan ?? '-', $p->status_kuota_label,
            $f?->tempat_lahir ?? '-', $f?->provinsi_lahir ?? '-', $f?->tanggal_lahir?->format('d/m/Y') ?? '-',
            $f?->asal_sekolah ?? '-', $f?->nisn ?? '-', $f?->prestasi ?? '-',
            $f?->tinggi_badan ?? '-', $f?->berat_badan ?? '-', $f?->lingkar_kepala ?? '-', $f?->lingkar_dada ?? '-', $f?->lingkar_pinggang ?? '-', $f?->panjang_celana ?? '-', $f?->ukuran_baju ?? '-', $f?->ukuran_celana ?? '-',
            $f?->hobi ?? '-', $f?->cita_cita ?? '-', $f?->nama_ayah ?? '-', $f?->pekerjaan_ayah ?? '-', $f?->pendidikan_ayah ?? '-', $f?->telepon_ayah ?? '-', $f?->nama_ibu ?? '-', $f?->pekerjaan_ibu ?? '-', $f?->pendidikan_ibu ?? '-', $f?->telepon_ibu ?? '-',
            $f?->alamat_kelurahan ?? '-', $f?->alamat_kecamatan ?? '-', $f?->alamat_kota ?? '-', $f?->alamat_provinsi ?? '-', $f?->telp_rumah ?? '-', $f?->telepon ?? $p->telepon ?? '-', $f?->jumlah_saudara ?? '-', $f?->kelompok ?? '-', $f?->desa ?? '-', $f?->daerah ?? '-',
            $f?->tanggal_daftar?->format('d/m/Y') ?? $p->created_at->format('d/m/Y'), $suratOrtu['nama_ki'] ?? '-', $suratOrtu['no_hp_ki'] ?? '-', $p->email ?? '-', 'Tahap '.$p->tahap_saat_ini, ucfirst($f?->status_verifikasi ?? 'belum isi'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'D9EAF7']]]];
    }
}
