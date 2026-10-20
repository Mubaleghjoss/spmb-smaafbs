<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Controller untuk fitur deployment: download project, backup DB, import DB, update project, clear cache.
 * Diekstrak dari PengaturanController untuk memisahkan tanggung jawab.
 */
class DeploymentController extends Controller
{
    /**
     * Download project untuk deployment (ZIP)
     * Termasuk folder vendor agar tidak perlu composer install di hosting
     */
    public function downloadProject()
    {
        set_time_limit(900);
        ini_set('memory_limit', '1024M');

        $projectPath = base_path();
        $zipFileName = 'spmb-full-' . date('Y-m-d-His') . '.zip';

        $tempDir = sys_get_temp_dir();
        $zipFilePath = $tempDir . DIRECTORY_SEPARATOR . $zipFileName;

        if (file_exists($zipFilePath)) {
            @unlink($zipFilePath);
        }

        $excludes = [
            '.git',
            'node_modules',
            'storage' . DIRECTORY_SEPARATOR . 'logs',
            'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'data',
            'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'sessions',
            'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views',
            'storage' . DIRECTORY_SEPARATOR . 'exports',
            '.env',
            '.phpunit.result.cache',
            'phpunit.xml',
            'tests',
        ];

        $zip = new \ZipArchive();
        $result = $zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            return back()->with('error', 'Gagal membuat file ZIP. Error code: ' . $result);
        }

        $filesToAdd = [];
        $sqlFileName = 'spmb-database-' . date('Y-m-d-His') . '.sql';
        $sqlFilePath = $this->generateSqlBackupFilePath($sqlFileName);

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($projectPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($projectPath) + 1);

                $shouldExclude = false;
                foreach ($excludes as $exclude) {
                    $normalizedRelative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
                    $normalizedExclude = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $exclude);

