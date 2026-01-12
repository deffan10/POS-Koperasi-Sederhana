<?php
/**
 * POS Koperasi Al-Farmasi
 * Pengaturan Pembayaran Non-Tunai (Admin Only)
 */

require_once 'includes/auth.php';
requireAdmin();

$pageTitle = 'Pengaturan Pembayaran';
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_qris') {
        $qrisAktif = isset($_POST['qris_aktif']) ? 1 : 0;
        $qrisNama = trim($_POST['qris_nama'] ?? '');
        
        // Handle file upload
        if (isset($_FILES['qris_image']) && $_FILES['qris_image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['qris_image']['type'], $allowedTypes)) {
                $message = 'Format gambar tidak valid. Gunakan JPG, PNG, atau GIF.';
                $messageType = 'danger';
            } elseif ($_FILES['qris_image']['size'] > $maxSize) {
                $message = 'Ukuran gambar maksimal 2MB.';
                $messageType = 'danger';
            } else {
                $uploadDir = 'assets/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['qris_image']['name'], PATHINFO_EXTENSION);
                $filename = 'qris_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['qris_image']['tmp_name'], $filepath)) {
                    // Delete old image
                    $oldImage = getSetting('qris_image');
                    if ($oldImage && file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                    saveSetting('qris_image', $filepath);
                }
            }
        }
        
        if (empty($message)) {
            saveSetting('qris_aktif', $qrisAktif);
            saveSetting('qris_nama', $qrisNama);
            $message = 'Pengaturan QRIS berhasil disimpan!';
            $messageType = 'success';
        }
    }
    
    if ($action === 'save_transfer') {
        $transferAktif = isset($_POST['transfer_aktif']) ? 1 : 0;
        $bankNama = trim($_POST['bank_nama'] ?? '');
        $bankRekening = trim($_POST['bank_rekening'] ?? '');
        $bankAtasNama = trim($_POST['bank_atas_nama'] ?? '');
        
        saveSetting('transfer_aktif', $transferAktif);
        saveSetting('bank_nama', $bankNama);
        saveSetting('bank_rekening', $bankRekening);
        saveSetting('bank_atas_nama', $bankAtasNama);
        
        $message = 'Pengaturan Transfer berhasil disimpan!';
        $messageType = 'success';
    }
    
    if ($action === 'delete_qris_image') {
        $oldImage = getSetting('qris_image');
        if ($oldImage && file_exists($oldImage)) {
            unlink($oldImage);
        }
        saveSetting('qris_image', '');
        $message = 'Gambar QRIS berhasil dihapus!';
        $messageType = 'success';
    }
}

// Get current settings
$qrisAktif = getSetting('qris_aktif', 0);
$qrisNama = getSetting('qris_nama', '');
$qrisImage = getSetting('qris_image', '');
$transferAktif = getSetting('transfer_aktif', 0);
$bankNama = getSetting('bank_nama', '');
$bankRekening = getSetting('bank_rekening', '');
$bankAtasNama = getSetting('bank_atas_nama', '');

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-credit-card me-2"></i>Pengaturan Pembayaran Non-Tunai</h2>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- QRIS Settings -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-qr-code me-2"></i>Pengaturan QRIS</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="save_qris">
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="qrisAktif" 
                                       name="qris_aktif" <?= $qrisAktif ? 'checked' : '' ?>>
                                <label class="form-check-label" for="qrisAktif">
                                    <strong>Aktifkan Pembayaran QRIS</strong>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama QRIS</label>
                            <input type="text" class="form-control" name="qris_nama" 
                                   value="<?= escape($qrisNama) ?>" placeholder="Contoh: QRIS Koperasi Al-Farmasi">
                            <small class="text-muted">Nama yang ditampilkan di kasir</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Gambar QRIS</label>
                            <?php if ($qrisImage && file_exists($qrisImage)): ?>
                            <div class="mb-2 text-center p-3 bg-light rounded">
                                <img src="<?= $qrisImage ?>" alt="QRIS" class="img-fluid" style="max-height: 200px;">
                                <br>
                                <button type="submit" name="action" value="delete_qris_image" 
                                        class="btn btn-sm btn-outline-danger mt-2"
                                        onclick="return confirm('Hapus gambar QRIS?')">
                                    <i class="bi bi-trash me-1"></i>Hapus Gambar
                                </button>
                            </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="qris_image" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle me-1"></i>Simpan Pengaturan QRIS
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Transfer Settings -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-bank me-2"></i>Pengaturan Transfer Bank</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="save_transfer">
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="transferAktif" 
                                       name="transfer_aktif" <?= $transferAktif ? 'checked' : '' ?>>
                                <label class="form-check-label" for="transferAktif">
                                    <strong>Aktifkan Pembayaran Transfer</strong>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Bank <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="bank_nama" 
                                   value="<?= escape($bankNama) ?>" placeholder="Contoh: BCA, BRI, Mandiri">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nomor Rekening <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="bank_rekening" 
                                   value="<?= escape($bankRekening) ?>" placeholder="Contoh: 1234567890">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Atas Nama <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="bank_atas_nama" 
                                   value="<?= escape($bankAtasNama) ?>" placeholder="Contoh: Koperasi Al-Farmasi">
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle me-1"></i>Simpan Pengaturan Transfer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Preview -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Preview Tampilan di Kasir</h5>
        </div>
        <div class="card-body">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="accordion" id="previewAccordion">
                        <?php if ($qrisAktif && $qrisImage): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#previewQris">
                                    <i class="bi bi-qr-code me-2"></i><?= escape($qrisNama ?: 'QRIS') ?>
                                </button>
                            </h2>
                            <div id="previewQris" class="accordion-collapse collapse show" data-bs-parent="#previewAccordion">
                                <div class="accordion-body text-center">
                                    <img src="<?= $qrisImage ?>" alt="QRIS" class="img-fluid" style="max-height: 250px;">
                                    <p class="mt-2 mb-0 text-muted">Scan QRIS untuk membayar</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($transferAktif && $bankRekening): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= !($qrisAktif && $qrisImage) ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#previewTransfer">
                                    <i class="bi bi-bank me-2"></i>Transfer Bank
                                </button>
                            </h2>
                            <div id="previewTransfer" class="accordion-collapse collapse <?= !($qrisAktif && $qrisImage) ? 'show' : '' ?>" data-bs-parent="#previewAccordion">
                                <div class="accordion-body">
                                    <div class="text-center">
                                        <h5 class="text-primary mb-3"><?= escape($bankNama) ?></h5>
                                        <div class="bg-light p-3 rounded mb-2">
                                            <h3 class="mb-0 font-monospace"><?= escape($bankRekening) ?></h3>
                                        </div>
                                        <p class="mb-0">a.n. <strong><?= escape($bankAtasNama) ?></strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!($qrisAktif && $qrisImage) && !($transferAktif && $bankRekening)): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Belum ada metode pembayaran non-tunai yang aktif. Aktifkan QRIS atau Transfer di atas.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
