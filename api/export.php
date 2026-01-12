<?php
/**
 * POS Koperasi Al-Farmasi
 * API: Export Laporan ke Excel/CSV
 */

require_once '../includes/auth.php';
requireLogin();

$format = $_GET['format'] ?? 'excel';
$tanggalMulai = $_GET['tanggal_mulai'] ?? date('Y-m-d');
$tanggalAkhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'summary';

// Get data based on type
switch ($type) {
    case 'detail':
        exportDetailTransaksi($tanggalMulai, $tanggalAkhir, $format);
        break;
    case 'product':
        exportProductSales($tanggalMulai, $tanggalAkhir, $format);
        break;
    case 'daily':
        exportDailySummary($tanggalMulai, $tanggalAkhir, $format);
        break;
    default:
        exportSummary($tanggalMulai, $tanggalAkhir, $format);
}

/**
 * Export Ringkasan Laporan
 */
function exportSummary($tanggalMulai, $tanggalAkhir, $format) {
    $summary = fetchOne("SELECT 
                            COUNT(*) as total_transaksi,
                            COALESCE(SUM(total_harga), 0) as total_omzet,
                            COALESCE(SUM(total_item), 0) as total_item,
                            COALESCE(AVG(total_harga), 0) as rata_rata
                        FROM transaksi 
                        WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?", 
                        [$tanggalMulai, $tanggalAkhir]);
    
    $dailySummary = fetchAll("SELECT 
                                DATE(tanggal_transaksi) as tanggal,
                                COUNT(*) as jumlah_transaksi,
                                SUM(total_harga) as omzet
                             FROM transaksi 
                             WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
                             GROUP BY DATE(tanggal_transaksi)
                             ORDER BY tanggal", 
                             [$tanggalMulai, $tanggalAkhir]);
    
    $filename = "laporan_ringkasan_{$tanggalMulai}_sd_{$tanggalAkhir}";
    
    if ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
        header('Cache-Control: max-age=0');
        
        echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
        echo "<head><meta charset=\"UTF-8\"></head>";
        echo "<body>";
        
        // Title
        echo "<h2>LAPORAN PENJUALAN</h2>";
        echo "<h3>" . APP_NAME . "</h3>";
        echo "<p>Periode: " . date('d/m/Y', strtotime($tanggalMulai)) . " s/d " . date('d/m/Y', strtotime($tanggalAkhir)) . "</p>";
        echo "<br>";
        
        // Summary
        echo "<table border='1'>";
        echo "<tr><th colspan='2'>RINGKASAN</th></tr>";
        echo "<tr><td>Total Omzet</td><td style='text-align:right'>" . formatRupiah($summary['total_omzet']) . "</td></tr>";
        echo "<tr><td>Total Transaksi</td><td style='text-align:right'>" . number_format($summary['total_transaksi']) . "</td></tr>";
        echo "<tr><td>Total Item Terjual</td><td style='text-align:right'>" . number_format($summary['total_item']) . "</td></tr>";
        echo "<tr><td>Rata-rata/Transaksi</td><td style='text-align:right'>" . formatRupiah($summary['rata_rata']) . "</td></tr>";
        echo "</table>";
        echo "<br>";
        
        // Daily Summary
        echo "<table border='1'>";
        echo "<tr><th colspan='3'>RINGKASAN HARIAN</th></tr>";
        echo "<tr><th>Tanggal</th><th>Jumlah Transaksi</th><th>Omzet</th></tr>";
        foreach ($dailySummary as $day) {
            echo "<tr>";
            echo "<td>" . date('d/m/Y', strtotime($day['tanggal'])) . "</td>";
            echo "<td style='text-align:center'>" . $day['jumlah_transaksi'] . "</td>";
            echo "<td style='text-align:right'>" . formatRupiah($day['omzet']) . "</td>";
            echo "</tr>";
        }
        echo "<tr><td><strong>TOTAL</strong></td>";
        echo "<td style='text-align:center'><strong>" . $summary['total_transaksi'] . "</strong></td>";
        echo "<td style='text-align:right'><strong>" . formatRupiah($summary['total_omzet']) . "</strong></td></tr>";
        echo "</table>";
        
        echo "</body></html>";
    } else {
        // CSV format
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8
        
        fputcsv($output, ['LAPORAN PENJUALAN - ' . APP_NAME]);
        fputcsv($output, ['Periode: ' . date('d/m/Y', strtotime($tanggalMulai)) . ' s/d ' . date('d/m/Y', strtotime($tanggalAkhir))]);
        fputcsv($output, []);
        fputcsv($output, ['RINGKASAN']);
        fputcsv($output, ['Total Omzet', $summary['total_omzet']]);
        fputcsv($output, ['Total Transaksi', $summary['total_transaksi']]);
        fputcsv($output, ['Total Item', $summary['total_item']]);
        fputcsv($output, ['Rata-rata/Transaksi', $summary['rata_rata']]);
        fputcsv($output, []);
        fputcsv($output, ['RINGKASAN HARIAN']);
        fputcsv($output, ['Tanggal', 'Jumlah Transaksi', 'Omzet']);
        
        foreach ($dailySummary as $day) {
            fputcsv($output, [
                date('d/m/Y', strtotime($day['tanggal'])),
                $day['jumlah_transaksi'],
                $day['omzet']
            ]);
        }
        
        fclose($output);
    }
    exit;
}

