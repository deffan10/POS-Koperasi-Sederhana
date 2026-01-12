<?php
/**
 * POS Koperasi Al-Farmasi
 * Manajemen Pengguna (Admin Only)
 */

require_once 'includes/auth.php';
requireAdmin();

$pageTitle = 'Manajemen Pengguna';
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $nama = trim($_POST['nama_lengkap']);
        $role = $_POST['role'];
        
        // Cek username unik
        $existing = fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existing) {
            $message = 'Username sudah digunakan!';
            $messageType = 'danger';
        } elseif (strlen($password) < 6) {
            $message = 'Password minimal 6 karakter!';
            $messageType = 'danger';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            query("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)",
                [$username, $hashedPassword, $nama, $role]);
            $message = 'Pengguna berhasil ditambahkan!';
            $messageType = 'success';
        }
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $username = trim($_POST['username']);
        $nama = trim($_POST['nama_lengkap']);
        $role = $_POST['role'];
        $password = $_POST['password'];
        
        // Cek username unik (exclude current)
        $existing = fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id]);
        if ($existing) {
            $message = 'Username sudah digunakan!';
            $messageType = 'danger';
        } else {
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $message = 'Password minimal 6 karakter!';
                    $messageType = 'danger';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    query("UPDATE users SET username = ?, password = ?, nama_lengkap = ?, role = ? WHERE id = ?",
                        [$username, $hashedPassword, $nama, $role, $id]);
                    $message = 'Pengguna berhasil diperbarui!';
                    $messageType = 'success';
                }
            } else {
                query("UPDATE users SET username = ?, nama_lengkap = ?, role = ? WHERE id = ?",
                    [$username, $nama, $role, $id]);
                $message = 'Pengguna berhasil diperbarui!';
                $messageType = 'success';
            }
        }
    }
    
    if ($action === 'toggle_status') {
        $id = intval($_POST['id']);
        
        // Jangan nonaktifkan diri sendiri
        if ($id == $_SESSION['user_id']) {
            $message = 'Tidak bisa menonaktifkan akun sendiri!';
            $messageType = 'danger';
        } else {
            $user = fetchOne("SELECT status FROM users WHERE id = ?", [$id]);
            $newStatus = $user['status'] === 'aktif' ? 'nonaktif' : 'aktif';
            query("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $id]);
            $message = 'Status pengguna berhasil diubah!';
            $messageType = 'success';
        }
    }
}

// Get users
$users = fetchAll("SELECT * FROM users ORDER BY role, nama_lengkap");

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people me-2"></i>Manajemen Pengguna</h2>
        <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-person-plus me-2"></i>Tambah Pengguna
        </button>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th class="text-center">Role</th>
                            <th class="text-center">Status</th>
                            <th>Dibuat</th>
                            <th width="180" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $index => $user): ?>
                        <tr class="<?= $user['status'] === 'nonaktif' ? 'table-secondary' : '' ?>">
                            <td><?= $index + 1 ?></td>
                            <td><code><?= escape($user['username']) ?></code></td>
                            <td>
                                <?= escape($user['nama_lengkap']) ?>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                <span class="badge bg-info">Anda</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($user['role'] === 'admin'): ?>
                                <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                <span class="badge bg-primary">Kasir</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($user['status'] === 'aktif'): ?>
                                <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= date('d/m/Y', strtotime($user['created_at'])) ?></small></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $user['status'] === 'aktif' ? 'warning' : 'success' ?>"
                                            onclick="return confirm('<?= $user['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?> pengguna ini?')">
                                        <i class="bi bi-<?= $user['status'] === 'aktif' ? 'pause' : 'play' ?>"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Tambah Pengguna</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required 
                               pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" required>
                            <option value="kasir">Kasir</option>
                            <option value="admin">Admin</option>
                        </select>
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
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Pengguna</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" id="editUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_lengkap" id="editNama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" class="form-control" name="password" minlength="6">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" id="editRole" required>
                            <option value="kasir">Kasir</option>
                            <option value="admin">Admin</option>
                        </select>
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

<script>
function editUser(user) {
    document.getElementById('editId').value = user.id;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editNama').value = user.nama_lengkap;
    document.getElementById('editRole').value = user.role;
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
