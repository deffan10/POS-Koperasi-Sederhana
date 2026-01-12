<?php
/**
 * POS Koperasi Al-Farmasi
 * API: Transaksi
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validasi CSRF untuk POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'save':
        saveTransaction();
        break;
    case 'detail':
        getDetail();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function saveTransaction() {
    global $pdo;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || empty($data['items'])) {
            throw new Exception('Data transaksi tidak valid');
        }
        
        $items = $data['items'];
        $metode = $data['metode_pembayaran'] ?? 'tunai';
        
        // Validasi metode pembayaran
        if (!in_array($metode, ['tunai', 'qris', 'transfer'])) {
            $metode = 'tunai';
        }
        
        $uangDiterima = floatval($data['uang_diterima'] ?? 0);
        $totalHarga = floatval($data['total_harga'] ?? 0);
        $totalItem = intval($data['total_item'] ?? 0);
        $kembalian = $metode === 'tunai' ? ($uangDiterima - $totalHarga) : 0;
        
        // Validate stock
        foreach ($items as $item) {
            $produk = fetchOne("SELECT stok FROM produk WHERE id = ?", [$item['produk_id']]);
            if (!$produk || $produk['stok'] < $item['jumlah']) {
                throw new Exception('Stok ' . $item['nama_produk'] . ' tidak mencukupi');
            }
        }
        
        $pdo->beginTransaction();
        
        // Generate transaction number
        $noTransaksi = generateNoTransaksi();
        
        // Insert transaction header
        query("INSERT INTO transaksi (no_transaksi, tanggal_transaksi, user_id, total_item, total_harga, metode_pembayaran, uang_diterima, kembalian) 
               VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)",
            [$noTransaksi, $_SESSION['user_id'], $totalItem, $totalHarga, $metode, $uangDiterima, $kembalian]);
        
        $transaksiId = lastInsertId();
        
        // Insert items and update stock
        foreach ($items as $item) {
            // Insert detail
            query("INSERT INTO detail_transaksi (transaksi_id, produk_id, kode_produk, nama_produk, harga_satuan, jumlah, subtotal) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$transaksiId, $item['produk_id'], $item['kode_produk'], $item['nama_produk'], 
                 $item['harga'], $item['jumlah'], $item['subtotal']]);
            
            // Get current stock
            $produk = fetchOne("SELECT stok FROM produk WHERE id = ?", [$item['produk_id']]);
            $stokSebelum = $produk['stok'];
            $stokSesudah = $stokSebelum - $item['jumlah'];
            
            // Update stock
            query("UPDATE produk SET stok = stok - ? WHERE id = ?", [$item['jumlah'], $item['produk_id']]);
            
            // Record stock history
            query("INSERT INTO riwayat_stok (produk_id, jenis_perubahan, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id) 
                   VALUES (?, 'keluar', ?, ?, ?, ?, ?)",
                [$item['produk_id'], $item['jumlah'], $stokSebelum, $stokSesudah, 'Penjualan #' . $noTransaksi, $_SESSION['user_id']]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaksi berhasil disimpan',
            'no_transaksi' => $noTransaksi,
            'kembalian' => $kembalian
        ]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getDetail() {
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
        return;
    }
    
    $transaksi = fetchOne("SELECT t.*, u.nama_lengkap as kasir 
                          FROM transaksi t 
                          JOIN users u ON t.user_id = u.id 
                          WHERE t.id = ?", [$id]);
    
    if (!$transaksi) {
        echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
        return;
    }
    
    $items = fetchAll("SELECT * FROM detail_transaksi WHERE transaksi_id = ?", [$id]);
    
    echo json_encode([
        'success' => true,
        'transaksi' => $transaksi,
        'items' => $items
    ]);
}