/**
 * Export Detail Transaksi
 */
function exportDetailTransaksi($tanggalMulai, $tanggalAkhir, $format) {
    $transaksi = fetchAll("SELECT t.*, u.nama_lengkap as kasir 
                          FROM transaksi t 
                          JOIN users u ON t.user_id = u.id 
                          WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                          ORDER BY t.tanggal_transaksi", 
                          [$tanggalMulai, $tanggalAkhir]);
    
    $filename = "laporan_detail_transaksi_{$tanggalMulai}_sd_{$tanggalAkhir}";
    
    if ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
        
        echo "<html><head><meta charset=\"UTF-8\"></head><body>";
        echo "<h2>DETAIL TRANSAKSI</h2>";
        echo "<h3>" . APP_NAME . "</h3>";
        echo "<p>Periode: " . date('d/m/Y', strtotime($tanggalMulai)) . " s/d " . date('d/m/Y', strtotime($tanggalAkhir)) . "</p>";
        echo "<br>";
        
        echo "<table border='1'>";
        echo "<tr><th>No</th><th>No. Transaksi</th><th>Tanggal</th><th>Kasir</th><th>Item</th><th>Pembayaran</th><th>Total</th></tr>";
        
        $total = 0;
        foreach ($transaksi as $i => $t) {
            $total += $t['total_harga'];
            echo "<tr>";
            echo "<td>" . ($i + 1) . "</td>";
            echo "<td>" . $t['no_transaksi'] . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($t['tanggal_transaksi'])) . "</td>";
            echo "<td>" . escape($t['kasir']) . "</td>";
            echo "<td style='text-align:center'>" . $t['total_item'] . "</td>";
            echo "<td>" . ucfirst($t['metode_pembayaran']) . "</td>";
            echo "<td style='text-align:right'>" . formatRupiah($t['total_harga']) . "</td>";
            echo "</tr>";
        }
        
        echo "<tr><td colspan='6'><strong>TOTAL</strong></td>";
        echo "<td style='text-align:right'><strong>" . formatRupiah($total) . "</strong></td></tr>";
        echo "</table>";
        echo "</body></html>";
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['DETAIL TRANSAKSI - ' . APP_NAME]);
        fputcsv($output, ['Periode: ' . date('d/m/Y', strtotime($tanggalMulai)) . ' s/d ' . date('d/m/Y', strtotime($tanggalAkhir))]);
        fputcsv($output, []);
        fputcsv($output, ['No', 'No. Transaksi', 'Tanggal', 'Kasir', 'Item', 'Pembayaran', 'Total']);
        
        foreach ($transaksi as $i => $t) {
            fputcsv($output, [
                $i + 1,
                $t['no_transaksi'],
                date('d/m/Y H:i', strtotime($t['tanggal_transaksi'])),
                $t['kasir'],
                $t['total_item'],
                ucfirst($t['metode_pembayaran']),
                $t['total_harga']
            ]);
        }
        
        fclose($output);
    }
    exit;
}

