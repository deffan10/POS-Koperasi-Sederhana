<?php
/**
 * POS Koperasi Al-Farmasi
 * Halaman POS (Kasir)
 */

require_once 'includes/auth.php';
requireLogin();

$pageTitle = 'Kasir';

// Generate CSRF token untuk JavaScript
$csrfToken = generateCSRFToken();

// Ambil semua produk untuk autocomplete
$produk = fetchAll("SELECT id, kode_produk, nama_produk, harga_jual, stok 
                   FROM produk WHERE status = 'aktif' ORDER BY nama_produk");

// Ambil settings pembayaran
$paymentSettings = getPaymentSettings();

include 'includes/header.php';
?>

<div class="container-fluid py-3">
    <div class="row">
        <!-- Left Panel - Product Input -->
        <div class="col-lg-7 mb-3">
            <div class="card pos-card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-cart3 me-2"></i>Input Barang</h5>
                </div>
                <div class="card-body">
                    <!-- Search Product -->
                    <div class="row mb-3">
                        <div class="col-md-8 mb-2 mb-md-0">
                            <label class="form-label fw-bold">Cari Produk (Kode/Nama)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" 
                                       class="form-control" 
                                       id="searchProduct" 
                                       placeholder="Ketik kode atau nama produk..."
                                       autocomplete="off">
                            </div>
                            <div id="productSuggestions" class="suggestions-box"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Jumlah</label>
                            <div class="input-group input-group-lg">
                                <button class="btn btn-outline-secondary" type="button" id="btnMinus">
                                    <i class="bi bi-dash-lg"></i>
                                </button>
                                <input type="number" 
                                       class="form-control text-center" 
                                       id="quantity" 
                                       value="1" 
                                       min="1">
                                <button class="btn btn-outline-secondary" type="button" id="btnPlus">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Selected Product Info -->
                    <div id="selectedProduct" class="alert alert-info d-none">
                        <div class="row align-items-center">
                            <div class="col">
                                <strong id="selectedName">-</strong>
                                <br>
                                <small class="text-muted">Kode: <span id="selectedCode">-</span> | Stok: <span id="selectedStock">-</span></small>
                            </div>
                            <div class="col-auto">
                                <span class="h4 mb-0 text-success" id="selectedPrice">Rp 0</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Add Button -->
                    <button type="button" class="btn btn-success btn-lg w-100 mb-4" id="btnAddToCart">
                        <i class="bi bi-plus-circle me-2"></i>Tambah ke Keranjang
                    </button>
                    
                    <!-- Cart Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered" id="cartTable">
                            <thead class="table-success">
                                <tr>
                                    <th width="40">No</th>
                                    <th>Produk</th>
                                    <th width="100" class="text-center">Harga</th>
                                    <th width="80" class="text-center">Qty</th>
                                    <th width="120" class="text-end">Subtotal</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody id="cartBody">
                                <tr id="emptyCart">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-cart display-4"></i>
                                        <p class="mb-0 mt-2">Keranjang kosong</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Payment -->
        <div class="col-lg-5 mb-3">
            <div class="card pos-card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Pembayaran</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <!-- Total Summary -->
                    <div class="total-summary mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Item:</span>
                            <span id="totalItems">0</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0">TOTAL:</span>
                            <span class="h3 mb-0 text-success fw-bold" id="grandTotal">Rp 0</span>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Metode Pembayaran</label>
                        <div class="d-flex flex-wrap gap-2">
                            <input type="radio" class="btn-check" name="paymentMethod" id="tunai" value="tunai" checked>
                            <label class="btn btn-outline-success btn-lg flex-fill" for="tunai">
                                <i class="bi bi-cash me-2"></i>Tunai
                            </label>
                            
                            <?php if ($paymentSettings['qris_aktif']): ?>
                            <input type="radio" class="btn-check" name="paymentMethod" id="qris" value="qris">
                            <label class="btn btn-outline-primary btn-lg flex-fill" for="qris">
                                <i class="bi bi-qr-code me-2"></i>QRIS
                            </label>
                            <?php endif; ?>
                            
                            <?php if ($paymentSettings['transfer_aktif']): ?>
                            <input type="radio" class="btn-check" name="paymentMethod" id="transfer" value="transfer">
                            <label class="btn btn-outline-info btn-lg flex-fill" for="transfer">
                                <i class="bi bi-bank me-2"></i>Transfer
                            </label>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- QRIS Section (accordion) -->
                    <?php if ($paymentSettings['qris_aktif']): ?>
                    <div id="qrisSection" class="payment-section d-none mb-3">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-qr-code me-2"></i><?= htmlspecialchars($paymentSettings['qris_nama']) ?>
                            </div>
                            <div class="card-body text-center">
                                <?php if (!empty($paymentSettings['qris_image'])): ?>
                                <img src="<?= htmlspecialchars($paymentSettings['qris_image']) ?>" 
                                     alt="QRIS" 
                                     class="img-fluid rounded mb-2" 
                                     style="max-width: 250px;">
                                <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Gambar QRIS belum diupload
                                </div>
                                <?php endif; ?>
                                <p class="text-muted mb-0 mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Scan QRIS di atas untuk pembayaran
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Transfer Section (accordion) -->
                    <?php if ($paymentSettings['transfer_aktif']): ?>
                    <div id="transferSection" class="payment-section d-none mb-3">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <i class="bi bi-bank me-2"></i>Transfer Bank
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted" width="100">Bank</td>
                                        <td><strong><?= htmlspecialchars($paymentSettings['transfer_bank']) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">No. Rekening</td>
                                        <td>
                                            <strong class="fs-5 font-monospace"><?= htmlspecialchars($paymentSettings['transfer_rekening']) ?></strong>
                                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?= htmlspecialchars($paymentSettings['transfer_rekening']) ?>')">
                                                <i class="bi bi-copy"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Atas Nama</td>
                                        <td><strong><?= htmlspecialchars($paymentSettings['transfer_atas_nama']) ?></strong></td>
                                    </tr>
                                </table>
                                <p class="text-muted mb-0 mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Pastikan transfer sesuai nominal
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Cash Input (only for tunai) -->
                    <div id="cashSection" class="mb-3">
                        <label class="form-label fw-bold">Uang Diterima</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="cashReceived" placeholder="0">
                        </div>
                        
                        <!-- Quick Cash Buttons -->
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-cash" data-amount="exact">Uang Pas</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-cash" data-amount="5000">5.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-cash" data-amount="10000">10.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-cash" data-amount="20000">20.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-cash" data-amount="50000">50.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-cash" data-amount="100000">100.000</button>
                        </div>
                    </div>
                    
                    <!-- Change -->
                    <div class="change-display mb-4" id="changeSection">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Kembalian:</span>
                            <span class="h4 mb-0" id="changeAmount">Rp 0</span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-auto">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success btn-lg" id="btnSaveTransaction" disabled>
                                <i class="bi bi-check-circle me-2"></i>Simpan Transaksi
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="btnClearCart">
                                <i class="bi bi-trash me-2"></i>Kosongkan Keranjang
                            </button>
                        </div>
                        
                        <!-- Print Receipt (Future Development) -->
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-printer me-1"></i>Fitur cetak struk akan tersedia di versi mendatang
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="bi bi-check-circle-fill text-success display-1"></i>
                <h3 class="mt-3">Transaksi Berhasil!</h3>
                <p class="text-muted">No. Transaksi: <strong id="transactionNumber">-</strong></p>
                <div class="d-flex justify-content-center gap-2 mt-4">
                    <button type="button" class="btn btn-success btn-lg" data-bs-dismiss="modal" id="btnNewTransaction">
                        <i class="bi bi-plus-circle me-2"></i>Transaksi Baru
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Data -->
<script>
    const products = <?= json_encode($produk) ?>;
    const csrfToken = '<?= $csrfToken ?>';
    
    // Copy to clipboard function
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Nomor rekening disalin!', 'success');
        }).catch(() => {
            // Fallback for older browsers
            const el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            showToast('Nomor rekening disalin!', 'success');
        });
    }
    
    // Simple toast notification
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : 'info'} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 200px;';
        toast.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>${message}`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    }
</script>

<?php
$extraScripts = '<script src="assets/js/pos.js"></script>';
include 'includes/footer.php';
?>
