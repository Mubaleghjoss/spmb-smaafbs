<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\TahapanSpmb;
use App\Models\TahunAjaran;
use App\Models\GelombangPendaftaran;
use App\Models\FormulirSpmb;
use App\Models\GayaBelajarConfig;
use App\Models\HasilGayaBelajar;
use App\Models\HasilPsikotesKepribadian;
use App\Models\PsikotesKepribadianConfig;
use App\Models\SesiTes;
use App\Models\Tes;
use App\Helpers\NomorPendaftaranHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Service untuk impor dan ekspor peserta
 * Kebutuhan: 3.2
 */
class ImporEksporPesertaService
{
    private const REKAP_PASSWORD_DEFAULT = 'password123';
    private const REKAP_EMAIL_DOMAIN = 'import.spmb.local';

    public function __construct(
        private PeriodePendaftaranService $periodePendaftaranService
    ) {}

    /**
     * Impor peserta dari file Excel
     * Format: Nama | Email | Telepon | Alamat | Asal Sekolah
     */
    public function imporDariExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Skip header row
        array_shift($rows);

        $hasil = [
            'sukses' => 0,
            'gagal' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $baris = $index + 2;

            // Skip baris kosong
            if (empty(array_filter($row))) {
                continue;
            }

            try {
                $this->validasiBarisExcel($row, $baris);
                $this->simpanPesertaDariExcel($row);
                $hasil['sukses']++;
            } catch (\Exception $e) {
                $hasil['gagal']++;
                $hasil['errors'][] = "Baris {$baris}: " . $e->getMessage();
            }
        }

