<?php
/**
 * POS Koperasi Al-Farmasi
 * Konfigurasi Database
 */

// Security Headers - Set seawal mungkin
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Error reporting - production mode (hide errors from users)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'pos_koperasi');
define('DB_USER', 'koperasi');
define('DB_PASS', 'koperasi123');
define('DB_CHARSET', 'utf8mb4');

// Konfigurasi Aplikasi
define('APP_NAME', 'POS Koperasi Al-Farmasi');
define('APP_VERSION', '1.0.0');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Koneksi Database menggunakan PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log error untuk debugging (cek di error log server)
    error_log('Database connection error: ' . $e->getMessage());
    die("Koneksi database gagal. Silakan hubungi administrator.");
}

/**
 * Function untuk menjalankan query dengan prepared statement
 */
function query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Function untuk mengambil satu baris data
 */
function fetchOne($sql, $params = []) {
    return query($sql, $params)->fetch();
}

/**
 * Function untuk mengambil semua data
 */
function fetchAll($sql, $params = []) {
    return query($sql, $params)->fetchAll();
}

/**
 * Function untuk mendapatkan ID terakhir yang di-insert
 */
function lastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Function untuk escape string
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Function untuk format rupiah
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Function untuk generate nomor transaksi
 */
function generateNoTransaksi() {
    $tanggal = date('Ymd');
    $prefix = 'TRX' . $tanggal;
    
    // Cari nomor terakhir hari ini
    $sql = "SELECT no_transaksi FROM transaksi WHERE no_transaksi LIKE ? ORDER BY id DESC LIMIT 1";
    $result = fetchOne($sql, [$prefix . '%']);
    
    if ($result) {
        $lastNumber = intval(substr($result['no_transaksi'], -4));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Function untuk mendapatkan setting
 */
function getSetting($key, $default = null) {
    $result = fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $result ? $result['setting_value'] : $default;
}

/**
 * Function untuk menyimpan setting
 */
function saveSetting($key, $value) {
    $existing = fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    if ($existing) {
        query("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
    } else {
        query("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
    }
}

/**
 * Function untuk mendapatkan semua payment settings
 */
function getPaymentSettings() {
    return [
        'qris_aktif' => (int)getSetting('qris_aktif', 0),
        'qris_nama' => getSetting('qris_nama', 'QRIS'),
        'qris_image' => getSetting('qris_image', ''),
        'transfer_aktif' => (int)getSetting('transfer_aktif', 0),
        'transfer_bank' => getSetting('bank_nama', ''),
        'transfer_rekening' => getSetting('bank_rekening', ''),
        'transfer_atas_nama' => getSetting('bank_atas_nama', '')
    ];
}
