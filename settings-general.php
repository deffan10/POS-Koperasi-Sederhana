<?php
/**
 * POS Koperasi Al-Farmasi
 * Pengaturan Umum (Logo, Favicon, Nama Toko)
 */

require_once 'includes/auth.php';
requireAdmin();

$pageTitle = 'Pengaturan Umum';
$message = '';
$messageType = '';

// Get current settings
$namaApp = getSetting('app_name', APP_NAME);
$logo = getSetting('app_logo', '');
$favicon = getSetting('app_favicon', '');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_general') {
        $namaApp = trim($_POST['app_name'] ?? '');
        
        if (empty($namaApp)) {
            $message = 'Nama aplikasi tidak boleh kosong!';
            $messageType = 'danger';
        } else {
            saveSetting('app_name', $namaApp);
            
            // Handle Logo upload
            if (isset($_FILES['app_logo']) && $_FILES['app_logo']['error'] === UPLOAD_ERR_OK) {
                $result = uploadImage($_FILES['app_logo'], 'logo');
                if ($result['success']) {
                    // Delete old logo
                    $oldLogo = getSetting('app_logo');
                    if ($oldLogo && file_exists($oldLogo)) {
                        unlink($oldLogo);
                    }
                    saveSetting('app_logo', $result['path']);
                    $logo = $result['path'];
                } else {
                    $message = $result['message'];
                    $messageType = 'danger';
                }
            }
            
            // Handle Favicon upload
            if (isset($_FILES['app_favicon']) && $_FILES['app_favicon']['error'] === UPLOAD_ERR_OK) {
                $result = uploadImage($_FILES['app_favicon'], 'favicon', ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon']);
                if ($result['success']) {
                    // Delete old favicon
                    $oldFavicon = getSetting('app_favicon');
                    if ($oldFavicon && file_exists($oldFavicon)) {
                        unlink($oldFavicon);
                    }
                    saveSetting('app_favicon', $result['path']);
                    $favicon = $result['path'];
                } else {
                    $message = $result['message'];
                    $messageType = 'danger';
                }
            }
            
            if (empty($message)) {
                $message = 'Pengaturan berhasil disimpan!';
                $messageType = 'success';
            }
        }
    }
    
    if ($action === 'delete_logo') {
        $oldLogo = getSetting('app_logo');
        if ($oldLogo && file_exists($oldLogo)) {
            unlink($oldLogo);
        }
        saveSetting('app_logo', '');
        $logo = '';
        $message = 'Logo berhasil dihapus!';
        $messageType = 'success';
    }
    
    if ($action === 'delete_favicon') {
        $oldFavicon = getSetting('app_favicon');
        if ($oldFavicon && file_exists($oldFavicon)) {
            unlink($oldFavicon);
        }
        saveSetting('app_favicon', '');
        $favicon = '';
        $message = 'Favicon berhasil dihapus!';
        $messageType = 'success';
    }
}

/**
 * Helper function untuk upload image
 */
function uploadImage($file, $prefix, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']) {
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Format file tidak valid.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 2MB.'];
    }
    
    $uploadDir = 'assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Gagal mengupload file.'];
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-shop me-2"></i>Pengaturan Umum</h2>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Identitas Aplikasi</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="save_general">
                        
                        <!-- Nama Aplikasi -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Nama Aplikasi / Toko <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" name="app_name" 
                                   value="<?= escape($namaApp) ?>" required>
                            <small class="text-muted">Nama ini akan ditampilkan di navbar dan halaman login</small>
                        </div>
                        
                        <hr>
                        
                        <!-- Logo -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Logo Aplikasi</label>
                                <?php if ($logo && file_exists($logo)): ?>
                                <div class="mb-2 p-3 bg-light rounded text-center">
                                    <img src="<?= $logo ?>" alt="Logo" class="img-fluid" style="max-height: 80px;">
                                    <br>
                                    <button type="submit" name="action" value="delete_logo" 
                                            class="btn btn-sm btn-outline-danger mt-2"
                                            onclick="return confirm('Hapus logo?')">
                                        <i class="bi bi-trash me-1"></i>Hapus
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="mb-2 p-3 bg-light rounded text-center text-muted">
                                    <i class="bi bi-image display-4"></i>
                                    <p class="mb-0">Belum ada logo</p>
                                </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="app_logo" accept="image/*">
                                <small class="text-muted">Format: JPG, PNG, GIF. Maks 2MB. Rekomendasi: 200x60px</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Favicon</label>
                                <?php if ($favicon && file_exists($favicon)): ?>
                                <div class="mb-2 p-3 bg-light rounded text-center">
                                    <img src="<?= $favicon ?>" alt="Favicon" style="width: 32px; height: 32px;">
                                    <br>
                                    <button type="submit" name="action" value="delete_favicon" 
                                            class="btn btn-sm btn-outline-danger mt-2"
                                            onclick="return confirm('Hapus favicon?')">
                                        <i class="bi bi-trash me-1"></i>Hapus
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="mb-2 p-3 bg-light rounded text-center text-muted">
                                    <i class="bi bi-app display-4"></i>
                                    <p class="mb-0">Belum ada favicon</p>
                                </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="app_favicon" accept=".ico,.png">
                                <small class="text-muted">Format: ICO, PNG. Maks 2MB. Rekomendasi: 32x32px</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle me-2"></i>Simpan Pengaturan
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Preview -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Preview</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Tampilan navbar dengan logo/nama:</p>
                    
                    <!-- Preview Navbar -->
                    <div class="bg-success text-white p-2 rounded mb-3">
                        <div class="d-flex align-items-center">
                            <?php if ($logo && file_exists($logo)): ?>
                            <img src="<?= $logo ?>" alt="Logo" style="max-height: 30px;" class="me-2">
                            <?php else: ?>
                            <i class="bi bi-shop me-2"></i>
                            <?php endif; ?>
                            <span class="fw-bold"><?= escape($namaApp) ?></span>
                        </div>
                    </div>
                    
                    <p class="text-muted small mb-3">Tampilan favicon di browser tab:</p>
                    
                    <!-- Preview Browser Tab -->
                    <div class="border rounded p-2 bg-light">
                        <div class="d-flex align-items-center">
                            <?php if ($favicon && file_exists($favicon)): ?>
                            <img src="<?= $favicon ?>" alt="Favicon" style="width: 16px; height: 16px;" class="me-2">
                            <?php else: ?>
                            <i class="bi bi-globe me-2" style="font-size: 16px;"></i>
                            <?php endif; ?>
                            <span class="small text-truncate"><?= escape($namaApp) ?></span>
                            <span class="ms-auto">×</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Info -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6><i class="bi bi-info-circle me-2"></i>Informasi</h6>
                    <ul class="small text-muted mb-0">
                        <li>Logo akan ditampilkan di navbar</li>
                        <li>Favicon adalah ikon kecil di tab browser</li>
                        <li>Perubahan nama membutuhkan refresh halaman</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
