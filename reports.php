<?php
/**
 * Sales Report Page
 * POS Koperasi Al-Farmasi
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - POS Koperasi Al-Farmasi</title>
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
                <a href="index.php" class="nav-item">
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
                
                <a href="reports.php" class="nav-item active">
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
                    <i class="fas fa-chart-bar"></i> Laporan Penjualan
                </h1>
                <div class="d-flex align-center gap-1">
                    <label class="form-label" style="margin: 0;">Tanggal:</label>
                    <input type="date" id="date-filter" class="form-control" 
                           value="<?= date('Y-m-d') ?>" style="width: auto;">
                </div>
            </div>
            
            <!-- Stats Summary -->
            <div class="stats-grid" id="report-stats">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="total-transactions">0</h3>
                        <p>Total Transaksi</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="total-revenue">Rp 0</h3>
                        <p>Total Omzet</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="total-items">0</h3>
                        <p>Item Terjual</p>
                    </div>
                </div>
            </div>
            
            <!-- Transactions Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-list"></i> Daftar Transaksi</h2>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Tanggal</th>
                                    <th>Kasir</th>
                                    <th>Metode</th>
                                    <th class="text-right">Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="report-table-body">
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="spinner" style="margin: 20px auto;"></div>
                                        <p>Memuat data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Transaction Detail Modal -->
    <div class="modal-overlay" id="detail-modal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-receipt"></i> Detail Transaksi</h3>
                <button class="modal-close" onclick="closeModal('detail-modal')">&times;</button>
            </div>
            <div class="modal-body" id="detail-content">
                <!-- Detail will be loaded here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('detail-modal')">Tutup</button>
            </div>
        </div>
    </div>
    
    <div id="toast-container" class="toast-container"></div>
    
    <script src="js/app.js"></script>
</body>
</html>
