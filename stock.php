<?php
/**
 * POS Koperasi Al-Farmasi
 * Manajemen Stok (Admin Only)
 */

require_once 'includes/auth.php';
requireAdmin();

$pageTitle = 'Manajemen Stok';
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_stock') {
        $produk_id = intval($_POST['produk_id']);
        $jumlah = intval($_POST['jumlah']);
        $keterangan = trim($_POST['keterangan']);
        
        if ($jumlah <= 0) {
            $message = 'Jumlah harus lebih dari 0!';
            $messageType = 'danger';
        } else {
            // Get current stock
            $produk = fetchOne("SELECT stok FROM produk WHERE id = ?", [$produk_id]);
            $stokSebelum = $produk['stok'];
            $stokSesudah = $stokSebelum + $jumlah;
            
            // Update stock
            query("UPDATE produk SET stok = stok + ? WHERE id = ?", [$jumlah, $produk_id]);
            
            // Record history
            query("INSERT INTO riwayat_stok (produk_id, jenis_perubahan, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id) 
                   VALUES (?, 'masuk', ?, ?, ?, ?, ?)",
                [$produk_id, $jumlah, $stokSebelum, $stokSesudah, $keterangan ?: 'Penambahan stok', $_SESSION['user_id']]);
            
            $message = 'Stok berhasil ditambahkan!';
            $messageType = 'success';
        }
    }
    
    if ($action === 'correction') {
        $produk_id = intval($_POST['produk_id']);
        $stok_baru = intval($_POST['stok_baru']);
        $keterangan = trim($_POST['keterangan']);
        
        if ($stok_baru < 0) {
            $message = 'Stok tidak boleh negatif!';
            $messageType = 'danger';
        } else {
            // Get current stock
            $produk = fetchOne("SELECT stok FROM produk WHERE id = ?", [$produk_id]);
            $stokSebelum = $produk['stok'];
            $selisih = $stok_baru - $stokSebelum;
            
            // Update stock
            query("UPDATE produk SET stok = ? WHERE id = ?", [$stok_baru, $produk_id]);
            
            // Record history
            query("INSERT INTO riwayat_stok (produk_id, jenis_perubahan, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id) 
                   VALUES (?, 'koreksi', ?, ?, ?, ?, ?)",
                [$produk_id, abs($selisih), $stokSebelum, $stok_baru, $keterangan ?: 'Koreksi stok', $_SESSION['user_id']]);
            
            $message = 'Stok berhasil dikoreksi!';
            $messageType = 'success';
        }
    }
}

// Get products for dropdown
$produkList = fetchAll("SELECT id, kode_produk, nama_produk, stok FROM produk WHERE status = 'aktif' ORDER BY nama_produk");

// Get stock history
$filterProduk = $_GET['produk'] ?? '';
$filterTanggal = $_GET['tanggal'] ?? '';

$where = ["1=1"];
$params = [];

if ($filterProduk) {
    $where[] = "r.produk_id = ?";
    $params[] = $filterProduk;
}

if ($filterTanggal) {
    $where[] = "DATE(r.created_at) = ?";
    $params[] = $filterTanggal;
}