                    if (strpos($normalizedRelative, $normalizedExclude) === 0) {
                        $shouldExclude = true;
                        break;
                    }
                }

                if ($shouldExclude || strpos($filePath, $zipFileName) !== false) {
                    continue;
                }

                $filesToAdd[] = [
                    'path'     => $filePath,
                    'relative' => $relativePath,
                    'isDir'    => $file->isDir(),
                ];
            }
        } catch (\Exception $e) {
            $zip->close();
            @unlink($zipFilePath);
            return back()->with('error', 'Gagal membaca file project: ' . $e->getMessage());
        }

        foreach ($filesToAdd as $fileInfo) {
            $zipRelativePath = str_replace('\\', '/', $fileInfo['relative']);

            if ($fileInfo['isDir']) {
                $zip->addEmptyDir($zipRelativePath);
            } else {
                $content = @file_get_contents($fileInfo['path']);
                if ($content !== false) {
                    $zip->addFromString($zipRelativePath, $content);
                }
            }
        }

        $publicRootPath = $this->resolvePublicRootPath();
        if ($publicRootPath !== null) {
            $publicIterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($publicRootPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($publicIterator as $file) {
                $relativePath = substr($file->getRealPath(), strlen($publicRootPath) + 1);
                $zipRelativePath = 'public-root/' . str_replace('\\', '/', $relativePath);

                if ($file->isDir()) {
                    $zip->addEmptyDir($zipRelativePath);
                    continue;
                }

                $content = @file_get_contents($file->getRealPath());
                if ($content !== false) {
                    $zip->addFromString($zipRelativePath, $content);
                }
            }
        }

        $zip->addFile($sqlFilePath, 'database/' . $sqlFileName);

        // Sertakan .env.production (versi sanitized dari .env)
        $envPath = $projectPath . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            $envContent = preg_replace('/^(DB_PASSWORD=).*$/m', '$1your_password', $envContent);
            $envContent = preg_replace('/^(DB_DATABASE=).*$/m', '$1your_database', $envContent);
            $envContent = preg_replace('/^(DB_USERNAME=).*$/m', '$1your_username', $envContent);
            $envContent = preg_replace('/^(MAIL_PASSWORD=).*$/m', '$1', $envContent);
            $envContent = preg_replace('/^(APP_URL=).*$/m', '$1https://your-domain.com', $envContent);
            $envContent = preg_replace('/^(APP_DEBUG=).*$/m', '$1false', $envContent);
            $envContent = preg_replace('/^(APP_ENV=).*$/m', '$1production', $envContent);
            $zip->addFromString('.env.production', $envContent);
        }

        // README deployment dan instalasi lokal
        $readmeContent  = "# Paket Backup & Instalasi Lokal SPMB\n\n";
        $readmeContent .= "Paket ini dibuat otomatis dari aplikasi production saat tombol Download Project ditekan.\n";
        $readmeContent .= "Berisi source project, vendor, database SQL terbaru, dan public root aktual.\n\n";
        $readmeContent .= "## Struktur Paket\n\n";
        $readmeContent .= "- `database/` - dump SQL database saat paket dibuat\n";
        $readmeContent .= "- `public-root/` - isi public root web aktual, terpisah dari folder source `public/`\n";
        $readmeContent .= "- `vendor/` - dependency Composer yang sudah terpasang\n";
        $readmeContent .= "- `.env.production` - template konfigurasi yang sudah disanitasi\n\n";
        $readmeContent .= "## Instalasi Lokal\n\n";
        $readmeContent .= "1. Extract ZIP ke folder kerja lokal.\n";
        $readmeContent .= "2. Salin `.env.production` menjadi `.env`; jangan gunakan kredensial production.\n";
        $readmeContent .= "3. Buat database MySQL lokal, lalu import file SQL di folder `database/`.\n";
        $readmeContent .= "4. Sesuaikan DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, dan DB_PASSWORD di `.env`.\n";
        $readmeContent .= "5. Jalankan `php artisan key:generate`.\n";
        $readmeContent .= "6. Jalankan `php artisan storage:link`.\n";
        $readmeContent .= "7. Jalankan `php artisan optimize:clear`.\n";
        $readmeContent .= "8. Jika dependency/vendor tidak dipakai, jalankan `composer install`.\n";
        $readmeContent .= "9. Jalankan `npm install` lalu `npm run build` jika asset frontend perlu dibangun ulang.\n";
        $readmeContent .= "10. Jalankan `php artisan serve` dan buka URL lokal yang ditampilkan.\n\n";
        $readmeContent .= "## Menempatkan Public Root\n\n";
        $readmeContent .= "Untuk web server lokal, arahkan document root ke folder `public-root/` jika ingin meniru struktur public root production.\n";
        $readmeContent .= "Jika memakai Laravel standar, gunakan folder source `public/` dan salin asset yang diperlukan dari `public-root/`.\n\n";
        $readmeContent .= "## Catatan Keamanan\n\n";
        $readmeContent .= "- File SQL dapat berisi data peserta production. Simpan dan distribusikan secara terbatas.\n";
        $readmeContent .= "- Jangan mengunggah SQL atau `.env` hasil konfigurasi lokal ke repository publik.\n";
        $readmeContent .= "- Generated: " . now()->format('d/m/Y H:i:s') . "\n";
        $readmeContent .= "- PHP minimum: 8.1 | MySQL minimum: 5.7\n";
        $zip->addFromString('README-DEPLOYMENT.md', $readmeContent);

        $zip->close();
        @unlink($sqlFilePath);

        return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Download database untuk deployment (SQL)
     */
    public function downloadDatabase()
    {
        set_time_limit(300);

        $sqlFileName = 'spmb-database-' . date('Y-m-d-His') . '.sql';

        // Coba mysqldump dulu (hanya di environment lokal / server yang mendukung)
        try {
            // Cek apakah fungsi shell tersedia
            if (!function_exists('escapeshellarg') || !function_exists('exec')) {
                return response()->download($this->generateSqlBackupFilePath($sqlFileName), $sqlFileName)->deleteFileAfterSend(true);
            }

            $dbName = config('database.connections.mysql.database');
            $dbHost = config('database.connections.mysql.host');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            $tempDir     = sys_get_temp_dir();
            $sqlFilePath = $tempDir . DIRECTORY_SEPARATOR . $sqlFileName;

            $mysqldumpPath = 'mysqldump';

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $possiblePaths = [
                    'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                    'E:\\xampp\\mysql\\bin\\mysqldump.exe',
                    'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
                    'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
                ];
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $mysqldumpPath = '"' . $path . '"';
                        break;
                    }
                }
            }

            $command = sprintf(
                '%s --host=%s --user=%s --password=%s %s > %s 2>&1',
                $mysqldumpPath,
                \escapeshellarg($dbHost),
                \escapeshellarg($dbUser),
                \escapeshellarg($dbPass),
                \escapeshellarg($dbName),
                \escapeshellarg($sqlFilePath)
            );

            \exec($command, $output, $returnVar);

            if ($returnVar !== 0 || !file_exists($sqlFilePath) || filesize($sqlFilePath) === 0) {
                return response()->download($this->generateSqlBackupFilePath($sqlFileName), $sqlFileName)->deleteFileAfterSend(true);
            }

            return response()->download($sqlFilePath, $sqlFileName)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            // Fallback ke PHP-based backup jika mysqldump atau shell functions gagal
            return response()->download($this->generateSqlBackupFilePath($sqlFileName), $sqlFileName)->deleteFileAfterSend(true);
        }
    }

    /**
     * Import database dari file SQL
     */
    public function importDatabase(Request $request)
    {
        $request->validate(['sql_file' => 'required|file|max:102400']);

        $file = $request->file('sql_file');

        if ($file->getClientOriginalExtension() !== 'sql') {
            return back()->with('error', 'File harus berekstensi .sql');
        }

        try {
            set_time_limit(600);
            ini_set('memory_limit', '512M');

            $sql = file_get_contents($file->getRealPath());

            \DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $queries      = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
            $successCount = 0;
            $errorCount   = 0;
            $errors       = [];

            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query) || strpos($query, '--') === 0) continue;

                try {
                    \DB::unprepared($query);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    if (count($errors) < 5) {
                        $errors[] = substr($e->getMessage(), 0, 100);
                    }
                }
            }

            \DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $message = "Import database selesai. Query sukses: {$successCount}, Query gagal: {$errorCount}";

            if (!empty($errors)) {
                $message .= ". Beberapa error: " . implode('; ', $errors);
                return back()->with('warning', $message);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal import database: ' . $e->getMessage());
        }
    }

    /**
     * Update project dari file ZIP
     */
    public function updateProject(Request $request)
    {
        $request->validate(['zip_file' => 'required|file|max:512000']);

        $file = $request->file('zip_file');

        if ($file->getClientOriginalExtension() !== 'zip') {
            return back()->with('error', 'File harus berekstensi .zip');
        }

        try {
            set_time_limit(900);
            ini_set('memory_limit', '1024M');

            $projectRoot = base_path();
            $tmpFile     = $file->getRealPath();

            $zip = new \ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                return back()->with('error', 'Tidak bisa membuka file ZIP');
            }

            // Backup .env sebelum extract
            $envBackup = '';
            $envPath   = $projectRoot . '/.env';
            if (file_exists($envPath)) {
                $envBackup = file_get_contents($envPath);
            }

            $extractCount = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                // Jangan overwrite .env
                if ($filename === '.env' || $filename === '.env.production') {
                    continue;
                }

                $zip->extractTo($projectRoot, $filename);
                $extractCount++;
            }
            $zip->close();

            if (!empty($envBackup)) {
                file_put_contents($envPath, $envBackup);
            }

            $this->clearAllCache();

            return back()->with('success', "Project berhasil diupdate. {$extractCount} files extracted. Cache cleared.");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal update project: ' . $e->getMessage());
        }
    }

    /**
     * Clear all Laravel caches
     */
    public function clearCache()
    {
        try {
            $this->clearAllCache();
            return back()->with('success', 'Cache berhasil dihapus (config, routes, views, file cache).');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal clear cache: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Generate SQL backup menggunakan PHP (fallback jika mysqldump tidak tersedia)
     */
    private function generateSqlBackupFilePath(string $sqlFileName): string
    {
        $tempDir     = sys_get_temp_dir();
        $sqlFilePath = $tempDir . DIRECTORY_SEPARATOR . $sqlFileName;

        $tables  = \DB::select('SHOW TABLES');
        $dbName  = config('database.connections.mysql.database');

        $sql  = "-- SPMB Database Backup\n";
        $sql .= "-- Generated: " . now()->format('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: {$dbName}\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql .= "SET AUTOCOMMIT = 0;\nSTART TRANSACTION;\n\n";

        foreach ($tables as $table) {
            $tableName  = array_values((array) $table)[0];
            $createTable = \DB::select("SHOW CREATE TABLE `{$tableName}`");
            $createSql   = $createTable[0]->{'Create Table'} ?? '';

            $sql .= "-- Table structure for `{$tableName}`\n";
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $createSql . ";\n\n";

            $rows = \DB::table($tableName)->get();

            if ($rows->count() > 0) {
                $sql .= "-- Data for `{$tableName}`\n";

                foreach ($rows as $row) {
                    $values = [];
                    foreach ((array) $row as $value) {
                        $values[] = is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
                    }
                    $sql .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\nCOMMIT;\n";

        file_put_contents($sqlFilePath, $sql);

        return $sqlFilePath;
    }

    private function resolvePublicRootPath(): ?string
    {
        $configuredPath = env('SPMB_PUBLIC_ROOT');
        $candidates = array_filter([
            $configuredPath,
            dirname(base_path()) . '/public_html/web/www.seleksi',
            base_path('public'),
        ]);

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);
            if ($realPath !== false && is_dir($realPath)) {
                return $realPath;
            }
        }

        return null;
    }

    /**
     * Clear semua cache Laravel
     */
    private function clearAllCache(): void
    {
        $projectRoot = base_path();

        $cachePaths = [
            $projectRoot . '/bootstrap/cache/config.php',
            $projectRoot . '/bootstrap/cache/routes-v7.php',
            $projectRoot . '/bootstrap/cache/services.php',
            $projectRoot . '/bootstrap/cache/packages.php',
        ];

        foreach ($cachePaths as $cachePath) {
            if (file_exists($cachePath)) {
                @unlink($cachePath);
            }
        }

        $viewCachePath = $projectRoot . '/storage/framework/views';
        if (is_dir($viewCachePath)) {
            foreach (glob($viewCachePath . '/*.php') as $viewFile) {
                @unlink($viewFile);
            }
        }

        $fileCachePath = $projectRoot . '/storage/framework/cache/data';
        if (is_dir($fileCachePath)) {
            $cacheFiles = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fileCachePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($cacheFiles as $file) {
                if ($file->isFile()) {
                    @unlink($file->getPathname());
                }
            }
        }

        try {
            \Artisan::call('cache:clear');
        } catch (\Exception $e) {
            // Ignore if artisan fails
        }
    }
}
