<?php
/**
 * Setup Script untuk Hosting Tanpa SSH
 * Akses via: https://seleksi.smaafbs.sch.id/setup-hosting.php
 * HAPUS FILE INI SETELAH SELESAI SETUP!
 */

// Password untuk keamanan (ganti dengan password Anda)
$setupPassword = 'setup123';

// Cek password
if (!isset($_GET['key']) || $_GET['key'] !== $setupPassword) {
    die('Akses ditolak. Gunakan: setup-hosting.php?key=' . $setupPassword);
}

// ============================================
// KONFIGURASI PATH - SESUAIKAN DENGAN HOSTING
// ============================================
// Path ke folder aplikasi Laravel (di luar public_html)
$appPath = '/home/sman5479/spmb-app';
// ============================================

echo "<h1>🔧 Setup Laravel Hosting</h1>";
echo "<pre>";

echo "App Path: $appPath\n";
echo "Current Dir: " . __DIR__ . "\n\n";

// 1. Generate APP_KEY jika belum ada
echo "\n=== 1. Checking APP_KEY ===\n";
$envPath = $appPath . '/.env';
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    if (strpos($envContent, 'APP_KEY=base64:') === false || strpos($envContent, 'GENERATE_NEW_KEY') !== false) {
        // Generate new key
        $key = 'base64:' . base64_encode(random_bytes(32));
        $envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $envContent);
        file_put_contents($envPath, $envContent);
        echo "✅ APP_KEY generated: $key\n";
    } else {
        echo "✅ APP_KEY already exists\n";
    }
} else {
    echo "❌ .env file not found!\n";
}

// 2. Set folder permissions
echo "\n=== 2. Setting Permissions ===\n";
$folders = [
    $appPath . '/storage',
    $appPath . '/storage/app',
    $appPath . '/storage/app/public',
    $appPath . '/storage/framework',
    $appPath . '/storage/framework/cache',
    $appPath . '/storage/framework/sessions',
    $appPath . '/storage/framework/views',
    $appPath . '/storage/logs',
    $appPath . '/bootstrap/cache',
];

foreach ($folders as $folder) {
    if (!is_dir($folder)) {
        if (mkdir($folder, 0775, true)) {
            echo "✅ Created: " . basename($folder) . "\n";
        } else {
            echo "❌ Failed to create: " . basename($folder) . "\n";
        }
    } else {
        @chmod($folder, 0775);
        echo "✅ Exists: " . basename($folder) . "\n";
    }
}

// 3. Create storage link (copy instead of symlink)
echo "\n=== 3. Creating Storage Link ===\n";
$publicStorage = __DIR__ . '/storage';
$appStorage = $appPath . '/storage/app/public';

if (!is_dir($publicStorage)) {
    // Buat folder storage di public
    if (mkdir($publicStorage, 0775, true)) {
        echo "✅ Created public/storage folder\n";
        
        // Copy .gitignore
        file_put_contents($publicStorage . '/.gitignore', "*\n!.gitignore\n");
        echo "✅ Created .gitignore in public/storage\n";
    }
} else {
    echo "✅ public/storage folder exists\n";
}

// 4. Clear cache files
echo "\n=== 4. Clearing Cache ===\n";
$cachePaths = [
    $appPath . '/bootstrap/cache/config.php',
    $appPath . '/bootstrap/cache/routes-v7.php',
    $appPath . '/bootstrap/cache/services.php',
    $appPath . '/bootstrap/cache/packages.php',
];

foreach ($cachePaths as $cachePath) {
    if (file_exists($cachePath)) {
        @unlink($cachePath);
        echo "✅ Deleted: " . basename($cachePath) . "\n";
    }
}

// Clear view cache
$viewCachePath = $appPath . '/storage/framework/views';
if (is_dir($viewCachePath)) {
    $files = glob($viewCachePath . '/*.php');
    foreach ($files as $file) {
        @unlink($file);
    }
    echo "✅ Cleared view cache (" . count($files) . " files)\n";
}

// 5. Test database connection
echo "\n=== 5. Testing Database Connection ===\n";
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    preg_match('/^DB_HOST=(.*)$/m', $envContent, $host);
    preg_match('/^DB_DATABASE=(.*)$/m', $envContent, $database);
    preg_match('/^DB_USERNAME=(.*)$/m', $envContent, $username);
    preg_match('/^DB_PASSWORD=(.*)$/m', $envContent, $password);
    
    $dbHost = trim($host[1] ?? 'localhost');
    $dbName = trim($database[1] ?? '');
    $dbUser = trim($username[1] ?? '');
    $dbPass = trim($password[1] ?? '');
    
    echo "Host: $dbHost\n";
    echo "Database: $dbName\n";
    echo "Username: $dbUser\n";
    
    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ Database connection successful!\n";
        
        // Count tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll();
        echo "✅ Found " . count($tables) . " tables\n";
    } catch (PDOException $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    }
}

// 6. Check PHP version and extensions
echo "\n=== 6. PHP Environment ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext\n";
    } else {
        echo "❌ $ext (missing)\n";
    }
}

echo "\n=== ✅ SETUP COMPLETE ===\n";
echo "\n⚠️ PENTING: Hapus file setup-hosting.php setelah selesai!\n";
echo "</pre>";

echo "<hr>";
echo "<h2>Langkah Selanjutnya:</h2>";
echo "<ol>";
echo "<li>Test akses: <a href='/'>Halaman Utama</a></li>";
echo "<li>Test login admin</li>";
echo "<li><strong style='color:red'>HAPUS file setup-hosting.php ini!</strong></li>";
echo "</ol>";
?>