/**
 * Export Penjualan Produk
 */
function exportProductSales($tanggalMulai, $tanggalAkhir, $format) {
    $products = fetchAll("SELECT 
                            p.kode_produk,
                            p.nama_produk,
                            COALESCE(k.nama_kategori, 'Tanpa Kategori') as kategori,
                            SUM(dt.jumlah) as total_terjual,
                            SUM(dt.subtotal) as total_pendapatan
                        FROM detail_transaksi dt
                        JOIN produk p ON dt.produk_id = p.id
                        LEFT JOIN kategori k ON p.kategori_id = k.id
                        JOIN transaksi t ON dt.transaksi_id = t.id
                        WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                        GROUP BY dt.produk_id, p.kode_produk, p.nama_produk, k.nama_kategori
                        ORDER BY total_terjual DESC", 
                        [$tanggalMulai, $tanggalAkhir]);
    
    $filename = "laporan_penjualan_produk_{$tanggalMulai}_sd_{$tanggalAkhir}";
    
    if ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
        
        echo "<html><head><meta charset=\"UTF-8\"></head><body>";
        echo "<h2>PENJUALAN PER PRODUK</h2>";
        echo "<h3>" . APP_NAME . "</h3>";
        echo "<p>Periode: " . date('d/m/Y', strtotime($tanggalMulai)) . " s/d " . date('d/m/Y', strtotime($tanggalAkhir)) . "</p>";
        echo "<br>";
        
        echo "<table border='1'>";
        echo "<tr><th>No</th><th>Kode</th><th>Nama Produk</th><th>Kategori</th><th>Qty Terjual</th><th>Pendapatan</th></tr>";
        
        $totalQty = 0;
        $totalPendapatan = 0;
        foreach ($products as $i => $p) {
            $totalQty += $p['total_terjual'];
            $totalPendapatan += $p['total_pendapatan'];
            echo "<tr>";
            echo "<td>" . ($i + 1) . "</td>";
            echo "<td>" . $p['kode_produk'] . "</td>";
            echo "<td>" . escape($p['nama_produk']) . "</td>";
            echo "<td>" . escape($p['kategori']) . "</td>";
            echo "<td style='text-align:center'>" . $p['total_terjual'] . "</td>";
            echo "<td style='text-align:right'>" . formatRupiah($p['total_pendapatan']) . "</td>";
            echo "</tr>";
        }
        
        echo "<tr><td colspan='4'><strong>TOTAL</strong></td>";
        echo "<td style='text-align:center'><strong>" . $totalQty . "</strong></td>";
        echo "<td style='text-align:right'><strong>" . formatRupiah($totalPendapatan) . "</strong></td></tr>";
        echo "</table>";
        echo "</body></html>";
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['PENJUALAN PER PRODUK - ' . APP_NAME]);
        fputcsv($output, ['Periode: ' . date('d/m/Y', strtotime($tanggalMulai)) . ' s/d ' . date('d/m/Y', strtotime($tanggalAkhir))]);
        fputcsv($output, []);
        fputcsv($output, ['No', 'Kode', 'Nama Produk', 'Kategori', 'Qty Terjual', 'Pendapatan']);
        
        foreach ($products as $i => $p) {
            fputcsv($output, [
                $i + 1,
                $p['kode_produk'],
                $p['nama_produk'],
                $p['kategori'],
                $p['total_terjual'],
                $p['total_pendapatan']
            ]);
        }
        
        fclose($output);
    }
    exit;
}

/**
 * Export Ringkasan Harian
 */