$whereClause = implode(' AND ', $where);
$riwayat = fetchAll("SELECT r.*, p.kode_produk, p.nama_produk, u.nama_lengkap 
                    FROM riwayat_stok r 
                    JOIN produk p ON r.produk_id = p.id 
                    JOIN users u ON r.user_id = u.id 
                    WHERE $whereClause 
                    ORDER BY r.created_at DESC 
                    LIMIT 100", $params);

// Get low stock products
$stokMenipis = fetchAll("SELECT * FROM produk WHERE stok < 10 AND status = 'aktif' ORDER BY stok ASC LIMIT 10");

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-boxes me-2"></i>Manajemen Stok</h2>
        <div>
            <button class="btn btn-success btn-lg me-2" data-bs-toggle="modal" data-bs-target="#addStockModal">
                <i class="bi bi-plus-circle me-2"></i>Tambah Stok
            </button>
            <button class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#correctionModal">
                <i class="bi bi-pencil-square me-2"></i>Koreksi Stok
            </button>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Left Column - Stock Alert -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Stok Menipis</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($stokMenipis) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($stokMenipis as $item): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= escape($item['nama_produk']) ?></strong>
                                <br>
                                <small class="text-muted"><?= escape($item['kode_produk']) ?></small>
                            </div>
                            <span class="badge bg-danger rounded-pill fs-6"><?= $item['stok'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle display-4 text-success"></i>
                        <p class="mb-0 mt-2">Semua stok aman!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Stock History -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Perubahan Stok</h5>
                </div>
                <div class="card-body">
                    <!-- Filter -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-5">
                            <select class="form-select" name="produk">
                                <option value="">Semua Produk</option>
                                <?php foreach ($produkList as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $filterProduk == $p['id'] ? 'selected' : '' ?>>
                                    <?= escape($p['nama_produk']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="date" class="form-control" name="tanggal" value="<?= escape($filterTanggal) ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                        </div>
                    </form>
                    
                    <!-- History Table -->
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Waktu</th>
                                    <th>Produk</th>
                                    <th class="text-center">Jenis</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Stok</th>
                                    <th>Keterangan</th>
                                    <th>Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($riwayat) > 0): ?>
                                <?php foreach ($riwayat as $item): ?>
                                <tr>
                                    <td><small><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></small></td>
                                    <td>
                                        <small class="text-muted"><?= escape($item['kode_produk']) ?></small><br>
                                        <?= escape($item['nama_produk']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item['jenis_perubahan'] === 'masuk'): ?>
                                        <span class="badge bg-success">Masuk</span>
                                        <?php elseif ($item['jenis_perubahan'] === 'keluar'): ?>
                                        <span class="badge bg-danger">Keluar</span>
                                        <?php else: ?>
                                        <span class="badge bg-warning">Koreksi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item['jenis_perubahan'] === 'masuk'): ?>
                                        <span class="text-success">+<?= $item['jumlah'] ?></span>
                                        <?php elseif ($item['jenis_perubahan'] === 'keluar'): ?>
                                        <span class="text-danger">-<?= $item['jumlah'] ?></span>
                                        <?php else: ?>
                                        <span class="text-warning"><?= $item['jumlah'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted"><?= $item['stok_sebelum'] ?></small>
                                        <i class="bi bi-arrow-right mx-1"></i>
                                        <strong><?= $item['stok_sesudah'] ?></strong>
                                    </td>
                                    <td><small><?= escape($item['keterangan']) ?></small></td>
                                    <td><small><?= escape($item['nama_lengkap']) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Tidak ada riwayat ditemukan
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_stock">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Stok</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Produk <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" name="produk_id" required>
                            <option value="">- Pilih Produk -</option>
                            <?php foreach ($produkList as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                [<?= escape($p['kode_produk']) ?>] <?= escape($p['nama_produk']) ?> (Stok: <?= $p['stok'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Penambahan <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg" name="jumlah" required min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <input type="text" class="form-control" name="keterangan" placeholder="Contoh: Restock dari supplier">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Tambah Stok
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Correction Modal -->
<div class="modal fade" id="correctionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="correction">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Koreksi Stok</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        Gunakan fitur ini untuk menyesuaikan stok dengan kondisi fisik barang.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih Produk <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" name="produk_id" id="correctionProduct" required>
                            <option value="">- Pilih Produk -</option>
                            <?php foreach ($produkList as $p): ?>
                            <option value="<?= $p['id'] ?>" data-stok="<?= $p['stok'] ?>">
                                [<?= escape($p['kode_produk']) ?>] <?= escape($p['nama_produk']) ?> (Stok: <?= $p['stok'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Saat Ini</label>
                        <input type="text" class="form-control" id="currentStock" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Sebenarnya (Hasil Hitung Fisik) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg" name="stok_baru" required min="0" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan Koreksi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="keterangan" required 
                               placeholder="Contoh: Stok opname bulanan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Koreksi Stok
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('correctionProduct').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const stok = selected.getAttribute('data-stok') || '-';
    document.getElementById('currentStock').value = stok;
});
</script>

<?php include 'includes/footer.php'; ?>
