<?php
/**
 * POS Koperasi Al-Farmasi
 * Manajemen Kategori (Admin Only)
 */

require_once 'includes/auth.php';
requireAdmin();

$pageTitle = 'Manajemen Kategori';
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama = trim($_POST['nama_kategori']);
        
        if (empty($nama)) {
            $message = 'Nama kategori tidak boleh kosong!';
            $messageType = 'danger';
        } else {
            query("INSERT INTO kategori (nama_kategori) VALUES (?)", [$nama]);
            $message = 'Kategori berhasil ditambahkan!';
            $messageType = 'success';
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $nama = trim($_POST['nama_kategori']);
        
        if (empty($nama)) {
            $message = 'Nama kategori tidak boleh kosong!';
            $messageType = 'danger';
        } else {
            query("UPDATE kategori SET nama_kategori = ? WHERE id = ?", [$nama, $id]);
            $message = 'Kategori berhasil diperbarui!';
            $messageType = 'success';
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        
        // Check if category is used
        $count = fetchOne("SELECT COUNT(*) as total FROM produk WHERE kategori_id = ?", [$id])['total'];
        if ($count > 0) {
            $message = "Kategori tidak bisa dihapus karena masih digunakan oleh $count produk!";
            $messageType = 'danger';
        } else {
            query("DELETE FROM kategori WHERE id = ?", [$id]);
            $message = 'Kategori berhasil dihapus!';
            $messageType = 'success';
        }
    }
}

// Get categories with product count
$kategori = fetchAll("SELECT k.*, COUNT(p.id) as jumlah_produk 
                     FROM kategori k 
                     LEFT JOIN produk p ON k.id = p.kategori_id AND p.status = 'aktif'
                     GROUP BY k.id 
                     ORDER BY k.nama_kategori");

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-tags me-2"></i>Manajemen Kategori</h2>
        <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle me-2"></i>Tambah Kategori
        </button>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <?php if (count($kategori) > 0): ?>
        <?php foreach ($kategori as $item): ?>
        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-tag-fill display-4 text-success mb-3"></i>
                    <h5 class="card-title"><?= escape($item['nama_kategori']) ?></h5>
                    <p class="text-muted mb-3"><?= $item['jumlah_produk'] ?> produk</p>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary btn-sm" 
                                onclick="editCategory(<?= $item['id'] ?>, '<?= escape($item['nama_kategori']) ?>')">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </button>
                        <?php if ($item['jumlah_produk'] == 0): ?>
                        <button class="btn btn-outline-danger btn-sm" 
                                onclick="deleteCategory(<?= $item['id'] ?>, '<?= escape($item['nama_kategori']) ?>')">
                            <i class="bi bi-trash me-1"></i>Hapus
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="bi bi-tags display-1"></i>
                <p class="mt-3">Belum ada kategori</p>
            </div>
        </div>
        <?php endif; ?>
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
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Kategori</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" name="nama_kategori" required 
                               placeholder="Contoh: Makanan, Minuman, dll">
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
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Kategori</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" name="nama_kategori" id="editNama" required>
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
function editCategory(id, nama) {
    document.getElementById('editId').value = id;
    document.getElementById('editNama').value = nama;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteCategory(id, nama) {
    if (confirm('Hapus kategori "' + nama + '"?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
