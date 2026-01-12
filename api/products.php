<?php
/**
 * POS Koperasi Al-Farmasi
 * API: Produk
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'search':
        searchProducts();
        break;
    case 'get':
        getProduct();
        break;
    case 'list':
        listProducts();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function searchProducts() {
    $query = $_GET['q'] ?? '';
    
    if (strlen($query) < 1) {
        echo json_encode(['success' => true, 'products' => []]);
        return;
    }
    
    $products = fetchAll("SELECT id, kode_produk, nama_produk, harga_jual, stok 
                         FROM produk 
                         WHERE status = 'aktif' 
                         AND (kode_produk LIKE ? OR nama_produk LIKE ?)
                         ORDER BY nama_produk
                         LIMIT 10",
                        ["%$query%", "%$query%"]);
    
    echo json_encode(['success' => true, 'products' => $products]);
}

function getProduct() {
    $id = intval($_GET['id'] ?? 0);
    $kode = $_GET['kode'] ?? '';
    
    if ($id) {
        $product = fetchOne("SELECT * FROM produk WHERE id = ? AND status = 'aktif'", [$id]);
    } elseif ($kode) {
        $product = fetchOne("SELECT * FROM produk WHERE kode_produk = ? AND status = 'aktif'", [$kode]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ID atau kode produk diperlukan']);
        return;
    }
    
    if ($product) {
        echo json_encode(['success' => true, 'product' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
    }
}

function listProducts() {
    $products = fetchAll("SELECT id, kode_produk, nama_produk, harga_jual, stok 
                         FROM produk 
                         WHERE status = 'aktif' 
                         ORDER BY nama_produk");
    
    echo json_encode(['success' => true, 'products' => $products]);
}
