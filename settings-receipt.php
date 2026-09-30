<?php
/**
 * POS Koperasi Al-Farmasi
 * Pengaturan Struk Thermal (Admin Only)
 */

require_once 'includes/auth.php';
requireAdmin();

$pageTitle = 'Pengaturan Struk';
$message = '';
$messageType = '';

// Simpan pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_receipt') {
        $header = trim($_POST['receipt_header'] ?? '');
        $address = trim($_POST['receipt_address'] ?? '');
        $phone = trim($_POST['receipt_phone'] ?? '');
        $footer = trim($_POST['receipt_footer'] ?? '');
        $paperWidth = intval($_POST['receipt_paper_width'] ?? 58);
        $copies = intval($_POST['receipt_copies'] ?? 1);
        $autoPrint = isset($_POST['receipt_auto_print']) ? 1 : 0;
        $showLogo = isset($_POST['receipt_show_logo']) ? 1 : 0;

        if (!in_array($paperWidth, [58, 80])) {
            $paperWidth = 58;
        }
        if ($copies < 1) {
            $copies = 1;
        }
        if ($copies > 5) {
            $copies = 5;
        }

        saveSetting('receipt_header', $header);
        saveSetting('receipt_address', $address);
        saveSetting('receipt_phone', $phone);
        saveSetting('receipt_footer', $footer);
        saveSetting('receipt_paper_width', $paperWidth);
        saveSetting('receipt_copies', $copies);
        saveSetting('receipt_auto_print', $autoPrint);
        saveSetting('receipt_show_logo', $showLogo);

        $message = 'Pengaturan struk berhasil disimpan!';
        $messageType = 'success';
    }
}

$appName = getSetting('app_name', APP_NAME);
$logo = getSetting('app_logo', '');

