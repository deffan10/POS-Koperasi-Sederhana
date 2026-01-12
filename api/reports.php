<?php
/**
 * Reports API
 * POS Koperasi Al-Farmasi
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Helper function for report response with summary
function reportJsonResponse($success, $message, $data = null, $summary = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    if ($summary !== null) {
        $response['summary'] = $summary;
    }
    echo json_encode($response);
    exit;
}

// Start session and check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    reportJsonResponse(false, 'Unauthorized');
}

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'GET') {
        reportJsonResponse(false, 'Method not allowed');
    }
    
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    // Get transactions for the date
    $stmt = $db->prepare("
        SELECT t.*, u.nama_lengkap as nama_kasir
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE DATE(t.tanggal_transaksi) = ?
        ORDER BY t.tanggal_transaksi DESC
    ");
    $stmt->execute([$date]);
    $transactions = $stmt->fetchAll();
    
    // Get summary statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_transaksi,
            COALESCE(SUM(total_harga), 0) as total_omzet
        FROM transactions
        WHERE DATE(tanggal_transaksi) = ?
    ");
    $stmt->execute([$date]);
    $summary = $stmt->fetch();
    
    // Get total items sold
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(ti.jumlah), 0) as total_item
        FROM transaction_items ti
        JOIN transactions t ON ti.transaction_id = t.id
        WHERE DATE(t.tanggal_transaksi) = ?
    ");
    $stmt->execute([$date]);
    $itemSummary = $stmt->fetch();
    
    $summary['total_item'] = $itemSummary['total_item'];
    
    reportJsonResponse(true, 'Report loaded', $transactions, $summary);
    
} catch (PDOException $e) {
    reportJsonResponse(false, 'Database error: ' . $e->getMessage());
}
