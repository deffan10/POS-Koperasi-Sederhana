<?php
/**
 * Stock Management Page
 * POS Koperasi Al-Farmasi
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$auth = new Auth();
$auth->requireAdmin(); // Only admin can access

$user = $auth->getUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok - POS Koperasi Al-Farmasi</title>
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
                <a href="products.php" class="nav-item">
                    <i class="fas fa-box"></i> Produk
                </a>
                <a href="stock.php" class="nav-item active">
                    <i class="fas fa-cubes"></i> Stok
                </a>
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
                    <i class="fas fa-cubes"></i> Manajemen Stok
                </h1>
                <button class="btn btn-primary" onclick="openModal('stock-modal')">
                    <i class="fas fa-plus"></i> Tambah Stok
                </button>
            </div>
            
            <!-- Stock History Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-history"></i> Riwayat Perubahan Stok</h2>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kode</th>
                                    <th>Produk</th>
                                    <th>Jenis</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Stok</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody id="stock-history-body">
                                <tr>
                                    <td colspan="7" class="text-center">
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
    
    <!-- Add Stock Modal -->
    <div class="modal-overlay" id="stock-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Stok</h3>
                <button class="modal-close" onclick="closeModal('stock-modal')">&times;</button>
            </div>
            <form id="stock-form" onsubmit="addStock(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Pilih Produk *</label>
                        <select id="stock-product-id" name="product_id" class="form-control" required>
                            <option value="">-- Pilih Produk --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Jenis Perubahan *</label>
                        <select name="jenis_perubahan" class="form-control" required>
                            <option value="masuk">Stok Masuk</option>
                            <option value="keluar">Stok Keluar</option>
                            <option value="penyesuaian">Penyesuaian (Set ke nilai tertentu)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Jumlah *</label>
                        <input type="number" name="jumlah" class="form-control" 
                               placeholder="Masukkan jumlah" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3" 
                                  placeholder="Alasan perubahan stok (opsional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('stock-modal')">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="toast-container" class="toast-container"></div>
    
    <script src="js/app.js"></script>
</body>
</html>
