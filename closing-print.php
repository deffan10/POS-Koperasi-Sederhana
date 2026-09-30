<?php
/**
 * POS Koperasi Al-Farmasi
 * Laporan Tutup Buku - PDF A4 Print
 */

require_once 'includes/auth.php';
requireAdmin();

function getBulanIndo($bulan) {
    $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $namaBulan[$bulan] ?? '';
}

$mulai = $_GET['mulai'] ?? date('Y-m');
$akhir = $_GET['akhir'] ?? date('Y-m');

if (!preg_match('/^\d{4}-\d{2}$/', $mulai) || !preg_match('/^\d{4}-\d{2}$/', $akhir)) {
    die('Periode tidak valid');
}

if ($mulai > $akhir) {
    $temp = $mulai;
    $mulai = $akhir;
    $akhir = $temp;
}

$tanggalMulai = $mulai . '-01';
$tanggalAkhir = date('Y-m-t', strtotime($akhir . '-01'));

$rows = fetchAll("SELECT tb.*, u.nama_lengkap 
                  FROM tutup_buku tb 
                  JOIN users u ON tb.user_id = u.id 
                  WHERE CONCAT(tb.tahun, '-', LPAD(tb.bulan, 2, '0')) BETWEEN ? AND ?
                  ORDER BY tb.tahun ASC, tb.bulan ASC", 
                  [$mulai, $akhir]);

// Pecah metode pembayaran per periode dari data transaksi
$payments = fetchAll("SELECT 
                            YEAR(t.tanggal_transaksi) as tahun,
                            MONTH(t.tanggal_transaksi) as bulan,
                            COALESCE(SUM(CASE WHEN t.metode_pembayaran = 'tunai' THEN t.total_harga ELSE 0 END), 0) as tunai,
                            COALESCE(SUM(CASE WHEN t.metode_pembayaran = 'qris' THEN t.total_harga ELSE 0 END), 0) as qris,
                            COALESCE(SUM(CASE WHEN t.metode_pembayaran = 'transfer' THEN t.total_harga ELSE 0 END), 0) as transfer
                        FROM transaksi t
                        WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?
                        GROUP BY tahun, bulan", 
                        [$tanggalMulai, $tanggalAkhir]);

$paymentMap = [];
foreach ($payments as $p) {
    $key = sprintf('%04d-%02d', $p['tahun'], $p['bulan']);
    $paymentMap[$key] = $p;
}

$appName = getSetting('app_name', APP_NAME);
$appLogo = getSetting('app_logo', '');
$adminName = $_SESSION['nama_lengkap'] ?? 'Admin';

