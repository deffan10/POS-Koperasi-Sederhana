<?php
/**
 * POS Koperasi Al-Farmasi
 * Struk Thermal - Print View
 */

require_once 'includes/auth.php';
requireLogin();

$transaksiId = intval($_GET['id'] ?? 0);

if (!$transaksiId) {
    die('ID transaksi tidak valid');
}

$transaksi = fetchOne("SELECT t.*, u.nama_lengkap as kasir_nama 
                       FROM transaksi t 
                       JOIN users u ON t.user_id = u.id 
                       WHERE t.id = ?", 
                       [$transaksiId]);

if (!$transaksi) {
    die('Transaksi tidak ditemukan');
}

$items = fetchAll("SELECT * FROM detail_transaksi WHERE transaksi_id = ? ORDER BY id ASC", [$transaksiId]);

// Settings
$appName = getSetting('app_name', APP_NAME);
$logo = getSetting('app_logo', '');
$headerText = getSetting('receipt_header', $appName);
$address = getSetting('receipt_address', '');
$phone = getSetting('receipt_phone', '');
$footerText = getSetting('receipt_footer', 'Terima kasih telah berbelanja');
$paperWidth = (int)getSetting('receipt_paper_width', 58);
$copies = (int)getSetting('receipt_copies', 1);
$showLogo = (int)getSetting('receipt_show_logo', 1);
$bold = (int)getSetting('receipt_bold', 1);

if (!in_array($paperWidth, [55, 58, 80])) {
    $paperWidth = 58;
}
if ($copies < 1 || $copies > 5) {
    $copies = 1;
}

$widthMm = match($paperWidth) { 80 => '80mm', 58 => '58mm', default => '55mm' };
$lineWidth = match($paperWidth) { 80 => 42, 58 => 32, default => 30 };

function fmt($amount) {
    return number_format($amount, 0, ',', '.');
}

$pageTitle = 'Struk ' . escape($transaksi['no_transaksi']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <style>
        @page {
            size: <?= $widthMm ?> auto;
            margin: 0;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            font-family: "Courier New", Consolas, monospace;
            font-size: 11px;
            line-height: 1.25;
            color: #000;
        }
        .no-print {
            text-align: center;
            padding: 15px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .no-print button {
            padding: 8px 16px;
            margin: 0 5px 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
        }
        .no-print .btn-print { background: #5D4E6D; color: #fff; }
        .no-print .btn-close { background: #dc3545; color: #fff; }
        .page-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 10px;
        }
        .receipt {
            width: <?= $widthMm ?>;
            padding: 6px 4px;
            background: #fff;
            text-align: center;
            margin-bottom: 20px;
            page-break-after: always;
            page-break-inside: avoid;
            font-size: <?= $bold ? '12px' : '11px' ?>;
            <?= $bold ? 'font-weight: bold;' : '' ?>
        }
        .receipt:last-child {
            page-break-after: auto;
        }
        .logo {
            max-width: 70%;
            max-height: 60px;
            margin-bottom: 6px;
        }
        .store-name {
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .store-info {
            font-size: 9px;
            margin-bottom: 6px;
        }
        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .info {
            text-align: left;
            font-size: 10px;
            margin-bottom: 4px;
        }
        .info table { width: 100%; }
        .info td:first-child { width: 38%; vertical-align: top; }
        .items {
            text-align: left;
            font-size: 10px;
            margin: 6px 0;
        }
        .item-name {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            font-weight: bold;
        }
        .item-detail {
            display: flex;
            justify-content: space-between;
        }
        .totals {
            text-align: left;
            font-size: 10px;
            margin: 6px 0;
        }
        .total-row {
            font-weight: bold;
            font-size: 11px;
        }
        .footer-text {
            font-size: 9px;
            margin-top: 8px;
            white-space: pre-line;
        }
        .cut-feed {
            height: 20px;
        }
        @media print {
            html, body { background: #fff; }
            .no-print { display: none !important; }
            .page-wrapper { padding: 0; }
            .receipt {
                box-shadow: none;
                margin: 0;
                padding: 0 4px;
                width: <?= $widthMm ?>;
            }
            .receipt:last-child {
                margin-bottom: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">Cetak Struk</button>
        <button class="btn-close" onclick="window.close()">Tutup</button>
        <p class="text-muted mt-2 mb-0" style="font-size:11px;">
            Jika printer tidak muncul, pastikan printer thermal sudah menjadi default printer di Windows.
        </p>
    </div>

    <div class="page-wrapper">
        <?php for ($i = 0; $i < $copies; $i++): ?>
        <div class="receipt">
            <?php if ($showLogo && $logo && file_exists($logo)): ?>
                <img src="<?= escape($logo) ?>" class="logo" alt="Logo">
            <?php endif; ?>

            <div class="store-name"><?= escape($headerText ?: $appName) ?></div>
            <?php if ($address): ?>
                <div class="store-info"><?= nl2br(escape($address)) ?></div>
            <?php endif; ?>
            <?php if ($phone): ?>
                <div class="store-info">Telp: <?= escape($phone) ?></div>
            <?php endif; ?>

            <hr class="divider">

            <div class="info">
                <table>
                    <tr><td>No</td><td>: <?= escape($transaksi['no_transaksi']) ?></td></tr>
                    <tr><td>Tgl</td><td>: <?= date('d/m/Y H:i', strtotime($transaksi['tanggal_transaksi'])) ?></td></tr>
                    <tr><td>Kasir</td><td>: <?= escape($transaksi['kasir_nama']) ?></td></tr>
                    <tr><td>Bayar</td><td>: <?= strtoupper($transaksi['metode_pembayaran']) ?></td></tr>
                </table>
            </div>

            <hr class="divider">

            <div class="items">
                <?php foreach ($items as $item): ?>
                    <div class="item-name"><?= escape($item['nama_produk']) ?></div>
                    <div class="item-detail">
                        <span><?= $item['jumlah'] ?> x <?= fmt($item['harga_satuan']) ?></span>
                        <span><?= fmt($item['subtotal']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <hr class="divider">

            <div class="totals">
                <div class="item-detail">
                    <span>TOTAL</span>
                    <span><?= fmt($transaksi['total_harga']) ?></span>
                </div>
                <?php if ($transaksi['metode_pembayaran'] === 'tunai'): ?>
                <div class="item-detail">
                    <span>Tunai</span>
                    <span><?= fmt($transaksi['uang_diterima']) ?></span>
                </div>
                <div class="item-detail">
                    <span>Kembali</span>
                    <span><?= fmt($transaksi['kembalian']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <hr class="divider">

            <div class="footer-text"><?= nl2br(escape($footerText)) ?></div>

            <?php if ($copies > 1): ?>
                <div style="font-size:8px; margin-top:4px;">Copy <?= $i + 1 ?> / <?= $copies ?></div>
            <?php endif; ?>

            <div class="cut-feed"></div>
        </div>
        <?php endfor; ?>
    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
