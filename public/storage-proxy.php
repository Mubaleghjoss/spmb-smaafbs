<?php
/**
 * Storage Proxy untuk Hosting Tanpa Symlink
 * File ini akan serve file dari storage/app/public
 */

// ============================================
// KONFIGURASI PATH - SESUAIKAN DENGAN HOSTING
// ============================================
$appPath = '/home/sman5479/spmb-app';
// ============================================

// Get requested file path
$requestUri = $_SERVER['REQUEST_URI'];
$filePath = preg_replace('/^\/storage\//', '', parse_url($requestUri, PHP_URL_PATH));

// Security: prevent directory traversal
$filePath = str_replace(['..', "\0"], '', $filePath);

// Full path to file - gunakan path absolut ke app storage
$fullPath = $appPath . '/storage/app/public/' . $filePath;

// Debug mode - akses dengan ?debug=1 untuk lihat path
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain');
    echo "Request URI: " . $requestUri . "\n";
    echo "File Path: " . $filePath . "\n";
    echo "Full Path: " . $fullPath . "\n";
    echo "File Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
    echo "Is Dir: " . (is_dir($fullPath) ? 'YES' : 'NO') . "\n";
    
    // List directory jika ada
    $dir = dirname($fullPath);
    if (is_dir($dir)) {
        echo "\nFiles in directory:\n";
        foreach (scandir($dir) as $file) {
            echo "  - $file\n";
        }
    }
    exit;
}

// Check if file exists
if (!file_exists($fullPath) || is_dir($fullPath)) {
    http_response_code(404);
    die('File not found: ' . $filePath);
}

// Get mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fullPath);
finfo_close($finfo);

// Set headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=31536000');

// Output file
readfile($fullPath);
exit;
