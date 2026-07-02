<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Controller untuk sinkronisasi data antara server local dan online.
 * Menyediakan API endpoint (export/import) dan aksi admin (tarik/push).
 */
class SyncController extends Controller
{
    /**
     * Tabel yang disinkronkan (urutan penting untuk foreign key).
     */
    private array $syncTables = [
        'peserta',
        'formulir_spmb',
        'tahapan_spmb',
        'pembayaran',
        'token',
        'sesi_tes',
        'jawaban_peserta',
        'hasil_gaya_belajar',
        'hasil_psikotes_kepribadian',
        'hasil_mbti',
        'hasil_profiling',
        'wawancara',
        'jadwal_wawancara',
        'peserta_wawancara',
        'log_tahapan_spmb',
        'grup_peserta',
        'token_global',
        'token_global_tes',
        'token_global_log',
    ];

    /**
     * Folder storage yang disinkronkan.
     */
    private array $syncFolders = [
        'formulir',
        'pembayaran',
        'spmb',
        'wawancara',
    ];

    // =========================================================================
    // API ENDPOINTS (dipanggil oleh server lain)
    // =========================================================================

    /**
     * API: Export data sebagai ZIP (dipanggil dari server lain).
     */
    public function eksporData(Request $request)
    {
        if (!$this->validateToken($request)) {
            return response()->json(['error' => 'Token tidak valid'], 403);
        }

        try {
            set_time_limit(600);
            ini_set('memory_limit', '1024M');

            $zipPath = $this->generateSyncZip();

            return response()->download($zipPath, 'sync-data.zip')->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Gagal export: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Import data dari ZIP (dipanggil dari server lain).
     */
    public function imporData(Request $request)
    {
        if (!$this->validateToken($request)) {
            return response()->json(['success' => false, 'error' => 'Token tidak valid'], 403);
        }

        if (!$request->hasFile('zip_file')) {
            return response()->json(['success' => false, 'error' => 'File ZIP tidak diterima. Cek upload_max_filesize di server.'], 400);
        }

        try {
            set_time_limit(600);
            ini_set('memory_limit', '1024M');

            $zipFile = $request->file('zip_file');
            $zipSize = $zipFile->getSize();
            $zipPath = $zipFile->getRealPath();

            // Validasi ZIP bukan kosong
            if ($zipSize < 100) {
                return response()->json([
                    'success' => false,
                    'error' => 'File ZIP terlalu kecil (' . $zipSize . ' bytes). Kemungkinan upload gagal/terpotong.',
                ], 400);
            }

            // Hitung record sebelum import
            $beforeCounts = [];
            foreach ($this->syncTables as $table) {
                try { $beforeCounts[$table] = DB::table($table)->count(); } catch (\Throwable $e) { $beforeCounts[$table] = 0; }
            }

            $result = $this->applySyncZip($zipPath);

            // Hitung record sesudah import untuk verifikasi
            $afterCounts = [];
            foreach ($this->syncTables as $table) {
                try { $afterCounts[$table] = DB::table($table)->count(); } catch (\Throwable $e) { $afterCounts[$table] = 0; }
            }

            // Build ringkasan detail
            $ringkasanDetail = 'ZIP: ' . round($zipSize / 1024) . 'KB | ' . $result['ringkasan'];
            $sumberIp = $request->ip();

            // Simpan log di sisi penerima
            try {
                DB::table('sync_logs')->insert([
                    'tipe' => 'tarik',
                    'status' => 'berhasil',
                    'server_url' => 'Push dari ' . $sumberIp,
                    'ringkasan' => 'Menerima push dari ' . $sumberIp . ' — ' . $ringkasanDetail,
                    'perubahan' => json_encode($result['perubahan']),
                    'konflik_resolved' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $logError) {
                // Log error tersimpan di ringkasan
                $ringkasanDetail .= ' | Log gagal: ' . $logError->getMessage();
            }

            return response()->json([
                'success' => true,
                'ringkasan' => $ringkasanDetail,
                'perubahan' => $result['perubahan'],
                'zip_size' => $zipSize,
                'before_counts' => $beforeCounts,
                'after_counts' => $afterCounts,
            ]);
        } catch (\Throwable $e) {
            $errorMsg = 'Gagal import: ' . $e->getMessage();

            try {
                DB::table('sync_logs')->insert([
                    'tipe' => 'tarik',
                    'status' => 'gagal',
                    'server_url' => 'Push dari ' . $request->ip(),
                    'ringkasan' => $errorMsg,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $logError) { /* ignore */ }

            return response()->json([
                'success' => false,
                'error' => $errorMsg,
            ], 500);
        }
    }

    // =========================================================================
    // ADMIN ACTIONS (dipanggil dari halaman pengaturan)
    // =========================================================================

    /**
     * Simpan konfigurasi sync (URL + Token) dari UI.
     */
    public function simpanKonfigSync(Request $request)
    {
        $request->validate([
            'sync_server_url' => 'required|url',
            'sync_token' => 'required|string|min:6',
        ]);

        Pengaturan::atur('sync_server_url', rtrim($request->sync_server_url, '/'));
        Pengaturan::atur('sync_token', $request->sync_token);

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi sync berhasil disimpan.',
        ]);
    }

    /**
     * Tes koneksi ke server online.
     */
    public function tesKoneksi()
    {
        $serverUrl = Pengaturan::ambil('sync_server_url');
        $token = Pengaturan::ambil('sync_token');

        if (empty($serverUrl) || empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'URL dan Token belum dikonfigurasi.',
            ]);
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Sync-Token' => $token])
                ->get("{$serverUrl}/api/sync/export");

            if ($response->status() === 403) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token ditolak oleh server. Pastikan token sama di kedua sisi.',
                ]);
            }

            if ($response->successful() || $response->status() === 200) {
                return response()->json([
                    'success' => true,
                    'message' => 'Koneksi berhasil! Server online siap disinkronkan.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Server merespon tapi status: ' . $response->status(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Tarik data dari server online ke local.
     */
    public function tarikDariOnline(Request $request)
    {
        $serverUrl = Pengaturan::ambil('sync_server_url');
        $token = Pengaturan::ambil('sync_token');

        if (empty($serverUrl) || empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi sync belum diisi. Silakan isi URL dan Token terlebih dahulu.',
            ], 400);
        }

        try {
            set_time_limit(600);
            ini_set('memory_limit', '1024M');

            // 1. Ambil data local untuk deteksi konflik
            $localPesertaIds = DB::table('peserta')->pluck('id')->toArray();
            $localFiles = $this->getStorageFileList();

            // 2. Download ZIP dari server online
            $response = Http::timeout(300)
                ->withHeaders(['X-Sync-Token' => $token])
                ->get("{$serverUrl}/api/sync/export");

            if (!$response->successful()) {
                throw new \Exception('Server online menolak: ' . $response->status() . ' - ' . $response->body());
            }

            // 3. Simpan ZIP ke temp
            $tempZip = tempnam(sys_get_temp_dir(), 'sync_') . '.zip';
            file_put_contents($tempZip, $response->body());

            // 4. Baca data online dari ZIP untuk deteksi konflik
            $onlinePesertaIds = $this->getPesertaIdsFromZip($tempZip);

            // 5. Deteksi konflik: data local yang tidak ada di online
            $konflikIds = array_diff($localPesertaIds, $onlinePesertaIds);
            $konflikData = [];

            if (!empty($konflikIds)) {
                $pesertaKonflik = DB::table('peserta')
                    ->whereIn('id', $konflikIds)
                    ->get(['id', 'nama', 'nomor_pendaftaran', 'created_at']);

                foreach ($pesertaKonflik as $p) {
                    $konflikData[] = [
                        'tipe' => 'peserta',
                        'id' => $p->id,
                        'keterangan' => "Peserta: {$p->nama} (No. {$p->nomor_pendaftaran})",
                        'detail' => "Terdaftar lokal pada " . ($p->created_at ?? '-'),
                    ];
                }
            }

            // Deteksi file local yang tidak ada di online
            $onlineFiles = $this->getFileListFromZip($tempZip);
            $localOnlyFiles = array_diff($localFiles, $onlineFiles);
            foreach ($localOnlyFiles as $file) {
                $konflikData[] = [
                    'tipe' => 'file',
                    'id' => $file,
                    'keterangan' => "File: {$file}",
                    'detail' => 'File ada di local tapi tidak ada di server online',
                ];
            }

            // 6. Apply data online ke local
            $result = $this->applySyncZip($tempZip);

            // 7. Cleanup
            @unlink($tempZip);

            // 8. Simpan log
            $status = empty($konflikData) ? 'berhasil' : 'konflik';
            $logId = DB::table('sync_logs')->insertGetId([
                'tipe' => 'tarik',
                'status' => $status,
                'server_url' => $serverUrl,
                'ringkasan' => $result['ringkasan'],
                'perubahan' => json_encode($result['perubahan']),
                'konflik' => !empty($konflikData) ? json_encode($konflikData) : null,
                'konflik_resolved' => empty($konflikData),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil tarik data dari server online.',
                'ringkasan' => $result['ringkasan'],
                'perubahan' => $result['perubahan'],
                'konflik' => $konflikData,
                'log_id' => $logId,
            ]);

        } catch (\Throwable $e) {
            // Log error
            DB::table('sync_logs')->insert([
                'tipe' => 'tarik',
                'status' => 'gagal',
                'server_url' => $serverUrl,
                'ringkasan' => 'Gagal: ' . $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal tarik data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Push data local ke server online.
     */
    public function pushKeOnline(Request $request)
    {
        $serverUrl = Pengaturan::ambil('sync_server_url');
        $token = Pengaturan::ambil('sync_token');

        if (empty($serverUrl) || empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi sync belum diisi. Silakan isi URL dan Token terlebih dahulu.',
            ], 400);
        }

        try {
            set_time_limit(600);
            ini_set('memory_limit', '1024M');

            // 1. Generate ZIP dari data local
            $zipPath = $this->generateSyncZip();
            $zipSize = filesize($zipPath);

            // 2. Hitung perubahan yang akan di-push
            $perubahan = $this->hitungPerubahan();

            // 3. Upload ZIP ke server online
            $zipContent = file_get_contents($zipPath);
            @unlink($zipPath);

            if ($zipContent === false || strlen($zipContent) < 100) {
                throw new \Exception('Gagal membaca file ZIP atau file terlalu kecil (' . strlen($zipContent) . ' bytes)');
            }

            $response = Http::timeout(300)
                ->withHeaders(['X-Sync-Token' => $token])
                ->attach('zip_file', $zipContent, 'sync-data.zip')
                ->post("{$serverUrl}/api/sync/import");

            $responseData = $response->json() ?? [];
            $httpStatus = $response->status();

            // Cek apakah server menolak
            if (!$response->successful()) {
                $errorMsg = $responseData['error'] ?? $response->body();
                throw new \Exception("Server menolak (HTTP {$httpStatus}): {$errorMsg}");
            }

            // Cek apakah server berhasil import
            if (isset($responseData['success']) && $responseData['success'] === false) {
                throw new \Exception('Server gagal import: ' . ($responseData['error'] ?? 'Unknown error'));
            }

            $serverRingkasan = $responseData['ringkasan'] ?? '-';
            $ringkasan = 'ZIP ' . round($zipSize/1024) . 'KB dikirim | Server: ' . $serverRingkasan;

            // Tambahkan info before/after counts jika ada dari server
            if (!empty($responseData['before_counts']) && !empty($responseData['after_counts'])) {
                foreach ($responseData['after_counts'] as $tabel => $after) {
                    $before = $responseData['before_counts'][$tabel] ?? 0;
                    if ($after !== $before) {
                        $diff = $after - $before;
                        $perubahan[] = [
                            'tipe' => 'server_verify',
                            'aksi' => $diff >= 0 ? 'tambah' : 'hapus',
                            'keterangan' => "Server `{$tabel}`: {$before} → {$after}",
                        ];
                    }
                }
            }

            // 5. Simpan log
            $logId = DB::table('sync_logs')->insertGetId([
                'tipe' => 'push',
                'status' => 'berhasil',
                'server_url' => $serverUrl,
                'ringkasan' => $ringkasan,
                'perubahan' => json_encode($perubahan),
                'konflik_resolved' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil push data ke server online.',
                'ringkasan' => $ringkasan,
                'perubahan' => $perubahan,
                'log_id' => $logId,
                'debug' => [
                    'zip_size' => $zipSize,
                    'http_status' => $httpStatus,
                    'server_response' => $serverRingkasan,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::table('sync_logs')->insert([
                'tipe' => 'push',
                'status' => 'gagal',
                'server_url' => $serverUrl ?? '-',
                'ringkasan' => 'Gagal: ' . $e->getMessage(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal push data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ambil riwayat sinkronisasi (AJAX).
     */
    public function riwayat()
    {
        $logs = DB::table('sync_logs')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($log) {
                $log->perubahan = json_decode($log->perubahan, true);
                $log->konflik = json_decode($log->konflik, true);
                $log->waktu = \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i');
                return $log;
            });

        return response()->json($logs);
    }

    /**
     * Resolve konflik: merge atau hapus data local yang tidak ada di online.
     */
    public function resolveKonflik(Request $request)
    {
        $request->validate([
            'log_id' => 'required|integer',
            'aksi' => 'required|in:merge,hapus',
            'items' => 'required|array',
        ]);

        $log = DB::table('sync_logs')->find($request->log_id);

        if (!$log) {
            return response()->json(['success' => false, 'message' => 'Log tidak ditemukan'], 404);
        }

        $konflik = json_decode($log->konflik, true) ?? [];
        $items = $request->items; // array of IDs / file paths to resolve

        try {
            if ($request->aksi === 'hapus') {
                // Hapus data local yang tidak ada di server
                foreach ($items as $item) {
                    if (isset($item['tipe']) && $item['tipe'] === 'peserta') {
                        // Hapus peserta dan data terkait
                        $pesertaId = $item['id'];
                        $sesiIds = DB::table('sesi_tes')->where('peserta_id', $pesertaId)->pluck('id')->toArray();

                        DB::statement('SET FOREIGN_KEY_CHECKS=0');
                        if (!empty($sesiIds)) {
                            DB::table('jawaban_peserta')->whereIn('sesi_tes_id', $sesiIds)->delete();
                            DB::table('hasil_gaya_belajar')->whereIn('sesi_tes_id', $sesiIds)->delete();
                            DB::table('hasil_psikotes_kepribadian')->whereIn('sesi_tes_id', $sesiIds)->delete();
                            DB::table('hasil_mbti')->whereIn('sesi_tes_id', $sesiIds)->delete();
                            DB::table('hasil_profiling')->whereIn('sesi_tes_id', $sesiIds)->delete();
                        }
                        DB::table('sesi_tes')->where('peserta_id', $pesertaId)->delete();
                        DB::table('wawancara')->where('peserta_id', $pesertaId)->delete();
                        DB::table('peserta_wawancara')->where('peserta_id', $pesertaId)->delete();
                        DB::table('pembayaran')->where('peserta_id', $pesertaId)->delete();
                        DB::table('log_tahapan_spmb')->where('peserta_id', $pesertaId)->delete();
                        DB::table('tahapan_spmb')->where('peserta_id', $pesertaId)->delete();
                        DB::table('formulir_spmb')->where('peserta_id', $pesertaId)->delete();
                        DB::table('grup_peserta')->where('peserta_id', $pesertaId)->delete();
                        DB::table('peserta')->where('id', $pesertaId)->delete();
                        DB::statement('SET FOREIGN_KEY_CHECKS=1');
                    } elseif (isset($item['tipe']) && $item['tipe'] === 'file') {
                        // Hapus file
                        $filePath = storage_path('app/public/' . $item['id']);
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                    }
                }
            }
            // aksi === 'merge' → biarkan data local tetap ada (tidak perlu aksi)

            // Mark konflik sebagai resolved
            DB::table('sync_logs')->where('id', $request->log_id)->update([
                'konflik_resolved' => true,
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->aksi === 'hapus'
                    ? 'Data konflik berhasil dihapus (mengikuti server).'
                    : 'Data konflik dipertahankan (disatukan dengan data server).',
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Validasi sync token dari request header.
     */
    private function validateToken(Request $request): bool
    {
        $token = Pengaturan::ambil('sync_token', env('SYNC_TOKEN'));
        $requestToken = $request->header('X-Sync-Token') ?? $request->query('token');
        return !empty($token) && $requestToken === $token;
    }

    /**
     * Generate ZIP berisi data.sql + files/ dari data local.
     */
    private function generateSyncZip(): string
    {
        $tempDir = sys_get_temp_dir();
        $zipPath = $tempDir . DIRECTORY_SEPARATOR . 'sync-' . uniqid() . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Gagal membuat file ZIP');
        }

        // 1. Export database tables to SQL
        $sql = $this->generateDataSql();
        $zip->addFromString('data.sql', $sql);

        // 2. Export peserta IDs list (untuk deteksi konflik)
        $pesertaIds = DB::table('peserta')->pluck('id')->toArray();
        $zip->addFromString('peserta_ids.json', json_encode($pesertaIds));

        // 3. Export storage files
        $storagePath = storage_path('app/public');
        foreach ($this->syncFolders as $folder) {
            $folderPath = $storagePath . DIRECTORY_SEPARATOR . $folder;
            if (!is_dir($folderPath)) continue;

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = $folder . '/' . substr($file->getPathname(), strlen($folderPath) + 1);
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $content = @file_get_contents($file->getPathname());
                    if ($content !== false) {
                        $zip->addFromString('files/' . $relativePath, $content);
                    }
                }
            }
        }

        // 4. File list for conflict detection
        $fileList = $this->getStorageFileList();
        $zip->addFromString('file_list.json', json_encode($fileList));

        $zip->close();
        return $zipPath;
    }

    /**
     * Apply ZIP data (import SQL + extract files).
     */
    private function applySyncZip(string $zipPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Gagal membuka file ZIP');
        }

        $perubahan = [];
        $ringkasan = '';

        // 1. Import SQL
        $sql = $zip->getFromName('data.sql');
        if ($sql !== false) {
            $importResult = $this->importDataSql($sql);
            $perubahan = array_merge($perubahan, $importResult['perubahan']);
            $ringkasan = $importResult['ringkasan'];
        }

        // 2. Extract files to storage
        $storagePath = storage_path('app/public');
        $fileCount = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (strpos($filename, 'files/') === 0 && substr($filename, -1) !== '/') {
                $relativePath = substr($filename, 6); // remove 'files/' prefix
                $targetPath = $storagePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

                // Create directory if needed
                $dir = dirname($targetPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    file_put_contents($targetPath, $content);
                    $fileCount++;
                }
            }
        }

        $zip->close();

        if ($fileCount > 0) {
            $perubahan[] = [
                'tipe' => 'file',
                'aksi' => 'sync',
                'keterangan' => "{$fileCount} file berhasil disinkronkan",
            ];
            $ringkasan .= " | {$fileCount} file disinkronkan";
        }

        return [
            'ringkasan' => $ringkasan,
            'perubahan' => $perubahan,
        ];
    }

    /**
     * Generate SQL untuk tabel-tabel sync.
     */
    private function generateDataSql(): string
    {
        $dbName = config('database.connections.mysql.database');

        $sql  = "-- Sync Data Export\n";
        $sql .= "-- Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: {$dbName}\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n\n";

        foreach ($this->syncTables as $tableName) {
            try {
                // Check if table exists
                $exists = DB::select("SHOW TABLES LIKE '{$tableName}'");
                if (empty($exists)) continue;

                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createSql = $createTable[0]->{'Create Table'} ?? '';

                $sql .= "-- Table: `{$tableName}`\n";
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= $createSql . ";\n\n";

                // Export data in chunks to save memory
                $offset = 0;
                $chunkSize = 500;
                $hasData = true;

                while ($hasData) {
                    $rows = DB::table($tableName)->offset($offset)->limit($chunkSize)->get();

                    if ($rows->isEmpty()) {
                        $hasData = false;
                        continue;
                    }

                    foreach ($rows as $row) {
                        $values = [];
                        foreach ((array) $row as $value) {
                            $values[] = $this->quoteSqlValue($value);
                        }
                        $sql .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                    }

                    $offset += $chunkSize;
                }

                $sql .= "\n";
            } catch (\Throwable $e) {
                $sql .= "-- Skipped `{$tableName}`: {$e->getMessage()}\n\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    private function quoteSqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        $escaped = strtr((string) $value, [
            "\\" => "\\\\",
            "\0" => "\\0",
            "\n" => "\\n",
            "\r" => "\\r",
            "\x1a" => "\\Z",
            "'" => "\\'",
        ]);

        return "'{$escaped}'";
    }

    private function hapusKomentarSql(string $sql): string
    {
        $lines = preg_split('/\R/', $sql);
        $clean = [];

        foreach ($lines as $line) {
            $trimmed = ltrim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }

            $clean[] = $line;
        }

        return implode("\n", $clean);
    }

    /**
     * Import SQL ke database.
     */
    private function importDataSql(string $sql): array
    {
        $perubahan = [];
        $totalQueries = 0;
        $totalErrors = 0;
        $errorDetails = [];

        // Count records before import for comparison
        $beforeCounts = [];
        foreach ($this->syncTables as $table) {
            try {
                $beforeCounts[$table] = DB::table($table)->count();
            } catch (\Throwable $e) {
                $beforeCounts[$table] = 0;
            }
        }

        // Execute SQL
        $sql = $this->hapusKomentarSql($sql);
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // SQL export dibuat satu statement per baris/blok dan nilai teks sudah di-escape tanpa newline mentah.
        $queries = preg_split('/;\s*(?:\r?\n|$)/', $sql);

        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query)) continue;

            try {
                DB::unprepared($query . ';');
                $totalQueries++;
            } catch (\Throwable $e) {
                $totalErrors++;
                // Simpan max 10 error detail untuk debugging
                if (count($errorDetails) < 10) {
                    $errorDetails[] = mb_substr($e->getMessage(), 0, 120) . ' | Query: ' . mb_substr($query, 0, 60);
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Count records after import for change detection
        foreach ($this->syncTables as $table) {
            try {
                $afterCount = DB::table($table)->count();
                $before = $beforeCounts[$table] ?? 0;

                if ($afterCount !== $before) {
                    $diff = $afterCount - $before;
                    $aksi = $diff > 0 ? 'tambah' : 'hapus';
                    $perubahan[] = [
                        'tipe' => 'tabel',
                        'tabel' => $table,
                        'aksi' => $aksi,
                        'keterangan' => "`{$table}`: {$before} → {$afterCount} (" . ($diff > 0 ? "+{$diff}" : $diff) . " record)",
                    ];
                }
            } catch (\Throwable $e) {
                // skip
            }
        }

        $ringkasan = "Query sukses: {$totalQueries}";
        if ($totalErrors > 0) {
            $ringkasan .= ", gagal: {$totalErrors}";
        }

        return [
            'ringkasan' => $ringkasan,
            'perubahan' => $perubahan,
            'errors' => $errorDetails,
        ];
    }

    /**
     * Ambil daftar file di storage sync folders.
     */
    private function getStorageFileList(): array
    {
        $files = [];
        $storagePath = storage_path('app/public');

        foreach ($this->syncFolders as $folder) {
            $folderPath = $storagePath . DIRECTORY_SEPARATOR . $folder;
            if (!is_dir($folderPath)) continue;

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = $folder . '/' . substr($file->getPathname(), strlen($folderPath) + 1);
                    $files[] = str_replace('\\', '/', $relativePath);
                }
            }
        }

        return $files;
    }

    /**
     * Baca peserta IDs dari ZIP (untuk deteksi konflik).
     */
    private function getPesertaIdsFromZip(string $zipPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) return [];

        $json = $zip->getFromName('peserta_ids.json');
        $zip->close();

        return $json !== false ? (json_decode($json, true) ?? []) : [];
    }

    /**
     * Baca file list dari ZIP (untuk deteksi konflik).
     */
    private function getFileListFromZip(string $zipPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) return [];

        $json = $zip->getFromName('file_list.json');
        $zip->close();

        return $json !== false ? (json_decode($json, true) ?? []) : [];
    }

    /**
     * Hitung perubahan data local (untuk log push).
     */
    private function hitungPerubahan(): array
    {
        $perubahan = [];

        foreach ($this->syncTables as $table) {
            try {
                $count = DB::table($table)->count();
                if ($count > 0) {
                    $perubahan[] = [
                        'tipe' => 'tabel',
                        'tabel' => $table,
                        'aksi' => 'push',
                        'keterangan' => "`{$table}`: {$count} record",
                    ];
                }
            } catch (\Throwable $e) {
                // skip
            }
        }

        $fileCount = count($this->getStorageFileList());
        if ($fileCount > 0) {
            $perubahan[] = [
                'tipe' => 'file',
                'aksi' => 'push',
                'keterangan' => "{$fileCount} file akan di-push",
            ];
        }

        return $perubahan;
    }
}
