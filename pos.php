<?php
/**
 * POS / Cashier Page
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
    <title>Kasir - POS Koperasi Al-Farmasi</title>
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
                <a href="pos.php" class="nav-item active">
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
                    <i class="fas fa-cash-register"></i> Kasir / POS
                </h1>
                <span class="text-muted"><?= date('l, d F Y') ?></span>
            </div>
            
            <!-- POS Container -->
            <div class="pos-container">
                <!-- Products Section -->
                <div class="pos-products">
                    <div class="pos-search">
                        <input type="text" id="search-input" class="pos-search-input" 
                               placeholder="🔍 Cari kode atau nama produk..." autofocus>
                    </div>
                    <div class="pos-product-list" id="product-list">
                        <!-- Products will be loaded here -->
                        <div class="text-center" style="padding: 40px;">
                            <div class="spinner"></div>
                            <p class="mt-1">Memuat produk...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Cart Section -->
                <div class="pos-cart">
                    <div class="cart-header">
                        <h3><i class="fas fa-shopping-cart"></i> Keranjang Belanja</h3>
                    </div>
                    
                    <div class="cart-items" id="cart-items">
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Keranjang kosong</p>
                        </div>
                    </div>
                    
                    <div class="cart-footer">
                        <div class="cart-total">
                            <span class="cart-total-label">TOTAL:</span>
                            <span class="cart-total-amount" id="cart-total">Rp 0</span>
                        </div>
                        
                        <div class="payment-buttons">
                            <button class="btn btn-success btn-lg" onclick="processPayment('tunai')">
                                <i class="fas fa-money-bill-wave"></i> TUNAI
                            </button>
                            <button class="btn btn-primary btn-lg" onclick="processPayment('non-tunai')">
                                <i class="fas fa-credit-card"></i> NON-TUNAI
                            </button>
                        </div>
                        
                        <button class="btn btn-outline mt-1" onclick="clearCart()" style="width: 100%;">
                            <i class="fas fa-trash"></i> Kosongkan Keranjang
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Cash Payment Modal -->
    <div class="modal-overlay" id="cash-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-money-bill-wave"></i> Pembayaran Tunai</h3>
                <button class="modal-close" onclick="closeModal('cash-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Total Belanja</label>
                    <div class="cart-total-amount" id="total-amount" style="font-size: 2rem;">Rp 0</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Jumlah Bayar</label>
                    <input type="number" id="cash-amount" class="form-control form-control-lg" 
                           placeholder="Masukkan jumlah uang" oninput="calculateChange()">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Kembalian</label>
                    <div class="cart-total-amount text-success" id="change-amount" style="font-size: 2rem;">Rp 0</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('cash-modal')">Batal</button>
                <button class="btn btn-success btn-lg" onclick="confirmCashPayment()">
                    <i class="fas fa-check"></i> Bayar Sekarang
                </button>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div class="modal-overlay" id="success-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title text-success"><i class="fas fa-check-circle"></i> Transaksi Berhasil!</h3>
                <button class="modal-close" onclick="closeModal('success-modal')">&times;</button>
            </div>
            <div class="modal-body text-center">
                <div style="font-size: 4rem; color: var(--success-color); margin: 20px 0;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <p style="font-size: 1.2rem;">Nomor Transaksi:</p>
                <h2 id="success-trx-number" style="color: var(--primary-color);">-</h2>
                <p class="text-muted mt-2">Simpan nomor ini untuk referensi</p>
            </div>
            <div class="modal-footer" style="justify-content: center;">
                <button class="btn btn-primary btn-lg" onclick="closeModal('success-modal')">
                    <i class="fas fa-arrow-left"></i> Transaksi Baru
                </button>
            </div>
        </div>
    </div>
    
    <div id="toast-container" class="toast-container"></div>
    
    <script src="js/app.js"></script>
</body>
</html>
