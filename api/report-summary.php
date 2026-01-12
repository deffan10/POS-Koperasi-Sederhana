<?php
/**
 * POS Koperasi Al-Farmasi
 * API: Get Report Summary
 */

header('Content-Type: application/json');

require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$tanggalMulai = $_GET['tanggal_mulai'] ?? date('Y-m-d');
$tanggalAkhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

$summary = fetchOne("SELECT 
                        COUNT(*) as total_transaksi,
                        COALESCE(SUM(total_harga), 0) as total_omzet,
                        COALESCE(SUM(total_item), 0) as total_item,
                        COALESCE(AVG(total_harga), 0) as rata_rata
                    FROM transaksi 
                    WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?", 
                    [$tanggalMulai, $tanggalAkhir]);

echo json_encode([
    'success' => true,
    'summary' => [
        'total_transaksi' => number_format($summary['total_transaksi']),
        'total_omzet' => $summary['total_omzet'],
        'total_omzet_formatted' => formatRupiah($summary['total_omzet']),
        'total_item' => number_format($summary['total_item']),
        'rata_rata' => formatRupiah($summary['rata_rata'])
    ]
]);