$pageTitle = 'Laporan Tutup Buku ' . $mulai . ' s/d ' . $akhir;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($pageTitle) ?></title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            padding: 8mm;
            color: #222;
            font-size: 9.5pt;
        }
        .no-print { margin-bottom: 15px; }
        .no-print button {
            padding: 8px 16px;
            margin-right: 8px;
            cursor: pointer;
            border: 1px solid #5D4E6D;
            background: #5D4E6D;
            color: #fff;
            border-radius: 4px;
            font-size: 10pt;
        }
        .no-print button:hover { opacity: 0.9; }
        .print-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #5D4E6D; padding-bottom: 12px; }
        .logo { max-height: 50px; margin-bottom: 8px; }
        .print-header h1 { font-size: 16pt; margin: 0; color: #5D4E6D; }
        .print-header h2 { font-size: 13pt; margin: 5px 0; font-weight: normal; }
        .print-header p { margin: 3px 0; font-size: 10pt; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #444; padding: 5px 4px; vertical-align: middle; }
        th { background: #f3f0f5; text-align: center; font-weight: bold; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background: #f9f6fb; font-weight: bold; }
        .summary-row td { background: #f9f6fb; }
        .signature-section { margin-top: 45px; display: flex; justify-content: space-between; }
        .signature { text-align: center; width: 230px; }
        .signature .line { border-bottom: 1px solid #333; height: 45px; margin-bottom: 5px; }
        .page-info { margin-top: 8px; font-size: 8pt; color: #666; text-align: right; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()">
            <i class="bi bi-printer"></i> Cetak / Simpan PDF
        </button>
        <button type="button" onclick="window.close()">Tutup</button>
    </div>

    <div class="print-header">
        <?php if ($appLogo && file_exists($appLogo)): ?>
            <img src="<?= escape($appLogo) ?>" class="logo" alt="Logo">
        <?php endif; ?>
        <h1><?= escape($appName) ?></h1>
        <h2>LAPORAN TUTUP BUKU BULANAN</h2>
        <p><strong>Periode:</strong> <?= getBulanIndo(intval(substr($mulai, 5, 2))) ?> <?= substr($mulai, 0, 4) ?> s/d <?= getBulanIndo(intval(substr($akhir, 5, 2))) ?> <?= substr($akhir, 0, 4) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Periode</th>
                <th>Jumlah<br>Barang</th>
                <th>Modal</th>
                <th>Omzet</th>
                <th>Tunai</th>
                <th>QRIS</th>
                <th>Transfer</th>
                <th>Laba</th>
                <th>Margin</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $grandItem = $grandModal = $grandOmzet = $grandTunai = $grandQris = $grandTransfer = $grandLaba = 0;
            foreach ($rows as $tb):
                $key = sprintf('%04d-%02d', $tb['tahun'], $tb['bulan']);
                $payment = $paymentMap[$key] ?? [
                    'tunai' => $tb['total_tunai'],
                    'qris' => 0,
                    'transfer' => $tb['total_non_tunai']
                ];
                $margin = $tb['total_omzet'] > 0 ? ($tb['total_laba'] / $tb['total_omzet']) * 100 : 0;

                $grandItem += $tb['total_item'];
                $grandModal += $tb['total_modal'];
                $grandOmzet += $tb['total_omzet'];
                $grandTunai += $payment['tunai'];
                $grandQris += $payment['qris'];
                $grandTransfer += $payment['transfer'];
                $grandLaba += $tb['total_laba'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= getBulanIndo($tb['bulan']) ?> <?= $tb['tahun'] ?></td>
                <td class="text-center"><?= number_format($tb['total_item']) ?></td>
                <td class="text-end"><?= formatRupiah($tb['total_modal']) ?></td>
                <td class="text-end"><?= formatRupiah($tb['total_omzet']) ?></td>
                <td class="text-end"><?= formatRupiah($payment['tunai']) ?></td>
                <td class="text-end"><?= formatRupiah($payment['qris']) ?></td>
                <td class="text-end"><?= formatRupiah($payment['transfer']) ?></td>
                <td class="text-end"><?= formatRupiah($tb['total_laba']) ?></td>
                <td class="text-center"><?= number_format($margin, 1) ?>%</td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($rows) > 0): 
                $grandMargin = $grandOmzet > 0 ? ($grandLaba / $grandOmzet) * 100 : 0;
            ?>
            <tr class="total-row">
                <td colspan="2" class="text-center">TOTAL</td>
                <td class="text-center"><?= number_format($grandItem) ?></td>
                <td class="text-end"><?= formatRupiah($grandModal) ?></td>
                <td class="text-end"><?= formatRupiah($grandOmzet) ?></td>
                <td class="text-end"><?= formatRupiah($grandTunai) ?></td>
                <td class="text-end"><?= formatRupiah($grandQris) ?></td>
                <td class="text-end"><?= formatRupiah($grandTransfer) ?></td>
                <td class="text-end"><?= formatRupiah($grandLaba) ?></td>
                <td class="text-center"><?= number_format($grandMargin, 1) ?>%</td>
            </tr>
            <?php else: ?>
            <tr>
                <td colspan="10" class="text-center">Tidak ada data tutup buku pada rentang periode ini</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="page-info">
        Dicetak: <?= date('d/m/Y H:i') ?> oleh <?= escape($adminName) ?>
    </div>

    <div class="signature-section">
        <div class="signature">
            <p>Disiapkan oleh</p>
            <div class="line"></div>
            <p><?= escape($adminName) ?></p>
        </div>
        <div class="signature">
            <p>Mengetahui</p>
            <div class="line"></div>
            <p>Kepala/Pengurus</p>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            // Auto open print dialog after short delay so CSS loads
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
