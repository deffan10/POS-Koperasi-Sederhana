<?php
/**
 * POS Koperasi Al-Farmasi
 * Manajemen Produk (Admin Only)
 */

require_once 'includes/auth.php';
requireAdmin();

$pageTitle = 'Manajemen Produk';
$message = '';
$messageType = '';

// Ambil kategori
$kategori = fetchAll("SELECT * FROM kategori ORDER BY nama_kategori");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $kode = trim($_POST['kode_produk']);
        $nama = trim($_POST['nama_produk']);
        $kategori_id = $_POST['kategori_id'] ?: null;
        $harga_modal = floatval($_POST['harga_modal']);
        $harga = floatval($_POST['harga_jual']);
        $stok = intval($_POST['stok']);
        
        // Cek kode unik
        $existing = fetchOne("SELECT id FROM produk WHERE kode_produk = ?", [$kode]);
        if ($existing) {
            $message = 'Kode produk sudah digunakan!';
            $messageType = 'danger';
        } else {
            query("INSERT INTO produk (kode_produk, nama_produk, kategori_id, harga_modal, harga_jual, stok) VALUES (?, ?, ?, ?, ?, ?)",
                [$kode, $nama, $kategori_id, $harga_modal, $harga, $stok]);
            
            // Catat riwayat stok
            $produkId = lastInsertId();
            query("INSERT INTO riwayat_stok (produk_id, jenis_perubahan, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id) 
                   VALUES (?, 'masuk', ?, 0, ?, 'Stok awal', ?)", 
                [$produkId, $stok, $stok, $_SESSION['user_id']]);
            
            $message = 'Produk berhasil ditambahkan!';
            $messageType = 'success';
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $kode = trim($_POST['kode_produk']);
        $nama = trim($_POST['nama_produk']);
        $kategori_id = $_POST['kategori_id'] ?: null;
        $harga_modal = floatval($_POST['harga_modal']);
        $harga = floatval($_POST['harga_jual']);
        
        // Cek kode unik (exclude current)
        $existing = fetchOne("SELECT id FROM produk WHERE kode_produk = ? AND id != ?", [$kode, $id]);
        if ($existing) {
            $message = 'Kode produk sudah digunakan!';
            $messageType = 'danger';
        } else {
            query("UPDATE produk SET kode_produk = ?, nama_produk = ?, kategori_id = ?, harga_modal = ?, harga_jual = ? WHERE id = ?",
                [$kode, $nama, $kategori_id, $harga_modal, $harga, $id]);
            $message = 'Produk berhasil diperbarui!';
            $messageType = 'success';
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        // Soft delete - set status nonaktif
        query("UPDATE produk SET status = 'nonaktif' WHERE id = ?", [$id]);
        $message = 'Produk berhasil dihapus!';
        $messageType = 'success';
    }
    
    if ($action === 'toggle_status') {
        $id = intval($_POST['id']);
        $status = $_POST['status'] === 'aktif' ? 'nonaktif' : 'aktif';
        query("UPDATE produk SET status = ? WHERE id = ?", [$status, $id]);
        $message = 'Status produk berhasil diubah!';
        $messageType = 'success';
    }
}

