<?php
/**
 * Product Management Page
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
    <title>Produk - POS Koperasi Al-Farmasi</title>
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
                <a href="products.php" class="nav-item active">
                    <i class="fas fa-box"></i> Produk
                </a>
                <a href="stock.php" class="nav-item">
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
                    <i class="fas fa-box"></i> Manajemen Produk
                </h1>
                <button class="btn btn-primary" onclick="newProduct()">
                    <i class="fas fa-plus"></i> Tambah Produk
                </button>
            </div>
            
            <!-- Products Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Produk</th>
                                    <th>Kategori</th>
                                    <th class="text-right">Harga</th>
                                    <th class="text-center">Stok</th>
                                    <th>Satuan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="product-table-body">
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
    
    <!-- Product Modal -->
    <div class="modal-overlay" id="product-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title">Tambah Produk Baru</h3>
                <button class="modal-close" onclick="closeModal('product-modal')">&times;</button>
            </div>
            <form id="product-form" onsubmit="saveProduct(event)">
                <div class="modal-body">
                    <input type="hidden" id="product-id" name="id">
                    
                    <div class="form-group">
                        <label class="form-label">Kode Produk *</label>
                        <input type="text" id="kode-produk" name="kode_produk" class="form-control" 
                               placeholder="Contoh: MKN001" required>
                        <small class="text-muted">Kode unik untuk identifikasi produk</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nama Produk *</label>
                        <input type="text" id="nama-produk" name="nama_produk" class="form-control" 
                               placeholder="Masukkan nama produk" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select id="category-id" name="category_id" class="form-control">
                            <option value="">-- Pilih Kategori --</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Harga Jual *</label>
                        <input type="number" id="harga-jual" name="harga_jual" class="form-control" 
                               placeholder="0" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Stok Awal</label>
                        <input type="number" id="stok" name="stok" class="form-control" 
                               placeholder="0" min="0" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Satuan</label>
                        <input type="text" id="satuan" name="satuan" class="form-control" 
                               placeholder="pcs, botol, pack, dll" value="pcs">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('product-modal')">Batal</button>
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
