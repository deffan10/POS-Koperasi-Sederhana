<?php
/**
 * POS Koperasi Al-Farmasi
 * Laporan Penjualan
 */

require_once 'includes/auth.php';
requireLogin();

$pageTitle = 'Laporan Penjualan';

// Filter
$tanggalMulai = $_GET['tanggal_mulai'] ?? date('Y-m-d');
$tanggalAkhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

// Get summary
$summary = fetchOne("SELECT 
                        COUNT(*) as total_transaksi,
                        COALESCE(SUM(total_harga), 0) as total_omzet,
                        COALESCE(SUM(total_item), 0) as total_item,
                        COALESCE(AVG(total_harga), 0) as rata_rata
                    FROM transaksi 
                    WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?", 
                    [$tanggalMulai, $tanggalAkhir]);

// Get daily summary with payment method breakdown
$dailySummary = fetchAll("SELECT 
                            DATE(tanggal_transaksi) as tanggal,
                            COUNT(*) as jumlah_transaksi,
                            SUM(total_harga) as omzet,
                            SUM(CASE WHEN metode_pembayaran = 'tunai' THEN total_harga ELSE 0 END) as tunai,
                            SUM(CASE WHEN metode_pembayaran = 'qris' THEN total_harga ELSE 0 END) as qris,
                            SUM(CASE WHEN metode_pembayaran = 'transfer' THEN total_harga ELSE 0 END) as transfer
                         FROM transaksi 
                         WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
                         GROUP BY DATE(tanggal_transaksi)
                         ORDER BY tanggal DESC", 
                         [$tanggalMulai, $tanggalAkhir]);

// Get payment method summary
$paymentSummary = fetchAll("SELECT 
                              metode_pembayaran,
                              COUNT(*) as jumlah,
                              SUM(total_harga) as total
                           FROM transaksi 
                           WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?
                           GROUP BY metode_pembayaran", 
                           [$tanggalMulai, $tanggalAkhir]);

// Get top products
$topProducts = fetchAll("SELECT 
                            p.nama_produk,
                            SUM(dt.jumlah) as total_terjual,
                            SUM(dt.subtotal) as total_pendapatan
                        FROM detail_transaksi dt
                        JOIN produk p ON dt.produk_id = p.id
                        JOIN transaksi t ON dt.transaksi_id = t.id
                        WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                        GROUP BY dt.produk_id, p.nama_produk
                        ORDER BY total_terjual DESC
                        LIMIT 10", 
                        [$tanggalMulai, $tanggalAkhir]);

