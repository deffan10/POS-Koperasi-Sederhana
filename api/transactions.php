<?php
/**
 * Transactions API
 * POS Koperasi Al-Farmasi
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session and check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    jsonResponse(false, 'Unauthorized');
}

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get transaction details by transaction number
            if (isset($_GET['nomor'])) {
                $stmt = $db->prepare("
                    SELECT t.*, u.nama_lengkap as nama_kasir
                    FROM transactions t
                    JOIN users u ON t.user_id = u.id
                    WHERE t.nomor_transaksi = ?
                ");
                $stmt->execute([$_GET['nomor']]);
                $transaction = $stmt->fetch();
                
                if (!$transaction) {
                    jsonResponse(false, 'Transaksi tidak ditemukan');
                }
                
                // Get transaction items
                $stmt = $db->prepare("
                    SELECT * FROM transaction_items WHERE transaction_id = ?
                ");
                $stmt->execute([$transaction['id']]);
                $items = $stmt->fetchAll();
                
                $transaction['items'] = $items;
                jsonResponse(true, 'Transaction found', $transaction);
            } else {
                // List all transactions (for reports)
                $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
                
                $stmt = $db->prepare("
                    SELECT t.*, u.nama_lengkap as nama_kasir
                    FROM transactions t
                    JOIN users u ON t.user_id = u.id
                    WHERE DATE(t.tanggal_transaksi) = ?
                    ORDER BY t.tanggal_transaksi DESC
                ");
                $stmt->execute([$date]);
                $transactions = $stmt->fetchAll();
                
                jsonResponse(true, 'Transactions loaded', $transactions);
            }
            break;
            
        case 'POST':
            // Create new transaction
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['items']) || !is_array($data['items'])) {
                jsonResponse(false, 'Item transaksi diperlukan');
            }
            
            $db->beginTransaction();
            
            try {
                // Generate transaction number
                $nomorTransaksi = generateTransactionNumber();
                
                // Make sure transaction number is unique
                $stmt = $db->prepare("SELECT id FROM transactions WHERE nomor_transaksi = ?");
                $stmt->execute([$nomorTransaksi]);
                while ($stmt->fetch()) {
                    $nomorTransaksi = generateTransactionNumber();
                    $stmt->execute([$nomorTransaksi]);
                }
                
                $totalHarga = 0;
                $itemsToInsert = [];
                
                // Validate and calculate items
                foreach ($data['items'] as $item) {
                    if (empty($item['product_id']) || empty($item['qty'])) {
                        throw new Exception('Data item tidak lengkap');
                    }
                    
                    // Get product
                    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 'aktif' FOR UPDATE");
                    $stmt->execute([$item['product_id']]);
                    $product = $stmt->fetch();
                    
                    if (!$product) {
                        throw new Exception('Produk tidak ditemukan');
                    }
                    
                    if ($product['stok'] < $item['qty']) {
                        throw new Exception("Stok {$product['nama_produk']} tidak mencukupi");
                    }
                    
                    $subtotal = $product['harga_jual'] * $item['qty'];
                    $totalHarga += $subtotal;
                    
                    $itemsToInsert[] = [
                        'product_id' => $product['id'],
                        'kode_produk' => $product['kode_produk'],
                        'nama_produk' => $product['nama_produk'],
                        'harga_satuan' => $product['harga_jual'],
                        'jumlah' => $item['qty'],
                        'subtotal' => $subtotal,
                        'stok_sebelum' => $product['stok']
                    ];
                }
                
                // Insert transaction
                $stmt = $db->prepare("
                    INSERT INTO transactions 
                    (nomor_transaksi, user_id, total_harga, metode_pembayaran, jumlah_bayar, kembalian) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $nomorTransaksi,
                    $auth->getUser()['id'],
                    $totalHarga,
                    $data['metode_pembayaran'] ?? 'tunai',
                    $data['jumlah_bayar'] ?? $totalHarga,
                    $data['kembalian'] ?? 0
                ]);
                
                $transactionId = $db->lastInsertId();
                
                // Insert transaction items and update stock
                foreach ($itemsToInsert as $item) {
                    // Insert transaction item
                    $stmt = $db->prepare("
                        INSERT INTO transaction_items 
                        (transaction_id, product_id, kode_produk, nama_produk, harga_satuan, jumlah, subtotal) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $transactionId,
                        $item['product_id'],
                        $item['kode_produk'],
                        $item['nama_produk'],
                        $item['harga_satuan'],
                        $item['jumlah'],
                        $item['subtotal']
                    ]);
                    
                    // Update product stock
                    $newStock = $item['stok_sebelum'] - $item['jumlah'];
                    $stmt = $db->prepare("UPDATE products SET stok = ? WHERE id = ?");
                    $stmt->execute([$newStock, $item['product_id']]);
                    
                    // Record stock history
                    $stmt = $db->prepare("
                        INSERT INTO stock_history 
                        (product_id, user_id, jenis_perubahan, jumlah, stok_sebelum, stok_sesudah, keterangan) 
                        VALUES (?, ?, 'keluar', ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $item['product_id'],
                        $auth->getUser()['id'],
                        $item['jumlah'],
                        $item['stok_sebelum'],
                        $newStock,
                        "Penjualan - {$nomorTransaksi}"
                    ]);
                }
                
                $db->commit();
                
                jsonResponse(true, 'Transaksi berhasil', [
                    'nomor_transaksi' => $nomorTransaksi,
                    'total_harga' => $totalHarga
                ]);
                
            } catch (Exception $e) {
                $db->rollBack();
                jsonResponse(false, $e->getMessage());
            }
            break;
            
        default:
            jsonResponse(false, 'Method not allowed');
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