function exportDailySummary($tanggalMulai, $tanggalAkhir, $format) {
    $dailySummary = fetchAll("SELECT 
                                DATE(tanggal_transaksi) as tanggal,
                                COUNT(*) as jumlah_transaksi,
                                SUM(total_item) as total_item,
                                SUM(total_harga) as omzet,
                                SUM(CASE WHEN metode_pembayaran = 'tunai' THEN total_harga ELSE 0 END) as tunai,
                                SUM(CASE WHEN metode_pembayaran = 'qris' THEN total_harga ELSE 0 END) as qris,
                                SUM(CASE WHEN metode_pembayaran = 'transfer' THEN total_harga ELSE 0 END) as transfer
                             FROM transaksi 
                             WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
                             GROUP BY DATE(tanggal_transaksi)
                             ORDER BY tanggal", 
                             [$tanggalMulai, $tanggalAkhir]);
    
    $filename = "laporan_harian_{$tanggalMulai}_sd_{$tanggalAkhir}";
    
    if ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
        
        echo "<html><head><meta charset=\"UTF-8\"></head><body>";
        echo "<h2>RINGKASAN HARIAN</h2>";
        echo "<h3>" . APP_NAME . "</h3>";
        echo "<p>Periode: " . date('d/m/Y', strtotime($tanggalMulai)) . " s/d " . date('d/m/Y', strtotime($tanggalAkhir)) . "</p>";
        echo "<br>";
        
        echo "<table border='1'>";
        echo "<tr><th>Tanggal</th><th>Transaksi</th><th>Item</th><th>Tunai</th><th>QRIS</th><th>Transfer</th><th>Total Omzet</th></tr>";
        
        $totalTrx = 0;
        $totalItem = 0;
        $totalTunai = 0;
        $totalQris = 0;
        $totalTransfer = 0;
        $totalOmzet = 0;
        
        foreach ($dailySummary as $day) {
            $totalTrx += $day['jumlah_transaksi'];
            $totalItem += $day['total_item'];
            $totalTunai += $day['tunai'];
            $totalQris += $day['qris'];
            $totalTransfer += $day['transfer'];
            $totalOmzet += $day['omzet'];
            
            echo "<tr>";
            echo "<td>" . date('d/m/Y', strtotime($day['tanggal'])) . "</td>";
            echo "<td style='text-align:center'>" . $day['jumlah_transaksi'] . "</td>";
            echo "<td style='text-align:center'>" . $day['total_item'] . "</td>";
            echo "<td style='text-align:right'>" . formatRupiah($day['tunai']) . "</td>";
            echo "<td style='text-align:right'>" . formatRupiah($day['qris']) . "</td>";
            echo "<td style='text-align:right'>" . formatRupiah($day['transfer']) . "</td>";
            echo "<td style='text-align:right'>" . formatRupiah($day['omzet']) . "</td>";
            echo "</tr>";
        }
        
        echo "<tr>";
        echo "<td><strong>TOTAL</strong></td>";
        echo "<td style='text-align:center'><strong>$totalTrx</strong></td>";
        echo "<td style='text-align:center'><strong>$totalItem</strong></td>";
        echo "<td style='text-align:right'><strong>" . formatRupiah($totalTunai) . "</strong></td>";
        echo "<td style='text-align:right'><strong>" . formatRupiah($totalQris) . "</strong></td>";
        echo "<td style='text-align:right'><strong>" . formatRupiah($totalTransfer) . "</strong></td>";
        echo "<td style='text-align:right'><strong>" . formatRupiah($totalOmzet) . "</strong></td>";
        echo "</tr>";
        echo "</table>";
        echo "</body></html>";
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['RINGKASAN HARIAN - ' . APP_NAME]);
        fputcsv($output, ['Periode: ' . date('d/m/Y', strtotime($tanggalMulai)) . ' s/d ' . date('d/m/Y', strtotime($tanggalAkhir))]);
        fputcsv($output, []);
        fputcsv($output, ['Tanggal', 'Transaksi', 'Item', 'Tunai', 'QRIS', 'Transfer', 'Total Omzet']);
        
        foreach ($dailySummary as $day) {
            fputcsv($output, [
                date('d/m/Y', strtotime($day['tanggal'])),
                $day['jumlah_transaksi'],
                $day['total_item'],
                $day['tunai'],
                $day['qris'],
                $day['transfer'],
                $day['omzet']
            ]);
        }
        
        fclose($output);
    }
    exit;
}
