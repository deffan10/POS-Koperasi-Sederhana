<?php
/**
 * POS Koperasi Al-Farmasi
 * Dashboard
 */

require_once 'includes/auth.php';
requireLogin();

$pageTitle = 'Dashboard';

// Statistik hari ini
$today = date('Y-m-d');
$stats = [];

// Total transaksi hari ini
$result = fetchOne("SELECT COUNT(*) as total, COALESCE(SUM(total_harga), 0) as omzet 
                   FROM transaksi WHERE DATE(tanggal_transaksi) = ?", [$today]);
$stats['transaksi_hari_ini'] = $result['total'];
$stats['omzet_hari_ini'] = $result['omzet'];

// Laba hari ini (hanya untuk admin)
$stats['laba_hari_ini'] = 0;
if (isAdmin()) {
    $labaHariIni = fetchOne("SELECT COALESCE(SUM(dt.laba), 0) as laba 
                            FROM detail_transaksi dt 
                            JOIN transaksi t ON dt.transaksi_id = t.id 
                            WHERE DATE(t.tanggal_transaksi) = ?", [$today]);
    $stats['laba_hari_ini'] = $labaHariIni['laba'];
}

// Total produk
$stats['total_produk'] = fetchOne("SELECT COUNT(*) as total FROM produk WHERE status = 'aktif'")['total'];

// Produk stok menipis (< 10)
$stats['stok_menipis'] = fetchOne("SELECT COUNT(*) as total FROM produk WHERE stok < 10 AND status = 'aktif'")['total'];

// Transaksi terakhir
$transaksiTerakhir = fetchAll("SELECT t.*, u.nama_lengkap as kasir 
                              FROM transaksi t 
                              JOIN users u ON t.user_id = u.id 
                              ORDER BY t.created_at DESC LIMIT 5");

// Produk terlaris hari ini
$produkTerlaris = fetchAll("SELECT p.nama_produk, SUM(dt.jumlah) as total_terjual
                           FROM detail_transaksi dt
                           JOIN produk p ON dt.produk_id = p.id
                           JOIN transaksi t ON dt.transaksi_id = t.id
                           WHERE DATE(t.tanggal_transaksi) = ?
                           GROUP BY dt.produk_id, p.nama_produk
                           ORDER BY total_terjual DESC LIMIT 5", [$today]);

// Cek apakah bulan sebelumnya sudah ditutup (reminder untuk Admin)
$reminderTutupBuku = null;
if (isAdmin()) {
    // Cek bulan lalu
    $bulanLalu = date('n', strtotime('-1 month'));
    $tahunLalu = date('Y', strtotime('-1 month'));
    
    // Cek apakah sudah ditutup
    $sudahDitutup = fetchOne("SELECT id FROM tutup_buku WHERE bulan = ? AND tahun = ?", [$bulanLalu, $tahunLalu]);
    
    if (!$sudahDitutup) {
        // Cek apakah ada transaksi di bulan lalu
        $tanggalMulaiBulanLalu = sprintf('%04d-%02d-01', $tahunLalu, $bulanLalu);
        $tanggalAkhirBulanLalu = date('Y-m-t', strtotime($tanggalMulaiBulanLalu));
        
        $adaTransaksi = fetchOne("SELECT COUNT(*) as total FROM transaksi 
                                  WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?", 
                                  [$tanggalMulaiBulanLalu, $tanggalAkhirBulanLalu]);
        
        if ($adaTransaksi['total'] > 0) {
            $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                          'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $reminderTutupBuku = [
                'bulan' => $bulanLalu,
                'tahun' => $tahunLalu,
                'nama_bulan' => $namaBulan[$bulanLalu],
                'total_transaksi' => $adaTransaksi['total']
            ];
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <?php if ($reminderTutupBuku): ?>
    <!-- Reminder Tutup Buku -->
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-journal-bookmark display-6 me-3"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Reminder: Tutup Buku <?= $reminderTutupBuku['nama_bulan'] ?> <?= $reminderTutupBuku['tahun'] ?>
                </h5>
                <p class="mb-0">
                    Bulan <?= $reminderTutupBuku['nama_bulan'] ?> <?= $reminderTutupBuku['tahun'] ?> belum ditutup. 
                    Terdapat <strong><?= number_format($reminderTutupBuku['total_transaksi']) ?> transaksi</strong> yang perlu direkap.
                </p>
            </div>
            <a href="closing.php" class="btn btn-warning btn-lg ms-3">
                <i class="bi bi-lock me-1"></i>Tutup Buku Sekarang
            </a>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="welcome-card">
                <h2 class="mb-1">
                    <i class="bi bi-emoji-smile me-2"></i>Selamat Datang, <?= escape($_SESSION['nama_lengkap']) ?>!
                </h2>
                <p class="mb-0" style="opacity: 0.85;"><?= date('l, d F Y') ?></p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-3">
                <a href="pos.php" class="btn btn-success btn-lg quick-action-btn">
                    <i class="bi bi-cart-plus me-2"></i>Mulai Transaksi
                </a>
                <?php if (isAdmin()): ?>
                <a href="products.php" class="btn btn-primary btn-lg quick-action-btn">
                    <i class="bi bi-plus-circle me-2"></i>Tambah Produk
                </a>
                <a href="stock.php" class="btn btn-info btn-lg quick-action-btn">
                    <i class="bi bi-box-arrow-in-down me-2"></i>Tambah Stok
                </a>
                <?php endif; ?>
                <a href="reports.php" class="btn btn-warning btn-lg quick-action-btn">
                    <i class="bi bi-graph-up me-2"></i>Lihat Laporan
                </a>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card stat-card-success">
                <div class="stat-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= formatRupiah($stats['omzet_hari_ini']) ?></h3>
                    <p class="stat-label">Omzet Hari Ini</p>
                </div>
            </div>
        </div>
        
        <?php if (isAdmin()): ?>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card" style="border-left: 4px solid #198754; background: linear-gradient(135deg, #d1e7dd 0%, #badbcc 100%);">
                <div class="stat-icon" style="color: #198754;">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value text-success"><?= formatRupiah($stats['laba_hari_ini']) ?></h3>
                    <p class="stat-label">Laba Hari Ini</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card stat-card-primary">
                <div class="stat-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= $stats['transaksi_hari_ini'] ?></h3>
                    <p class="stat-label">Transaksi Hari Ini</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card stat-card-info">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= $stats['total_produk'] ?></h3>
                    <p class="stat-label">Total Produk</p>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card stat-card-warning">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-value"><?= $stats['stok_menipis'] ?></h3>
                    <p class="stat-label">Stok Menipis</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Transaksi Terakhir -->
        <div class="col-lg-7 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Transaksi Terakhir</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($transaksiTerakhir) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Waktu</th>
                                    <th>Kasir</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaksiTerakhir as $trx): ?>
                                <tr>
                                    <td><code><?= escape($trx['no_transaksi']) ?></code></td>
                                    <td><?= date('H:i', strtotime($trx['tanggal_transaksi'])) ?></td>
                                    <td><?= escape($trx['kasir']) ?></td>
                                    <td class="text-end fw-bold"><?= formatRupiah($trx['total_harga']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox display-4"></i>
                        <p class="mt-2">Belum ada transaksi hari ini</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white">
                    <a href="transactions.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-right me-1"></i>Lihat Semua Transaksi
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Produk Terlaris -->
        <div class="col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Produk Terlaris Hari Ini</h5>
                </div>
                <div class="card-body">
                    <?php if (count($produkTerlaris) > 0): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($produkTerlaris as $index => $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>
                                <span class="badge bg-success me-2"><?= $index + 1 ?></span>
                                <?= escape($item['nama_produk']) ?>
                            </span>
                            <span class="badge bg-primary rounded-pill"><?= $item['total_terjual'] ?> terjual</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-emoji-neutral display-4"></i>
                        <p class="mt-2">Belum ada penjualan hari ini</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
