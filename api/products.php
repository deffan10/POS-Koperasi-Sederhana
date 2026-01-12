<?php
/**
 * Products API
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
            // Get single product or all products
            if (isset($_GET['id'])) {
                $stmt = $db->prepare("
                    SELECT p.*, c.nama_kategori 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $product = $stmt->fetch();
                
                if ($product) {
                    jsonResponse(true, 'Product found', $product);
                } else {
                    jsonResponse(false, 'Product not found');
                }
            } elseif (isset($_GET['search'])) {
                // Search products by code or name
                $search = '%' . $_GET['search'] . '%';
                $stmt = $db->prepare("
                    SELECT p.*, c.nama_kategori 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.status = 'aktif' 
                    AND (p.kode_produk LIKE ? OR p.nama_produk LIKE ?)
                    ORDER BY p.nama_produk
                ");
                $stmt->execute([$search, $search]);
                $products = $stmt->fetchAll();
                jsonResponse(true, 'Search results', $products);
            } else {
                // Get all products
                $stmt = $db->prepare("
                    SELECT p.*, c.nama_kategori 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.status = 'aktif'
                    ORDER BY p.nama_produk
                ");
                $stmt->execute();
                $products = $stmt->fetchAll();
                jsonResponse(true, 'Products loaded', $products);
            }
            break;
            
        case 'POST':
            // Create new product (Admin only)
            if (!$auth->isAdmin()) {
                jsonResponse(false, 'Access denied');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($data['kode_produk']) || empty($data['nama_produk']) || !isset($data['harga_jual'])) {
                jsonResponse(false, 'Kode produk, nama produk, dan harga wajib diisi');
            }
            
            // Check if product code already exists
            $stmt = $db->prepare("SELECT id FROM products WHERE kode_produk = ?");
            $stmt->execute([$data['kode_produk']]);
            if ($stmt->fetch()) {
                jsonResponse(false, 'Kode produk sudah digunakan');
            }
            
            $stmt = $db->prepare("
                INSERT INTO products (kode_produk, nama_produk, category_id, harga_jual, stok, satuan) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['kode_produk'],
                $data['nama_produk'],
                $data['category_id'] ?: null,
                $data['harga_jual'],
                $data['stok'] ?? 0,
                $data['satuan'] ?? 'pcs'
            ]);
            
            jsonResponse(true, 'Produk berhasil ditambahkan', ['id' => $db->lastInsertId()]);
            break;
            
        case 'PUT':
            // Update product (Admin only)
            if (!$auth->isAdmin()) {
                jsonResponse(false, 'Access denied');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                jsonResponse(false, 'ID produk diperlukan');
            }
            
            // Check if new product code already exists (exclude current product)
            if (!empty($data['kode_produk'])) {
                $stmt = $db->prepare("SELECT id FROM products WHERE kode_produk = ? AND id != ?");
                $stmt->execute([$data['kode_produk'], $data['id']]);
                if ($stmt->fetch()) {
                    jsonResponse(false, 'Kode produk sudah digunakan');
                }
            }
            
            $stmt = $db->prepare("
                UPDATE products SET 
                    kode_produk = ?,
                    nama_produk = ?,
                    category_id = ?,
                    harga_jual = ?,
                    stok = ?,
                    satuan = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $data['kode_produk'],
                $data['nama_produk'],
                $data['category_id'] ?: null,
                $data['harga_jual'],
                $data['stok'] ?? 0,
                $data['satuan'] ?? 'pcs',
                $data['id']
            ]);
            
            jsonResponse(true, 'Produk berhasil diupdate');
            break;
            
        case 'DELETE':
            // Delete product (Admin only)
            if (!$auth->isAdmin()) {
                jsonResponse(false, 'Access denied');
            }
            
            if (empty($_GET['id'])) {
                jsonResponse(false, 'ID produk diperlukan');
            }
            
            // Soft delete - just mark as inactive
            $stmt = $db->prepare("UPDATE products SET status = 'nonaktif' WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            
            jsonResponse(true, 'Produk berhasil dihapus');
            break;
            
        default:
            jsonResponse(false, 'Method not allowed');
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
