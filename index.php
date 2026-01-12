<?php
/**
 * Dashboard / Main Index
 * POS Koperasi Al-Farmasi
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getUser();
$db = Database::getInstance()->getConnection();

// Get today's stats
$today = date('Y-m-d');

// Total transactions today
$stmt = $db->prepare("SELECT COUNT(*) as count FROM transactions WHERE DATE(tanggal_transaksi) = ?");
$stmt->execute([$today]);
$todayTransactions = $stmt->fetch()['count'];

// Total revenue today
$stmt = $db->prepare("SELECT COALESCE(SUM(total_harga), 0) as total FROM transactions WHERE DATE(tanggal_transaksi) = ?");
$stmt->execute([$today]);
$todayRevenue = $stmt->fetch()['total'];

// Total products
$stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE status = 'aktif'");
$stmt->execute();
$totalProducts = $stmt->fetch()['count'];

// Low stock products
$stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE status = 'aktif' AND stok <= 5");
$stmt->execute();
$lowStockProducts = $stmt->fetch()['count'];

// Recent transactions
$stmt = $db->prepare("
    SELECT t.*, u.nama_lengkap as nama_kasir
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.tanggal_transaksi DESC
    LIMIT 5
");
$stmt->execute();
$recentTransactions = $stmt->fetchAll();

// Determine active page
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Koperasi Al-Farmasi</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="page-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-store"></i> POS Koperasi</h1>
                <small>Al-Farmasi</small>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?= $page === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="pos.php" class="nav-item">
                    <i class="fas fa-cash-register"></i> Kasir / POS
                </a>
                
                <?php if ($auth->isAdmin()): ?>
                <a href="products.php" class="nav-item">
                    <i class="fas fa-box"></i> Produk
                </a>
                <a href="stock.php" class="nav-item">
                    <i class="fas fa-cubes"></i> Stok
                </a>
                <?php endif; ?>
                
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i> Laporan
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars($user['nama_lengkap']) ?></strong>
                        <br><small><?= ucfirst($user['role']) ?></small>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-danger btn-sm mt-1" style="width: 100%;">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-home"></i> Dashboard
                </h1>
                <span class="text-muted"><?= date('l, d F Y') ?></span>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $todayTransactions ?></h3>
                        <p>Transaksi Hari Ini</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= formatRupiah($todayRevenue) ?></h3>
                        <p>Omzet Hari Ini</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $totalProducts ?></h3>
                        <p>Total Produk</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $lowStockProducts ?></h3>
                        <p>Stok Menipis</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mb-2">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-bolt"></i> Aksi Cepat</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-1" style="flex-wrap: wrap;">
                        <a href="pos.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-cash-register"></i> Mulai Transaksi
                        </a>
                        <?php if ($auth->isAdmin()): ?>
                        <a href="products.php" class="btn btn-success btn-lg">
                            <i class="fas fa-plus"></i> Tambah Produk
                        </a>
                        <a href="stock.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-cubes"></i> Update Stok
                        </a>
                        <?php endif; ?>
                        <a href="reports.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-chart-bar"></i> Lihat Laporan
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-history"></i> Transaksi Terakhir</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($recentTransactions)): ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <p>Belum ada transaksi</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No. Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Kasir</th>
                                        <th>Metode</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentTransactions as $trx): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($trx['nomor_transaksi']) ?></strong></td>
                                        <td><?= formatDate($trx['tanggal_transaksi']) ?></td>
                                        <td><?= htmlspecialchars($trx['nama_kasir']) ?></td>
                                        <td>
                                            <span class="badge <?= $trx['metode_pembayaran'] === 'tunai' ? 'badge-success' : 'badge-info' ?>">
                                                <?= strtoupper($trx['metode_pembayaran']) ?>
                                            </span>
                                        </td>
                                        <td class="text-right"><strong><?= formatRupiah($trx['total_harga']) ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="js/app.js"></script>
</body>
</html>