        return $hasil;
    }

    /**
     * Validasi baris Excel
     */
    private function validasiBarisExcel(array $row, int $baris): void
    {
        if (empty($row[0])) {
            throw new \Exception('Nama tidak boleh kosong');
        }

        if (empty($row[1])) {
            throw new \Exception('Email tidak boleh kosong');
        }

        if (!filter_var($row[1], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Format email tidak valid');
        }

        // Cek email sudah ada
        if (Peserta::where('email', $row[1])->exists()) {
            throw new \Exception('Email sudah terdaftar');
        }
    }

    /**
     * Simpan peserta dari baris Excel
     */
    private function simpanPesertaDariExcel(array $row): Peserta
    {
        return DB::transaction(function () use ($row) {
            $kategori = $this->kategoriDariBaris($row);

            // Password akan otomatis di-hash oleh model cast 'hashed'
            $peserta = Peserta::create([
                'nomor_pendaftaran' => NomorPendaftaranHelper::generate(),
                'nama' => $row[0],
                'email' => $row[1],
                'password' => 'password123',
                'telepon' => $row[2] ?? null,
                'alamat' => $row[3] ?? null,
                'asal_sekolah' => $row[4] ?? null,
                ...$kategori,
            ]);

            TahapanSpmb::create([
                'peserta_id' => $peserta->id,
                'tahap_saat_ini' => 1,
                'tahap_1_selesai' => true,
            ]);

            return $peserta;
        });
    }

    /**
     * Ekspor peserta ke Excel
     */
    public function eksporKeExcel(?int $grupId = null): string
    {
        $query = Peserta::with([
            'tahapanSpmb',
            'grup',
            'tahunAjaran',
            'gelombangPendaftaran',
        ]);

        if ($grupId) {
            $query->whereHas('grup', fn($q) => $q->where('grup.id', $grupId));
        }

        $pesertaList = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = [
            'No. Pendaftaran', 'Nama', 'Email', 'Telepon', 
            'Alamat', 'Asal Sekolah', 'Tahun Ajaran', 'Gelombang',
            'Jenis Pendaftaran', 'Kelas Tujuan', 'Kelas Penempatan', 'Grup', 'Tahap Saat Ini', 'Terdaftar'
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);

        // Data
        $row = 2;
        foreach ($pesertaList as $peserta) {
            $data = [
                $peserta->nomor_pendaftaran,
                $peserta->nama,
                $peserta->email,
                $peserta->telepon ?? '',
                $peserta->alamat ?? '',
                $peserta->asal_sekolah ?? '',
                $peserta->tahunAjaran?->nama ?? '',
                $peserta->gelombangPendaftaran?->nama ?? '',
                $peserta->jenis_pendaftaran_label,
                $peserta->kelas_tujuan ?? '',
                $peserta->kelas_penempatan ?? '',
                $peserta->grup->pluck('nama')->implode(', '),
                $peserta->tahap_saat_ini,
                $peserta->created_at->format('d/m/Y H:i'),
            ];

            $sheet->fromArray($data, null, "A{$row}");
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save to temp file
        $filename = 'ekspor_peserta_' . date('Y-m-d_His') . '.xlsx';
        $path = storage_path('app/public/exports/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    /**
     * Generate template Excel untuk impor
     */
    public function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = [
            'Nama*',
            'Email*',
            'Telepon',
            'Alamat',
            'Asal Sekolah',
            'Tahun Ajaran',
            'Gelombang',
            'Jenis Pendaftaran',
            'Kelas Tujuan',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        // Contoh data
        $default = $this->periodePendaftaranService->kategoriDefault();
        $tahun = TahunAjaran::query()->find($default['tahun_ajaran_id']);
        $gelombang = GelombangPendaftaran::query()->find($default['gelombang_pendaftaran_id']);
        $contoh = [
            'Ahmad Fauzi',
            'ahmad@email.com',
            '08123456789',
            'Jl. Contoh No. 1',
            'SMP Negeri 1',
            $tahun?->nama,
            $gelombang?->nama,
            'siswa_baru',
            10,
        ];
        $sheet->fromArray($contoh, null, 'A2');

        // Instruksi
        $sheet->setCellValue('A4', 'INSTRUKSI:');
        $sheet->setCellValue('A5', '- Kolom dengan tanda * wajib diisi');
        $sheet->setCellValue('A6', '- Email harus unik (belum terdaftar)');
        $sheet->setCellValue('A7', '- Password default: password123');
        $sheet->setCellValue('A8', '- Kolom kategori boleh kosong dan akan memakai periode default');
        $sheet->setCellValue('A9', '- Jenis pendaftaran: siswa_baru atau pindahan');

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'template_impor_peserta.xlsx';
        $path = storage_path('app/public/exports/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    public function imporRekapSeleksi(string $filePath): array
    {
        $preview = $this->previewImporRekapSeleksi($filePath);

        if (! empty($preview['errors']) || ! empty($preview['conflicts'])) {
            return array_merge($this->hasilKosongRekap(), [
                'errors' => $preview['errors'],
                'warnings' => $preview['warnings'],
                'needs_confirmation' => ! empty($preview['conflicts']),
                'conflicts' => $preview['conflicts'],
                'preview' => $preview,
            ]);
        }

        return $this->terapkanPreviewRekapSeleksi($preview, []);
    }

    public function previewImporRekapSeleksi(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $preview = [
            'rows' => [],
            'conflicts' => [],
            'errors' => [],
            'warnings' => [],
            'summary' => [
                'total' => 0,
                'baru' => 0,
                'tidak_berubah' => 0,
                'konflik' => 0,
                'gagal' => 0,
            ],
        ];

        if (empty($rows)) {
            $preview['errors'][] = 'File kosong.';

            return $preview;
        }

        $headers = array_shift($rows);
        $headerMap = $this->rekapHeaderMap($headers);
        $context = $this->ambilKonteksRekapSeleksi();

        if (! empty($context['missing'])) {
            $preview['errors'][] = 'Konfigurasi tes belum lengkap: ' . implode(', ', $context['missing']) . '.';

            return $preview;
        }

        foreach ($rows as $index => $row) {
            $baris = $index + 2;

            if (empty(array_filter($row, fn ($value) => filled($value)))) {
                continue;
            }

            try {
                $data = $this->dataRekapDariBaris($row, $headerMap);
                $match = $this->cariPesertaRekap($data);
                $preview['summary']['total']++;

                $item = [
                    'row_id' => 'baris_' . $baris,
                    'baris' => $baris,
                    'data' => $data,
                    'peserta_id' => $match['peserta']?->id,
                    'peserta' => $match['peserta'] ? $this->ringkasPesertaRekap($match['peserta']) : null,
                    'differences' => [],
                ];

                if (! empty($match['ambiguous'])) {
                    throw new \RuntimeException('Ditemukan lebih dari satu peserta dengan nama dan asal SMP yang sama.');
                }

                foreach ($data['warnings'] as $warning) {
                    $preview['warnings'][] = "Baris {$baris}: {$warning}";
                }

                if (! $match['peserta']) {
                    $preview['summary']['baru']++;
                    $preview['rows'][] = $item;
                    continue;
                }

                $item['differences'] = $this->bedaRekapDenganPeserta($match['peserta'], $data, $context);

                if (! empty($item['differences'])) {
                    $preview['summary']['konflik']++;
                    $preview['conflicts'][] = $item;
                } else {
                    $preview['summary']['tidak_berubah']++;
                }

                $preview['rows'][] = $item;
            } catch (\Throwable $e) {
                $preview['summary']['gagal']++;
                $preview['errors'][] = "Baris {$baris}: " . $e->getMessage();
            }
        }

        return $preview;
    }

    public function terapkanPreviewRekapSeleksi(array $preview, array $keputusan): array
    {
        $hasil = $this->hasilKosongRekap();

        if (! empty($preview['errors'])) {
            $hasil['errors'] = $preview['errors'];

            return $hasil;
        }

        $context = $this->ambilKonteksRekapSeleksi();

        if (! empty($context['missing'])) {
            $hasil['errors'][] = 'Konfigurasi tes belum lengkap: ' . implode(', ', $context['missing']) . '.';

            return $hasil;
        }

        foreach ($preview['rows'] ?? [] as $item) {
            $baris = (int) ($item['baris'] ?? 0);
            $data = $item['data'] ?? [];
            $rowId = (string) ($item['row_id'] ?? '');
            $hasConflict = ! empty($item['differences']);
            $pilihan = $keputusan[$rowId] ?? ($hasConflict ? null : 'baru');

            try {
                if ($hasConflict && ! in_array($pilihan, ['baru', 'lama'], true)) {
                    throw new \RuntimeException('Pilih tindakan: pakai data baru atau pertahankan data lama.');
                }

                if ($hasConflict && $pilihan === 'lama') {
                    $hasil['sukses']++;
                    $hasil['tidak_berubah']++;
                    continue;
                }

                if (! $hasConflict && ! empty($item['peserta_id'])) {
                    $hasil['sukses']++;
                    $hasil['tidak_berubah']++;
                    continue;
                }

                $result = DB::transaction(fn () => $this->simpanDataRekapSeleksi($data, $context));

                $hasil['sukses']++;
                $hasil[$result['created'] ? 'baru' : 'update']++;

                foreach ($data['warnings'] ?? [] as $warning) {
                    $hasil['warnings'][] = "Baris {$baris}: {$warning}";
                }
            } catch (\Throwable $e) {
                $hasil['gagal']++;
                $hasil['errors'][] = "Baris {$baris}: " . $e->getMessage();
            }
        }

        return $hasil;
    }

    public function generateTemplateRekapSeleksi(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'NO',
            'NAMA',
            'JK',
            'ASAL SMP',
            'PERSONALITY PLUS',
            'MODALITAS',
            'INDO',
            'INGG',
            'MTK',
            'IPA',
            'JML',
            'KLS',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);

        $sheet->fromArray([
            1,
            'Muhammad Baihaqi Asshiddiqi',
            'L',
            'SMP NEGERI 9 KOTA TANGERANG',
            'Plegmatis',
            'Visual dan Auditori',
            70,
            80,
            90,
            90,
            330,
            'X1',
        ], null, 'A2');

        $sheet->setCellValue('A4', 'INSTRUKSI:');
        $sheet->setCellValue('A5', '- NAMA, ASAL SMP, PERSONALITY PLUS, MODALITAS, INDO, INGG, MTK, IPA wajib diisi.');
        $sheet->setCellValue('A6', '- Email dan No HP tidak wajib; sistem akan membuat login internal otomatis.');
        $sheet->setCellValue('A7', '- Nilai JML dipakai untuk validasi total, mismatch tetap diimpor sebagai peringatan.');
        $sheet->setCellValue('A8', '- Peserta hasil import otomatis ditandai lulus final.');

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'template_impor_rekap_seleksi.xlsx';
        $path = storage_path('app/public/exports/' . $filename);

        if (! file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    private function kategoriDariBaris(array $row): array
    {
        $tahunNama = trim((string) ($row[5] ?? ''));
        $gelombangNama = trim((string) ($row[6] ?? ''));
        $jenis = trim((string) ($row[7] ?? ''));
        $kelas = $row[8] ?? null;

        if ($tahunNama === '' && $gelombangNama === '' && $jenis === '' && blank($kelas)) {
            $default = $this->periodePendaftaranService->kategoriDefault();

            return $this->periodePendaftaranService->validasiKategori($default);
        }

        $tahunNama = $this->periodePendaftaranService->normalisasiNamaTahun($tahunNama);
        $tahun = TahunAjaran::query()->where('nama', $tahunNama)->first();
        if (!$tahun) {
            throw new \Exception("Tahun ajaran {$tahunNama} tidak ditemukan");
        }

        $gelombang = $tahun->gelombangPendaftaran()
            ->where('nama', $gelombangNama)
            ->first();
        if (!$gelombang) {
            throw new \Exception("Gelombang {$gelombangNama} tidak ditemukan pada {$tahunNama}");
        }

        return $this->periodePendaftaranService->validasiKategori([
            'tahun_ajaran_id' => $tahun->id,
            'gelombang_pendaftaran_id' => $gelombang->id,
            'jenis_pendaftaran' => $jenis,
            'kelas_tujuan' => $kelas,
        ]);
    }

    private function hasilKosongRekap(): array
    {
        return [
            'sukses' => 0,
            'baru' => 0,
            'update' => 0,
            'tidak_berubah' => 0,
            'gagal' => 0,
            'errors' => [],
            'warnings' => [],
            'needs_confirmation' => false,
            'conflicts' => [],
        ];
    }

    private function dataRekapDariBaris(array $row, array $headerMap): array
    {
        $nama = $this->cellString($row, $headerMap, 'nama');
        $asalSmp = $this->cellString($row, $headerMap, 'asal_smp');

        if ($nama === '') {
            throw new \RuntimeException('Nama tidak boleh kosong.');
        }

        if ($asalSmp === '') {
            throw new \RuntimeException('Asal SMP tidak boleh kosong.');
        }

        $personality = $this->normalisasiKepribadian($this->cellString($row, $headerMap, 'personality'));
        $modalitas = $this->normalisasiModalitas($this->cellString($row, $headerMap, 'modalitas'));
        $nilaiAkademik = [
            'indo' => $this->cellNumber($row, $headerMap, 'indo', 'INDO'),
            'ingg' => $this->cellNumber($row, $headerMap, 'ingg', 'INGG'),
            'mtk' => $this->cellNumber($row, $headerMap, 'mtk', 'MTK'),
            'ipa' => $this->cellNumber($row, $headerMap, 'ipa', 'IPA'),
        ];

        $warnings = [];
        $jml = $this->cellNumberOrNull($row, $headerMap, 'jml');
        $total = array_sum($nilaiAkademik);
        if ($jml !== null && abs($jml - $total) > 0.01) {
            $warnings[] = "JML {$jml} berbeda dengan total nilai akademik {$total}.";
        }

        $nomorPendaftaran = $this->cellString($row, $headerMap, 'nomor_pendaftaran');
        $emailInput = strtolower($this->cellString($row, $headerMap, 'email'));
        $teleponInput = $this->cellString($row, $headerMap, 'telepon');
        $kelasPenempatan = Str::upper($this->cellString($row, $headerMap, 'kelas_penempatan'));
        $jenisKelamin = $this->normalisasiJenisKelamin($this->cellString($row, $headerMap, 'jk'));

        return [
            'nama' => $nama,
            'asal_smp' => $asalSmp,
            'personality' => $personality,
            'modalitas' => $modalitas,
            'nilai_akademik' => $nilaiAkademik,
            'jml' => $jml,
            'total' => $total,
            'nomor_pendaftaran' => $nomorPendaftaran,
            'email' => $emailInput,
            'telepon' => $teleponInput,
            'kelas_penempatan' => $kelasPenempatan,
            'jenis_kelamin' => $jenisKelamin,
            'warnings' => $warnings,
        ];
    }

    private function simpanBarisRekapSeleksi(array $row, array $headerMap, array $context): array
    {
        return $this->simpanDataRekapSeleksi(
            $this->dataRekapDariBaris($row, $headerMap),
            $context
        );
    }

    private function simpanDataRekapSeleksi(array $data, array $context): array
    {
        [$peserta, $created] = $this->cariAtauBuatPesertaRekap(
            $data['nama'],
            $data['asal_smp'],
            $data['nomor_pendaftaran'],
            $data['email'],
            $data['telepon'],
            $data['kelas_penempatan'],
            $context['kategori']
        );

        $this->sinkronFormulirRekap($peserta, $data['nama'], $data['asal_smp'], $data['jenis_kelamin']);
        $this->simpanHasilKepribadian($peserta, $context['tes']['kepribadian'], $data['personality']);
        $this->simpanHasilGayaBelajar($peserta, $context['tes']['gaya_belajar'], $data['modalitas']);

        foreach ($data['nilai_akademik'] as $kode => $nilai) {
            $this->simpanSesiNilai($peserta, $context['tes']['akademik'][$kode], $nilai);
        }

        $this->tandaiLulusFinal($peserta);

        return [
            'created' => $created,
            'warnings' => $data['warnings'],
        ];
    }

    /**
     * @return array{peserta:?Peserta, ambiguous:bool}
     */
    private function cariPesertaRekap(array $data): array
    {
        $peserta = null;

        if (($data['nomor_pendaftaran'] ?? '') !== '') {
            $peserta = Peserta::query()->where('nomor_pendaftaran', $data['nomor_pendaftaran'])->first();
        }

        if (! $peserta && ($data['email'] ?? '') !== '') {
            $peserta = Peserta::query()->where('email', $data['email'])->first();
        }

        if (! $peserta && ($data['telepon'] ?? '') !== '') {
            $peserta = Peserta::query()->where('telepon', $data['telepon'])->first();
        }

        if (! $peserta) {
            $matches = Peserta::query()
                ->whereRaw('LOWER(TRIM(nama)) = ?', [Str::lower($data['nama'])])
                ->whereRaw("LOWER(TRIM(COALESCE(asal_sekolah, ''))) = ?", [Str::lower($data['asal_smp'])])
                ->get();

            if ($matches->count() > 1) {
                return ['peserta' => null, 'ambiguous' => true];
            }

            $peserta = $matches->first();
        }

        return ['peserta' => $peserta, 'ambiguous' => false];
    }

    /**
     * @return array<int,array{field:string,label:string,lama:mixed,baru:mixed}>
     */
    private function bedaRekapDenganPeserta(Peserta $peserta, array $data, array $context): array
    {
        $differences = [];

        $this->tambahBeda($differences, 'nama', 'Nama', $peserta->nama, $data['nama']);
        $this->tambahBeda($differences, 'asal_sekolah', 'Asal SMP', $peserta->asal_sekolah, $data['asal_smp']);

        if (($data['email'] ?? '') !== '') {
            $this->tambahBeda($differences, 'email', 'Email', $peserta->email, $data['email']);
        }

        if (($data['telepon'] ?? '') !== '') {
            $this->tambahBeda($differences, 'telepon', 'No HP/Login', $peserta->telepon, $data['telepon']);
        }

        if ($this->pesertaMendukungKelasPenempatan() && ($data['kelas_penempatan'] ?? '') !== '') {
            $this->tambahBeda($differences, 'kelas_penempatan', 'Kelas Penempatan', $peserta->kelas_penempatan, $data['kelas_penempatan']);
        }

        $formulir = $peserta->formulirSpmb;
        if (($data['jenis_kelamin'] ?? null) !== null) {
            $this->tambahBeda($differences, 'jenis_kelamin', 'Jenis Kelamin', $formulir?->jenis_kelamin, $data['jenis_kelamin']);
        }

        $sesiKepribadian = $this->sesiPesertaUntukTes($peserta, $context['tes']['kepribadian']);
        $this->tambahBeda(
            $differences,
            'personality',
            'Personality Plus',
            $sesiKepribadian?->hasilPsikotesKepribadian?->hasil_kepribadian,
            $data['personality']
        );

        $sesiGayaBelajar = $this->sesiPesertaUntukTes($peserta, $context['tes']['gaya_belajar']);
        $this->tambahBeda(
            $differences,
            'modalitas',
            'Modalitas',
            $sesiGayaBelajar?->hasilGayaBelajar?->hasil_gaya_belajar,
            $data['modalitas']
        );

        foreach ($data['nilai_akademik'] as $kode => $nilai) {
            $sesi = $this->sesiPesertaUntukTes($peserta, $context['tes']['akademik'][$kode]);
            $this->tambahBeda($differences, "nilai_{$kode}", strtoupper($kode), $sesi?->nilai, $nilai);
        }

        return $differences;
    }

    private function tambahBeda(array &$differences, string $field, string $label, mixed $lama, mixed $baru): void
    {
        $lamaNormal = $this->normalisasiNilaiBanding($lama);
        $baruNormal = $this->normalisasiNilaiBanding($baru);

        if ($lamaNormal === $baruNormal) {
            return;
        }

        $differences[] = [
            'field' => $field,
            'label' => $label,
            'lama' => $lama,
            'baru' => $baru,
        ];
    }

    private function normalisasiNilaiBanding(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        }

        return Str::lower(trim((string) $value));
    }

    private function sesiPesertaUntukTes(Peserta $peserta, Tes $tes): ?SesiTes
    {
        return SesiTes::query()
            ->with(['hasilPsikotesKepribadian', 'hasilGayaBelajar'])
            ->where('peserta_id', $peserta->id)
            ->where('tes_id', $tes->id)
            ->first();
    }

    private function ringkasPesertaRekap(Peserta $peserta): array
    {
        return [
            'id' => $peserta->id,
            'nomor_pendaftaran' => $peserta->nomor_pendaftaran,
            'nama' => $peserta->nama,
            'asal_sekolah' => $peserta->asal_sekolah,
            'email' => $peserta->email,
            'telepon' => $peserta->telepon,
        ];
    }

    private function cariAtauBuatPesertaRekap(
        string $nama,
        string $asalSmp,
        string $nomorPendaftaran,
        string $emailInput,
        string $teleponInput,
        string $kelasPenempatan,
        array $kategori
    ): array {
        $match = $this->cariPesertaRekap([
            'nomor_pendaftaran' => $nomorPendaftaran,
            'email' => $emailInput,
            'telepon' => $teleponInput,
            'nama' => $nama,
            'asal_smp' => $asalSmp,
        ]);

        if ($match['ambiguous']) {
            throw new \RuntimeException('Ditemukan lebih dari satu peserta dengan nama dan asal SMP yang sama.');
        }

        $peserta = $match['peserta'];
        $generated = $this->kredensialGeneratedRekap($nama, $asalSmp);
        $email = $emailInput !== '' ? $emailInput : ($peserta?->email ?: $generated['email']);
        $telepon = $teleponInput !== '' ? $teleponInput : ($peserta?->telepon ?: $generated['telepon']);

        if (! $peserta) {
            $dataPeserta = [
                'nomor_pendaftaran' => $nomorPendaftaran !== '' ? $nomorPendaftaran : NomorPendaftaranHelper::generate(),
                'tahun_ajaran_id' => $kategori['tahun_ajaran_id'],
                'gelombang_pendaftaran_id' => $kategori['gelombang_pendaftaran_id'],
                'jenis_pendaftaran' => $kategori['jenis_pendaftaran'],
                'kelas_tujuan' => $kategori['kelas_tujuan'],
                'nama' => $nama,
                'email' => $email,
                'telepon' => $telepon,
                'password' => self::REKAP_PASSWORD_DEFAULT,
                'password_temp' => self::REKAP_PASSWORD_DEFAULT,
                'asal_sekolah' => $asalSmp,
            ];

            if ($this->pesertaMendukungKelasPenempatan()) {
                $dataPeserta['kelas_penempatan'] = $kelasPenempatan !== '' ? $kelasPenempatan : null;
            }

            return [
                Peserta::query()->create($dataPeserta),
                true,
            ];
        }

        $dataUpdate = [
            'nama' => $nama,
            'email' => $email,
            'telepon' => $telepon,
            'asal_sekolah' => $asalSmp,
            'tahun_ajaran_id' => $peserta->tahun_ajaran_id ?: $kategori['tahun_ajaran_id'],
            'gelombang_pendaftaran_id' => $peserta->gelombang_pendaftaran_id ?: $kategori['gelombang_pendaftaran_id'],
            'jenis_pendaftaran' => $peserta->jenis_pendaftaran ?: $kategori['jenis_pendaftaran'],
            'kelas_tujuan' => $peserta->kelas_tujuan ?: $kategori['kelas_tujuan'],
        ];

        if ($this->pesertaMendukungKelasPenempatan()) {
            $dataUpdate['kelas_penempatan'] = $kelasPenempatan !== '' ? $kelasPenempatan : $peserta->kelas_penempatan;
        }

        $peserta->update($dataUpdate);

        return [$peserta->fresh(), false];
    }

    private function pesertaMendukungKelasPenempatan(): bool
    {
        static $hasColumn = null;

        return $hasColumn ??= Schema::hasColumn('peserta', 'kelas_penempatan');
    }

    private function sinkronFormulirRekap(Peserta $peserta, string $nama, string $asalSmp, ?string $jenisKelamin): void
    {
        FormulirSpmb::query()->updateOrCreate(
            ['peserta_id' => $peserta->id],
            [
                'nama_lengkap' => $nama,
                'jenis_kelamin' => $jenisKelamin,
                'asal_sekolah' => $asalSmp,
                'telepon' => $peserta->telepon,
                'email' => $peserta->email,
                'tanggal_daftar' => now()->toDateString(),
                'status_verifikasi' => 'terverifikasi',
            ]
        );
    }

    private function simpanHasilKepribadian(Peserta $peserta, Tes $tes, string $hasilKepribadian): void
    {
        $sesi = $this->simpanSesiNilai($peserta, $tes, null);
        $detail = collect(['koleris', 'sanguin', 'plegmatis', 'melankolis'])
            ->mapWithKeys(fn ($tipe) => [$tipe => $tipe === $hasilKepribadian ? 1 : 0])
            ->all();

        HasilPsikotesKepribadian::query()->updateOrCreate(
            ['sesi_tes_id' => $sesi->id],
            [
                'hasil_kepribadian' => $hasilKepribadian,
                'detail_nilai' => $detail,
            ]
        );
    }

    private function simpanHasilGayaBelajar(Peserta $peserta, Tes $tes, string $hasilGayaBelajar): void
    {
        $sesi = $this->simpanSesiNilai($peserta, $tes, null);
        $selected = explode(' & ', $hasilGayaBelajar);
        $detail = collect(['visual', 'auditori', 'kinestetik'])
            ->mapWithKeys(fn ($tipe) => [$tipe => in_array($tipe, $selected, true) ? 1 : 0])
            ->all();

        HasilGayaBelajar::query()->updateOrCreate(
            ['sesi_tes_id' => $sesi->id],
            [
                'hasil_gaya_belajar' => $hasilGayaBelajar,
                'detail_nilai' => $detail,
            ]
        );
    }

    private function simpanSesiNilai(Peserta $peserta, Tes $tes, float|int|null $nilai): SesiTes
    {
        $sesi = SesiTes::query()->firstOrNew([
            'peserta_id' => $peserta->id,
            'tes_id' => $tes->id,
        ]);

        $sesi->fill([
            'waktu_mulai' => $sesi->waktu_mulai ?: now(),
            'waktu_selesai' => now(),
            'nilai' => $nilai,
            'status' => 'selesai',
            'status_verifikasi_tes' => $nilai !== null && $nilai < (float) $tes->nilai_lulus ? 'diloloskan' : null,
            'catatan_verifikasi' => $nilai !== null && $nilai < (float) $tes->nilai_lulus
                ? 'Diloloskan dari import rekap seleksi final.'
                : null,
        ]);
        $sesi->save();

        return $sesi;
    }

    private function tandaiLulusFinal(Peserta $peserta): void
    {
        $tahapan = TahapanSpmb::query()->firstOrNew(['peserta_id' => $peserta->id]);
        $tahapan->fill([
            'tahap_saat_ini' => 7,
            'tahap_1_selesai' => true,
            'tahap_2_selesai' => true,
            'tahap_3_selesai' => true,
            'tahap_4_selesai' => true,
            'tahap_5_selesai' => true,
            'tahap_6_selesai' => true,
            'tahap_7_selesai' => true,
            'status_kelulusan' => 'lulus',
        ]);
        $tahapan->save();
    }

    private function ambilKonteksRekapSeleksi(): array
    {
        $kategori = $this->periodePendaftaranService->validasiKategori(
            $this->periodePendaftaranService->kategoriDefault()
        );

        $tes = [
            'kepribadian' => $this->cariTesKepribadian(),
            'gaya_belajar' => $this->cariTesGayaBelajar(),
            'akademik' => [
                'indo' => $this->cariTesByNama(['indonesia', 'bindo', 'bindonesia']),
                'ingg' => $this->cariTesByNama(['inggris', 'binggris', 'bingg']),
                'mtk' => $this->cariTesByNama(['mtk', 'matematika']),
                'ipa' => $this->cariTesByNama(['ipa']),
            ],
        ];

        $missing = [];
        if (! $tes['kepribadian']) {
            $missing[] = 'Personality Plus';
        }
        if (! $tes['gaya_belajar']) {
            $missing[] = 'Modalitas/Gaya Belajar';
        }
        foreach (['indo' => 'B. Indonesia', 'ingg' => 'B. Inggris', 'mtk' => 'MTK', 'ipa' => 'IPA'] as $kode => $label) {
            if (! $tes['akademik'][$kode]) {
                $missing[] = $label;
            }
        }

        return compact('kategori', 'tes', 'missing');
    }

    private function cariTesKepribadian(): ?Tes
    {
        $tesId = PsikotesKepribadianConfig::query()->value('tes_id');

        return $tesId ? Tes::query()->find($tesId) : $this->cariTesByNama(['personality', 'kepribadian', 'psikotes']);
    }

    private function cariTesGayaBelajar(): ?Tes
    {
        $tesId = GayaBelajarConfig::query()->where('aktif', true)->value('tes_id')
            ?: GayaBelajarConfig::query()->value('tes_id');

        return $tesId ? Tes::query()->find($tesId) : $this->cariTesByNama(['modalitas', 'gayabelajar']);
    }

    private function cariTesByNama(array $tokens): ?Tes
    {
        return Tes::query()
            ->get()
            ->first(function (Tes $tes) use ($tokens) {
                $nama = $this->normalisasiTeksPencarian($tes->nama);

                foreach ($tokens as $token) {
                    if (str_contains($nama, $this->normalisasiTeksPencarian($token))) {
                        return true;
                    }
                }

                return false;
            });
    }

    private function rekapHeaderMap(array $headers): array
    {
        $aliases = [
            'nomor_pendaftaran' => ['nomorpendaftaran', 'nopendaftaran', 'nodaftar', 'nopeserta'],
            'nama' => ['nama', 'namapeserta', 'namasiswa'],
            'jk' => ['jk', 'jeniskelamin', 'lp'],
            'asal_smp' => ['asalsmp', 'asalsekolah', 'asalsekolahsmp', 'smpasal'],
            'personality' => ['personalityplus', 'personality', 'kepribadian', 'psikotes'],
            'modalitas' => ['modalitas', 'gayabelajar', 'tesmodalitas'],
            'indo' => ['indo', 'indonesia', 'bindonesia', 'tesbindonesia'],
            'ingg' => ['ingg', 'inggris', 'binggris', 'tesbinggris'],
            'mtk' => ['mtk', 'matematika', 'tesmtk'],
            'ipa' => ['ipa', 'tesipa'],
            'jml' => ['jml', 'jumlah', 'total'],
            'kelas_penempatan' => ['kls', 'kelas', 'kelaspenempatan'],
            'email' => ['email', 'surel'],
            'telepon' => ['telepon', 'nohp', 'hp', 'nowa', 'whatsapp'],
        ];

        $normalizedHeaders = collect($headers)
            ->map(fn ($header) => $this->normalisasiHeader((string) $header))
            ->all();

        $map = [];
        foreach ($aliases as $field => $fieldAliases) {
            foreach ($fieldAliases as $alias) {
                $index = array_search($alias, $normalizedHeaders, true);
                if ($index !== false) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return array_replace([
            'nama' => 1,
            'jk' => 2,
            'asal_smp' => 3,
            'personality' => 4,
            'modalitas' => 5,
            'indo' => 6,
            'ingg' => 7,
            'mtk' => 8,
            'ipa' => 9,
            'jml' => 10,
            'kelas_penempatan' => 11,
        ], $map);
    }

    private function cellString(array $row, array $map, string $field): string
    {
        if (! array_key_exists($field, $map)) {
            return '';
        }

        return trim((string) ($row[$map[$field]] ?? ''));
    }

    private function cellNumber(array $row, array $map, string $field, string $label): float
    {
        $number = $this->cellNumberOrNull($row, $map, $field);

        if ($number === null) {
            throw new \RuntimeException("Nilai {$label} wajib berupa angka.");
        }

        return $number;
    }

    private function cellNumberOrNull(array $row, array $map, string $field): ?float
    {
        $value = $this->cellString($row, $map, $field);

        if ($value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', preg_replace('/[^0-9,.\-]/', '', $value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function normalisasiKepribadian(string $value): string
    {
        $normalized = $this->normalisasiTeksPencarian($value);
        $allowed = ['koleris', 'sanguin', 'plegmatis', 'melankolis'];

        foreach ($allowed as $tipe) {
            if (str_contains($normalized, $tipe)) {
                return $tipe;
            }
        }

        throw new \RuntimeException('Personality Plus tidak valid. Gunakan Koleris, Sanguin, Plegmatis, atau Melankolis.');
    }

    private function normalisasiModalitas(string $value): string
    {
        $normalized = $this->normalisasiTeksPencarian($value);
        $selected = [];

        foreach (['visual', 'auditori', 'kinestetik'] as $tipe) {
            if (str_contains($normalized, $tipe)) {
                $selected[] = $tipe;
            }
        }

        if (empty($selected)) {
            throw new \RuntimeException('Modalitas tidak valid. Gunakan Visual, Auditori, atau Kinestetik.');
        }

        return implode(' & ', $selected);
    }

    private function normalisasiJenisKelamin(string $value): ?string
    {
        $normalized = Str::upper(trim($value));

        return in_array($normalized, ['L', 'P'], true) ? $normalized : null;
    }

    private function kredensialGeneratedRekap(string $nama, string $asalSmp): array
    {
        $fingerprint = sha1(Str::lower($nama) . '|' . Str::lower($asalSmp));
        $slug = Str::slug($nama, '.');
        $slug = $slug !== '' ? Str::limit($slug, 40, '') : 'peserta';

        return [
            'email' => "{$slug}." . substr($fingerprint, 0, 10) . '@' . self::REKAP_EMAIL_DOMAIN,
            'telepon' => 'IMP' . strtoupper(substr($fingerprint, 0, 12)),
        ];
    }

    private function normalisasiHeader(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower($value)) ?? '';
    }

    private function normalisasiTeksPencarian(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower($value)) ?? '';
    }
}
