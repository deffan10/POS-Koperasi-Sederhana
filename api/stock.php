<?php
/**
 * Stock Management API
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
            // Get stock history
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
            
            $stmt = $db->prepare("
                SELECT sh.*, p.kode_produk, p.nama_produk, u.nama_lengkap as nama_user
                FROM stock_history sh
                JOIN products p ON sh.product_id = p.id
                JOIN users u ON sh.user_id = u.id
                ORDER BY sh.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $history = $stmt->fetchAll();
            
            jsonResponse(true, 'Stock history loaded', $history);
            break;
            
        case 'POST':
            // Add stock (Admin only)
            if (!$auth->isAdmin()) {
                jsonResponse(false, 'Access denied');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['product_id']) || empty($data['jumlah']) || empty($data['jenis_perubahan'])) {
                jsonResponse(false, 'Data tidak lengkap');
            }
            
            $db->beginTransaction();
            
            try {
                // Get current product stock
                $stmt = $db->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
                $stmt->execute([$data['product_id']]);
                $product = $stmt->fetch();
                
                if (!$product) {
                    throw new Exception('Produk tidak ditemukan');
                }
                
                $stokSebelum = $product['stok'];
                $jumlah = intval($data['jumlah']);
                
                // Calculate new stock based on change type
                switch ($data['jenis_perubahan']) {
                    case 'masuk':
                        $stokSesudah = $stokSebelum + $jumlah;
                        break;
                    case 'keluar':
                        $stokSesudah = $stokSebelum - $jumlah;
                        if ($stokSesudah < 0) {
                            throw new Exception('Stok tidak mencukupi');
                        }
                        break;
                    case 'penyesuaian':
                        $stokSesudah = $jumlah; // Set stock to specified amount
                        $jumlah = abs($stokSesudah - $stokSebelum);
                        break;
                    default:
                        throw new Exception('Jenis perubahan tidak valid');
                }
                
                // Update product stock
                $stmt = $db->prepare("UPDATE products SET stok = ? WHERE id = ?");
                $stmt->execute([$stokSesudah, $data['product_id']]);
                
                // Record stock history
                $stmt = $db->prepare("
                    INSERT INTO stock_history 
                    (product_id, user_id, jenis_perubahan, jumlah, stok_sebelum, stok_sesudah, keterangan) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['product_id'],
                    $auth->getUser()['id'],
                    $data['jenis_perubahan'],
                    $jumlah,
                    $stokSebelum,
                    $stokSesudah,
                    $data['keterangan'] ?? null
                ]);
                
                $db->commit();
                
                jsonResponse(true, 'Stok berhasil diupdate', [
                    'stok_baru' => $stokSesudah
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
