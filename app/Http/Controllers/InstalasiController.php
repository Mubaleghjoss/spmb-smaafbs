<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Models\Pengguna;
use App\Models\Pengaturan;

class InstalasiController extends Controller
{
    /**
     * Tampilkan halaman wizard instalasi
     */
    public function index()
    {
        // Cek apakah sudah terinstal
        if ($this->sudahTerinstal()) {
            return redirect('/')->with('info', 'Aplikasi sudah terinstal.');
        }

        return view('instalasi.index', [
            'step' => 1,
            'requirements' => $this->cekRequirements(),
        ]);
    }

    /**
     * Step 1: Cek requirements sistem
     */
    public function cekRequirements()
    {
        return [
            'php_version' => [
                'label' => 'PHP >= 8.2',
                'status' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
            ],
            'pdo' => [
                'label' => 'PDO Extension',
                'status' => extension_loaded('pdo'),
            ],
            'pdo_mysql' => [
                'label' => 'PDO MySQL Extension',
                'status' => extension_loaded('pdo_mysql'),
            ],
            'mbstring' => [
                'label' => 'Mbstring Extension',
                'status' => extension_loaded('mbstring'),
            ],
            'openssl' => [
                'label' => 'OpenSSL Extension',
                'status' => extension_loaded('openssl'),
            ],
            'tokenizer' => [
                'label' => 'Tokenizer Extension',
                'status' => extension_loaded('tokenizer'),
            ],
            'xml' => [
                'label' => 'XML Extension',
                'status' => extension_loaded('xml'),
            ],
            'ctype' => [
                'label' => 'Ctype Extension',
                'status' => extension_loaded('ctype'),
            ],
            'json' => [
                'label' => 'JSON Extension',
                'status' => extension_loaded('json'),
            ],
            'fileinfo' => [
                'label' => 'Fileinfo Extension',
                'status' => extension_loaded('fileinfo'),
            ],
            'gd' => [
                'label' => 'GD Extension',
                'status' => extension_loaded('gd'),
            ],
            'zip' => [
                'label' => 'Zip Extension',
                'status' => extension_loaded('zip'),
            ],
            'storage_writable' => [
                'label' => 'Storage Directory Writable',
                'status' => is_writable(storage_path()),
            ],
            'bootstrap_cache_writable' => [
                'label' => 'Bootstrap Cache Writable',
                'status' => is_writable(base_path('bootstrap/cache')),
            ],
        ];
    }

    /**
     * Step 2: Form konfigurasi database
     */
    public function database()
    {
        if ($this->sudahTerinstal()) {
            return redirect('/');
        }

        return view('instalasi.database', ['step' => 2]);
    }

    /**
     * Step 2: Simpan konfigurasi database
     */
    public function simpanDatabase(Request $request)
    {
        $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        // Test koneksi database
        try {
            $pdo = new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_database}",
                $request->db_username,
                $request->db_password
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            return back()->withErrors(['database' => 'Koneksi database gagal: ' . $e->getMessage()]);
        }

        // Update .env file
        $this->updateEnv([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $request->db_database,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password ?? '',
        ]);

        return redirect()->route('instalasi.admin');
    }


    /**
     * Step 3: Form admin dan pengaturan
     */
    public function admin()
    {
        if ($this->sudahTerinstal()) {
            return redirect('/');
        }

        return view('instalasi.admin', ['step' => 3]);
    }

    /**
     * Step 3: Simpan admin dan pengaturan
     */
    public function simpanAdmin(Request $request)
    {
        $request->validate([
            'nama_institusi' => 'required|string|max:255',
            'admin_nama' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        // Update .env
        $this->updateEnv([
            'APP_NAME' => '"' . $request->nama_institusi . '"',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
        ]);

        // Simpan ke session untuk digunakan saat migrasi
        session([
            'instalasi_admin' => [
                'nama_institusi' => $request->nama_institusi,
                'admin_nama' => $request->admin_nama,
                'admin_email' => $request->admin_email,
                'admin_password' => $request->admin_password,
            ],
        ]);

        return redirect()->route('instalasi.proses');
    }

    /**
     * Step 4: Proses instalasi
     */
    public function proses()
    {
        if ($this->sudahTerinstal()) {
            return redirect('/');
        }

        return view('instalasi.proses', ['step' => 4]);
    }

    /**
     * Step 4: Jalankan instalasi
     */
    public function jalankan(Request $request)
    {
        $hasil = [];

        try {
            // Generate APP_KEY jika belum ada
            if (empty(config('app.key'))) {
                Artisan::call('key:generate', ['--force' => true]);
                $hasil[] = ['status' => 'success', 'pesan' => 'APP_KEY berhasil digenerate'];
            }

            // Clear config cache
            Artisan::call('config:clear');
            $hasil[] = ['status' => 'success', 'pesan' => 'Config cache dibersihkan'];

            // Jalankan migrasi
            Artisan::call('migrate', ['--force' => true]);
            $hasil[] = ['status' => 'success', 'pesan' => 'Migrasi database berhasil'];

            // Buat admin dari session
            $adminData = session('instalasi_admin');
            if ($adminData) {
                Pengguna::create([
                    'nama' => $adminData['admin_nama'],
                    'email' => $adminData['admin_email'],
                    'password' => Hash::make($adminData['admin_password']),
                    'peran' => 'admin',
                    'aktif' => true,
                ]);
                $hasil[] = ['status' => 'success', 'pesan' => 'Admin berhasil dibuat'];

                // Simpan pengaturan institusi
                Pengaturan::set('nama_institusi', $adminData['nama_institusi']);
                $hasil[] = ['status' => 'success', 'pesan' => 'Pengaturan institusi disimpan'];
            }

            // Buat storage link
            Artisan::call('storage:link');
            $hasil[] = ['status' => 'success', 'pesan' => 'Storage link berhasil dibuat'];

            // Optimize
            Artisan::call('optimize');
            $hasil[] = ['status' => 'success', 'pesan' => 'Aplikasi dioptimasi'];

            // Tandai sudah terinstal
            File::put(storage_path('installed'), date('Y-m-d H:i:s'));
            $hasil[] = ['status' => 'success', 'pesan' => 'Instalasi selesai'];

            session()->forget('instalasi_admin');

            return response()->json(['success' => true, 'hasil' => $hasil]);
        } catch (\Exception $e) {
            $hasil[] = ['status' => 'error', 'pesan' => $e->getMessage()];
            return response()->json(['success' => false, 'hasil' => $hasil], 500);
        }
    }

    /**
     * Step 5: Selesai
     */
    public function selesai()
    {
        return view('instalasi.selesai', ['step' => 5]);
    }

    /**
     * Cek apakah aplikasi sudah terinstal
     */
    private function sudahTerinstal(): bool
    {
        return File::exists(storage_path('installed'));
    }

    /**
     * Update file .env
     */
    private function updateEnv(array $data): void
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        File::put($envPath, $envContent);
    }
}