// Filter
$filterKategori = $_GET['kategori'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? 'aktif';

$where = ["1=1"];
$params = [];

if ($filterKategori) {
    $where[] = "p.kategori_id = ?";
    $params[] = $filterKategori;
}

if ($filterSearch) {
    $where[] = "(p.kode_produk LIKE ? OR p.nama_produk LIKE ?)";
    $params[] = "%$filterSearch%";
    $params[] = "%$filterSearch%";
}

if ($filterStatus) {
    $where[] = "p.status = ?";
    $params[] = $filterStatus;
}

$whereClause = implode(' AND ', $where);
$produk = fetchAll("SELECT p.*, k.nama_kategori 
                   FROM produk p 
                   LEFT JOIN kategori k ON p.kategori_id = k.id 
                   WHERE $whereClause 
                   ORDER BY p.nama_produk", $params);

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-box-seam me-2"></i>Manajemen Produk</h2>
        <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle me-2"></i>Tambah Produk
        </button>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Cari</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?= escape($filterSearch) ?>" placeholder="Kode / Nama produk">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kategori</label>
                    <select class="form-select" name="kategori">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori as $kat): ?>
                        <option value="<?= $kat['id'] ?>" <?= $filterKategori == $kat['id'] ? 'selected' : '' ?>>
                            <?= escape($kat['nama_kategori']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">Semua Status</option>
                        <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                    <a href="products.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Product List -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th width="120">Kode</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th class="text-end">Modal</th>
                            <th class="text-end">Harga Jual</th>
                            <th class="text-center">Margin</th>
                            <th class="text-center">Stok</th>
                            <th class="text-center">Status</th>
                            <th width="150" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($produk) > 0): ?>
                        <?php foreach ($produk as $index => $item): 
                            $margin = $item['harga_modal'] > 0 ? (($item['harga_jual'] - $item['harga_modal']) / $item['harga_modal'] * 100) : 0;
                        ?>
                        <tr class="<?= $item['status'] === 'nonaktif' ? 'table-secondary' : '' ?>">
                            <td><?= $index + 1 ?></td>
                            <td><code><?= escape($item['kode_produk']) ?></code></td>
                            <td><?= escape($item['nama_produk']) ?></td>
                            <td><?= escape($item['nama_kategori'] ?? '-') ?></td>
                            <td class="text-end text-muted"><?= formatRupiah($item['harga_modal']) ?></td>
                            <td class="text-end"><?= formatRupiah($item['harga_jual']) ?></td>
                            <td class="text-center">
                                <?php if ($margin > 0): ?>
                                <span class="badge bg-success"><?= number_format($margin, 1) ?>%</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($item['stok'] < 10): ?>
                                <span class="badge bg-danger"><?= $item['stok'] ?></span>
                                <?php else: ?>
                                <span class="badge bg-success"><?= $item['stok'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($item['status'] === 'aktif'): ?>
                                <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editProduct(<?= htmlspecialchars(json_encode($item)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteProduct(<?= $item['id'] ?>, '<?= escape($item['nama_produk']) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox display-4"></i>
                                <p class="mb-0 mt-2">Tidak ada produk ditemukan</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <small class="text-muted">Total: <?= count($produk) ?> produk</small>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Produk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Produk <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="kode_produk" required 
                               placeholder="Contoh: MKN001">
                        <small class="text-muted">Kode bersifat unik (angka, huruf, atau kombinasi)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_produk" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori_id">
                            <option value="">- Pilih Kategori -</option>
                            <?php foreach ($kategori as $kat): ?>
                            <option value="<?= $kat['id'] ?>"><?= escape($kat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga Modal <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="harga_modal" required min="0" id="addHargaModal">
                            </div>
                            <small class="text-muted">Harga beli/kulakan</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="harga_jual" required min="0" id="addHargaJual">
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info py-2 mb-3" id="addMarginInfo" style="display:none;">
                        <small><i class="bi bi-info-circle me-1"></i>Margin: <strong id="addMarginValue">0%</strong> | Laba/item: <strong id="addLabaValue">Rp 0</strong></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Awal</label>
                        <input type="number" class="form-control" name="stok" value="0" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Produk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Produk <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="kode_produk" id="editKode" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_produk" id="editNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori_id" id="editKategori">
                            <option value="">- Pilih Kategori -</option>
                            <?php foreach ($kategori as $kat): ?>
                            <option value="<?= $kat['id'] ?>"><?= escape($kat['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga Modal <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="harga_modal" id="editHargaModal" required min="0">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="harga_jual" id="editHarga" required min="0">
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info py-2 mb-3" id="editMarginInfo" style="display:none;">
                        <small><i class="bi bi-info-circle me-1"></i>Margin: <strong id="editMarginValue">0%</strong> | Laba/item: <strong id="editLabaValue">Rp 0</strong></small>
                    </div>
                    <div class="alert alert-secondary">
                        <i class="bi bi-info-circle me-1"></i>
                        Untuk mengubah stok, gunakan menu <a href="stock.php">Manajemen Stok</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form (Hidden) -->
<form id="deleteForm" method="POST" style="display: none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
// Format rupiah
function formatRupiahJS(angka) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
}

// Calculate margin
function calculateMargin(modal, jual, marginEl, labaEl, infoEl) {
    modal = parseFloat(modal) || 0;
    jual = parseFloat(jual) || 0;
    
    if (modal > 0 && jual > 0) {
        const laba = jual - modal;
        const margin = (laba / modal * 100).toFixed(1);
        
        marginEl.textContent = margin + '%';
        labaEl.textContent = formatRupiahJS(laba);
        infoEl.style.display = 'block';
        
        // Color coding
        if (laba < 0) {
            infoEl.className = 'alert alert-danger py-2 mb-3';
        } else if (margin < 10) {
            infoEl.className = 'alert alert-warning py-2 mb-3';
        } else {
            infoEl.className = 'alert alert-success py-2 mb-3';
        }
    } else {
        infoEl.style.display = 'none';
    }
}

// Add modal margin calculator
document.getElementById('addHargaModal').addEventListener('input', function() {
    calculateMargin(
        this.value,
        document.getElementById('addHargaJual').value,
        document.getElementById('addMarginValue'),
        document.getElementById('addLabaValue'),
        document.getElementById('addMarginInfo')
    );
});

document.getElementById('addHargaJual').addEventListener('input', function() {
    calculateMargin(
        document.getElementById('addHargaModal').value,
        this.value,
        document.getElementById('addMarginValue'),
        document.getElementById('addLabaValue'),
        document.getElementById('addMarginInfo')
    );
});

// Edit modal margin calculator
document.getElementById('editHargaModal').addEventListener('input', function() {
    calculateMargin(
        this.value,
        document.getElementById('editHarga').value,
        document.getElementById('editMarginValue'),
        document.getElementById('editLabaValue'),
        document.getElementById('editMarginInfo')
    );
});

document.getElementById('editHarga').addEventListener('input', function() {
    calculateMargin(
        document.getElementById('editHargaModal').value,
        this.value,
        document.getElementById('editMarginValue'),
        document.getElementById('editLabaValue'),
        document.getElementById('editMarginInfo')
    );
});

function editProduct(product) {
    document.getElementById('editId').value = product.id;
    document.getElementById('editKode').value = product.kode_produk;
    document.getElementById('editNama').value = product.nama_produk;
    document.getElementById('editKategori').value = product.kategori_id || '';
    document.getElementById('editHargaModal').value = product.harga_modal;
    document.getElementById('editHarga').value = product.harga_jual;
    
    // Calculate margin on load
    calculateMargin(
        product.harga_modal,
        product.harga_jual,
        document.getElementById('editMarginValue'),
        document.getElementById('editLabaValue'),
        document.getElementById('editMarginInfo')
    );
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteProduct(id, nama) {
    if (confirm('Hapus produk "' + nama + '"?\n\nProduk akan dinonaktifkan dan tidak bisa digunakan untuk transaksi baru.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
