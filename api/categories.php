<?php
/**
 * Categories API
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
            // Get all categories
            $stmt = $db->prepare("SELECT * FROM categories ORDER BY nama_kategori");
            $stmt->execute();
            $categories = $stmt->fetchAll();
            
            jsonResponse(true, 'Categories loaded', $categories);
            break;
            
        case 'POST':
            // Create new category (Admin only)
            if (!$auth->isAdmin()) {
                jsonResponse(false, 'Access denied');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['nama_kategori'])) {
                jsonResponse(false, 'Nama kategori wajib diisi');
            }
            
            $stmt = $db->prepare("INSERT INTO categories (nama_kategori, deskripsi) VALUES (?, ?)");
            $stmt->execute([$data['nama_kategori'], $data['deskripsi'] ?? null]);
            
            jsonResponse(true, 'Kategori berhasil ditambahkan', ['id' => $db->lastInsertId()]);
            break;
            
        case 'PUT':
            // Update category (Admin only)
            if (!$auth->isAdmin()) {
                jsonResponse(false, 'Access denied');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || empty($data['nama_kategori'])) {
                jsonResponse(false, 'Data tidak lengkap');
            }
            
            $stmt = $db->prepare("UPDATE categories SET nama_kategori = ?, deskripsi = ? WHERE id = ?");
            $stmt->execute([$data['nama_kategori'], $data['deskripsi'] ?? null, $data['id']]);
            
            jsonResponse(true, 'Kategori berhasil diupdate');
            break;
            
        case 'DELETE':
            // Delete category (Admin only)
            if (!$auth->isAdmin()) {
                jsonResponse(false, 'Access denied');
            }
            
            if (empty($_GET['id'])) {
                jsonResponse(false, 'ID kategori diperlukan');
            }
            
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            
            jsonResponse(true, 'Kategori berhasil dihapus');
            break;
            
        default:
            jsonResponse(false, 'Method not allowed');
    }
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