// Get category summary
$categorySummary = fetchAll("SELECT 
                               COALESCE(k.nama_kategori, 'Tanpa Kategori') as kategori,
                               SUM(dt.jumlah) as total_terjual,
                               SUM(dt.subtotal) as total_pendapatan
                            FROM detail_transaksi dt
                            JOIN produk p ON dt.produk_id = p.id
                            LEFT JOIN kategori k ON p.kategori_id = k.id
                            JOIN transaksi t ON dt.transaksi_id = t.id
                            WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                            GROUP BY k.id, k.nama_kategori
                            ORDER BY total_pendapatan DESC", 
                            [$tanggalMulai, $tanggalAkhir]);

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-graph-up me-2"></i>Laporan Penjualan</h2>
        <div class="btn-group">
            <div class="dropdown">
                <button class="btn btn-success btn-lg dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-2"></i>Download
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Format Excel (.xls)</h6></li>
                    <li>
                        <a class="dropdown-item" href="api/export.php?format=excel&type=summary&tanggal_mulai=<?= $tanggalMulai ?>&tanggal_akhir=<?= $tanggalAkhir ?>">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Ringkasan Laporan
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="api/export.php?format=excel&type=daily&tanggal_mulai=<?= $tanggalMulai ?>&tanggal_akhir=<?= $tanggalAkhir ?>">
                            <i class="bi bi-calendar3 me-2"></i>Ringkasan Harian
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="api/export.php?format=excel&type=detail&tanggal_mulai=<?= $tanggalMulai ?>&tanggal_akhir=<?= $tanggalAkhir ?>">
                            <i class="bi bi-receipt me-2"></i>Detail Transaksi
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="api/export.php?format=excel&type=product&tanggal_mulai=<?= $tanggalMulai ?>&tanggal_akhir=<?= $tanggalAkhir ?>">
                            <i class="bi bi-box-seam me-2"></i>Penjualan Produk
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header">Format CSV</h6></li>
                    <li>
                        <a class="dropdown-item" href="api/export.php?format=csv&type=summary&tanggal_mulai=<?= $tanggalMulai ?>&tanggal_akhir=<?= $tanggalAkhir ?>">
                            <i class="bi bi-filetype-csv me-2"></i>Ringkasan (CSV)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="api/export.php?format=csv&type=detail&tanggal_mulai=<?= $tanggalMulai ?>&tanggal_akhir=<?= $tanggalAkhir ?>">
                            <i class="bi bi-filetype-csv me-2"></i>Detail Transaksi (CSV)
                        </a>
                    </li>
                </ul>
            </div>
            <?php if (isAdmin()): ?>
            <a href="closing.php" class="btn btn-outline-primary btn-lg ms-2">
                <i class="bi bi-journal-check me-2"></i>Tutup Buku
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control form-control-lg" name="tanggal_mulai" 
                           value="<?= escape($tanggalMulai) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="date" class="form-control form-control-lg" name="tanggal_akhir" 
                           value="<?= escape($tanggalAkhir) ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-search me-2"></i>Tampilkan Laporan
                    </button>
                </div>
            </form>
            
            <!-- Quick Filters -->
            <div class="mt-3">
                <span class="text-muted me-2">Filter Cepat:</span>
                <a href="?tanggal_mulai=<?= date('Y-m-d') ?>&tanggal_akhir=<?= date('Y-m-d') ?>" 
                   class="btn btn-outline-secondary btn-sm">Hari Ini</a>
                <a href="?tanggal_mulai=<?= date('Y-m-d', strtotime('-7 days')) ?>&tanggal_akhir=<?= date('Y-m-d') ?>" 
                   class="btn btn-outline-secondary btn-sm">7 Hari Terakhir</a>
                <a href="?tanggal_mulai=<?= date('Y-m-01') ?>&tanggal_akhir=<?= date('Y-m-t') ?>" 
                   class="btn btn-outline-secondary btn-sm">Bulan Ini</a>
                <a href="?tanggal_mulai=<?= date('Y-m-01', strtotime('-1 month')) ?>&tanggal_akhir=<?= date('Y-m-t', strtotime('-1 month')) ?>" 
                   class="btn btn-outline-secondary btn-sm">Bulan Lalu</a>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card stat-card-success">
                <div class="stat-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= formatRupiah($summary['total_omzet']) ?></h3>
                    <p class="stat-label">Total Omzet</p>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card stat-card-primary">
                <div class="stat-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= number_format($summary['total_transaksi']) ?></h3>
                    <p class="stat-label">Total Transaksi</p>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card stat-card-info">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= number_format($summary['total_item']) ?></h3>
                    <p class="stat-label">Item Terjual</p>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card stat-card-warning">
                <div class="stat-icon">
                    <i class="bi bi-calculator"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= formatRupiah($summary['rata_rata']) ?></h3>
                    <p class="stat-label">Rata-rata/Transaksi</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Daily Summary -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Ringkasan Harian</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($dailySummary) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th class="text-center">Trx</th>
                                    <th class="text-end text-success">Tunai</th>
                                    <th class="text-end text-primary">QRIS</th>
                                    <th class="text-end text-info">Transfer</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalTunai = 0;
                                $totalQris = 0;
                                $totalTransfer = 0;
                                foreach ($dailySummary as $day): 
                                    $totalTunai += $day['tunai'];
                                    $totalQris += $day['qris'];
                                    $totalTransfer += $day['transfer'];
                                ?>
                                <tr>
                                    <td><?= date('D, d M Y', strtotime($day['tanggal'])) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= $day['jumlah_transaksi'] ?></span>
                                    </td>
                                    <td class="text-end <?= $day['tunai'] > 0 ? 'text-success' : 'text-muted' ?>">
                                        <?= $day['tunai'] > 0 ? formatRupiah($day['tunai']) : '-' ?>
                                    </td>
                                    <td class="text-end <?= $day['qris'] > 0 ? 'text-primary' : 'text-muted' ?>">
                                        <?= $day['qris'] > 0 ? formatRupiah($day['qris']) : '-' ?>
                                    </td>
                                    <td class="text-end <?= $day['transfer'] > 0 ? 'text-info' : 'text-muted' ?>">
                                        <?= $day['transfer'] > 0 ? formatRupiah($day['transfer']) : '-' ?>
                                    </td>
                                    <td class="text-end fw-bold"><?= formatRupiah($day['omzet']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-success">
                                <tr>
                                    <td><strong>TOTAL</strong></td>
                                    <td class="text-center"><strong><?= $summary['total_transaksi'] ?></strong></td>
                                    <td class="text-end"><strong><?= formatRupiah($totalTunai) ?></strong></td>
                                    <td class="text-end"><strong><?= formatRupiah($totalQris) ?></strong></td>
                                    <td class="text-end"><strong><?= formatRupiah($totalTransfer) ?></strong></td>
                                    <td class="text-end"><strong><?= formatRupiah($summary['total_omzet']) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox display-4"></i>
                        <p class="mb-0 mt-2">Tidak ada data untuk periode ini</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Payment Method Summary -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Metode Pembayaran</h5>
                </div>
                <div class="card-body">
                    <?php if (count($paymentSummary) > 0): ?>
                    <?php 
                    $paymentIcons = [
                        'tunai' => 'cash',
                        'qris' => 'qr-code',
                        'transfer' => 'bank'
                    ];
                    $paymentColors = [
                        'tunai' => 'success',
                        'qris' => 'primary',
                        'transfer' => 'info'
                    ];
                    ?>
                    <?php foreach ($paymentSummary as $payment): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <i class="bi bi-<?= $paymentIcons[$payment['metode_pembayaran']] ?? 'credit-card' ?> me-2 text-<?= $paymentColors[$payment['metode_pembayaran']] ?? 'secondary' ?>"></i>
                            <strong><?= strtoupper($payment['metode_pembayaran']) ?></strong>
                            <br>
                            <small class="text-muted"><?= $payment['jumlah'] ?> transaksi</small>
                        </div>
                        <span class="h5 mb-0 text-success"><?= formatRupiah($payment['total']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        Tidak ada data
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Top Products -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Produk Terlaris</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($topProducts) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">#</th>
                                    <th>Produk</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $index => $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($index < 3): ?>
                                        <span class="badge bg-<?= $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'danger') ?>">
                                            <?= $index + 1 ?>
                                        </span>
                                        <?php else: ?>
                                        <?= $index + 1 ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= escape($product['nama_produk']) ?></td>
                                    <td class="text-center"><span class="badge bg-success"><?= $product['total_terjual'] ?></span></td>
                                    <td class="text-end"><?= formatRupiah($product['total_pendapatan']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        Tidak ada data
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Category Summary -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-tags me-2"></i>Penjualan per Kategori</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($categorySummary) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kategori</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorySummary as $cat): ?>
                                <tr>
                                    <td><?= escape($cat['kategori']) ?></td>
                                    <td class="text-center"><span class="badge bg-info"><?= $cat['total_terjual'] ?></span></td>
                                    <td class="text-end"><?= formatRupiah($cat['total_pendapatan']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        Tidak ada data
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
