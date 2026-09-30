<?php
/**
 * POS Koperasi Al-Farmasi
 * API: Detail Laba per Item per Metode Pembayaran untuk Tutup Buku
 */

require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

$bulan = intval($_GET['bulan'] ?? 0);
$tahun = intval($_GET['tahun'] ?? 0);

if ($bulan < 1 || $bulan > 12 || $tahun < 2000) {
    echo json_encode(['success' => false, 'message' => 'Periode tidak valid']);
    exit;
}

$tanggalMulai = sprintf('%04d-%02d-01', $tahun, $bulan);
$tanggalAkhir = date('Y-m-t', strtotime($tanggalMulai));

$items = fetchAll("SELECT 
        dt.produk_id,
        dt.kode_produk,
        dt.nama_produk,
        COALESCE(SUM(dt.jumlah), 0) as total_qty,
        COALESCE(SUM(CASE WHEN t.metode_pembayaran = 'tunai' THEN dt.laba ELSE 0 END), 0) as laba_tunai,
        COALESCE(SUM(CASE WHEN t.metode_pembayaran = 'qris' THEN dt.laba ELSE 0 END), 0) as laba_qris,
        COALESCE(SUM(CASE WHEN t.metode_pembayaran = 'transfer' THEN dt.laba ELSE 0 END), 0) as laba_transfer,
        COALESCE(SUM(dt.laba), 0) as total_laba
    FROM detail_transaksi dt
    JOIN transaksi t ON dt.transaksi_id = t.id
    WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
    GROUP BY dt.produk_id, dt.kode_produk, dt.nama_produk
    ORDER BY total_laba DESC, dt.nama_produk ASC", 
    [$tanggalMulai, $tanggalAkhir]);

echo json_encode([
    'success' => true,
    'bulan' => $bulan,
    'tahun' => $tahun,
    'items' => $items
]);