$header = getSetting('receipt_header', $appName);
$address = getSetting('receipt_address', '');
$phone = getSetting('receipt_phone', '');
$footer = getSetting('receipt_footer', 'Terima kasih telah berbelanja');
$paperWidth = (int)getSetting('receipt_paper_width', 58);
$copies = (int)getSetting('receipt_copies', 1);
$autoPrint = (int)getSetting('receipt_auto_print', 1);
$showLogo = (int)getSetting('receipt_show_logo', 1);

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-printer me-2"></i>Pengaturan Struk Thermal</h2>
        <a href="pos.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Kasir
        </a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Printer thermal (mis. CX58D) cukup diinstall di Windows dan di-set sebagai <strong>printer default</strong>.
        Cetak struk memakai dialog print browser, jadi pilih printer thermal di dialog tersebut.
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Template Struk</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="save_receipt">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Header Toko</label>
                            <input type="text" class="form-control" name="receipt_header"
                                   value="<?= escape($header) ?>" placeholder="Contoh: Koperasi Al-Farmasi">
                            <small class="text-muted">Nama toko/koperasi yang tampil paling atas.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Alamat</label>
                            <input type="text" class="form-control" name="receipt_address"
                                   value="<?= escape($address) ?>" placeholder="Contoh: Jl. Merdeka No. 1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">No. Telepon / HP</label>
                            <input type="text" class="form-control" name="receipt_phone"
                                   value="<?= escape($phone) ?>" placeholder="Contoh: 0812-3456-7890">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Footer / Ucapan</label>
                            <textarea class="form-control" name="receipt_footer" rows="2"
                                      placeholder="Contoh: Terima kasih telah berbelanja"><?= escape($footer) ?></textarea>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Lebar Kertas</label>
                                <select class="form-select" name="receipt_paper_width">
                                    <option value="58" <?= $paperWidth === 58 ? 'selected' : '' ?>>58 mm</option>
                                    <option value="80" <?= $paperWidth === 80 ? 'selected' : '' ?>>80 mm</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Jumlah Copy</label>
                                <input type="number" class="form-control" name="receipt_copies"
                                       value="<?= $copies ?>" min="1" max="5">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Aksi</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="receipt_auto_print"
                                           id="receipt_auto_print" value="1" <?= $autoPrint ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="receipt_auto_print">Auto print</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="receipt_show_logo"
                                           id="receipt_show_logo" value="1" <?= $showLogo ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="receipt_show_logo">Tampilkan logo</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle me-2"></i>Simpan Pengaturan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Preview Struk</h5>
                </div>
                <div class="card-body text-center" style="background:#f1f1f1;">
                    <div style="display:inline-block; background:#fff; padding:8px 6px; width:<?= $paperWidth === 80 ? '80mm' : '58mm' ?>; font-family:'Courier New',monospace; font-size:10px; color:#000; text-align:center;">
                        <?php if ($showLogo && $logo && file_exists($logo)): ?>
                        <img src="<?= escape($logo) ?>" alt="Logo" style="max-width:70%; max-height:50px;">
                        <?php endif; ?>
                        <div style="font-weight:bold;"><?= escape($header ?: $appName) ?></div>
                        <?php if ($address): ?><div><?= escape($address) ?></div><?php endif; ?>
                        <?php if ($phone): ?><div>Telp: <?= escape($phone) ?></div><?php endif; ?>
                        <div><?= str_repeat('-', $paperWidth === 80 ? 42 : 32) ?></div>
                        <div style="text-align:left;">
                            No&nbsp;&nbsp;&nbsp;: TRX202609300001<br>
                            Tgl&nbsp;&nbsp;: <?= date('d/m/Y H:i') ?><br>
                            Kasir: <?= escape($_SESSION['nama_lengkap']) ?><br>
                            Bayar: TUNAI
                        </div>
                        <div><?= str_repeat('-', $paperWidth === 80 ? 42 : 32) ?></div>
                        <div style="text-align:left;">
                            Mie Instan Goreng<br>
                            &nbsp;&nbsp;2 x 3.500&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;7.000<br>
                            Teh Botol Sosro<br>
                            &nbsp;&nbsp;1 x 5.000&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;5.000
                        </div>
                        <div><?= str_repeat('-', $paperWidth === 80 ? 42 : 32) ?></div>
                        <div style="text-align:left; font-weight:bold;">
                            TOTAL&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;12.000<br>
                            Tunai&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;20.000<br>
                            Kembali&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;8.000
                        </div>
                        <div><?= str_repeat('-', $paperWidth === 80 ? 42 : 32) ?></div>
                        <div><?= nl2br(escape($footer)) ?></div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6><i class="bi bi-lightbulb me-2"></i>Tips Print</h6>
                    <ul class="small text-muted mb-0">
                        <li>Install driver printer CX58D di Windows.</li>
                        <li>Set printer thermal sebagai <strong>default printer</strong> di Windows.</li>
                        <li>Di dialog print browser, pilih printer thermal dan ukuran kertas 58mm.</li>
                        <li>Matikan header/footer browser agar struk bersih.</li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3 border-warning">
                <div class="card-body">
                    <h6><i class="bi bi-magic me-2"></i>Print Tanpa Dialog (Silent Print)</h6>
                    <p class="small text-muted mb-2">
                        Browser web standar tidak boleh langsung mencetak tanpa dialog demi keamanan. Agar dialog print tidak muncul, gunakan salah satu cara:
                    </p>
                    <ul class="small text-muted mb-0">
                        <li>
                            <strong>Chrome Kiosk Printing (paling mudah):</strong> buat shortcut Chrome dengan target<br>
                            <code>"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk-printing --app="http://localhost/POS-Koperasi-Sederhana/pos.php"</code><br>
                            Saat ini <code>window.print()</code> akan langsung cetak ke default printer tanpa dialog.
                        </li>
                        <li class="mt-2">
                            <strong>QZ Tray / local print agent:</strong> install QZ Tray di PC kasir. Aplikasi dapat mengirim perintah ESC/POS mentah ke printer secara silent. (Perlu pengembangan integrasi QZ Tray.)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
