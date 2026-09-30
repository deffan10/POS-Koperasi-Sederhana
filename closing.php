<?php
/**
 * POS Koperasi Al-Farmasi
 * Tutup Buku Bulanan (Admin Only)
 */

require_once 'includes/auth.php';
requireAdmin();

$pageTitle = 'Tutup Buku';
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'tutup_buku') {
        $bulan = intval($_POST['bulan']);
        $tahun = intval($_POST['tahun']);
        $keterangan = trim($_POST['keterangan'] ?? '');
        
        // Validasi
        if ($bulan < 1 || $bulan > 12 || $tahun < 2020 || $tahun > date('Y') + 1) {
            $message = 'Periode tidak valid!';
            $messageType = 'danger';
        } else {
            // Cek apakah sudah ditutup
            $existing = fetchOne("SELECT id FROM tutup_buku WHERE bulan = ? AND tahun = ?", [$bulan, $tahun]);
            
            if ($existing) {
                $message = 'Periode ini sudah ditutup sebelumnya!';
                $messageType = 'warning';
            } else {
                // Hitung data untuk periode ini
                $tanggalMulai = sprintf('%04d-%02d-01', $tahun, $bulan);
                $tanggalAkhir = date('Y-m-t', strtotime($tanggalMulai));
                
                $summary = fetchOne("SELECT 
                                        COUNT(*) as total_transaksi,
                                        COALESCE(SUM(total_harga), 0) as total_omzet,
                                        COALESCE(SUM(total_item), 0) as total_item,
                                        COALESCE(SUM(CASE WHEN metode_pembayaran = 'tunai' THEN total_harga ELSE 0 END), 0) as total_tunai,
                                        COALESCE(SUM(CASE WHEN metode_pembayaran IN ('qris', 'transfer') THEN total_harga ELSE 0 END), 0) as total_non_tunai
                                    FROM transaksi 
                                    WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?", 
                                    [$tanggalMulai, $tanggalAkhir]);
                
                // Hitung total modal dan laba dari detail_transaksi
                $labaSummary = fetchOne("SELECT 
                                            COALESCE(SUM(dt.harga_modal * dt.jumlah), 0) as total_modal,
                                            COALESCE(SUM(dt.laba), 0) as total_laba
                                        FROM detail_transaksi dt
                                        JOIN transaksi t ON dt.transaksi_id = t.id
                                        WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?", 
                                        [$tanggalMulai, $tanggalAkhir]);
                
                // Simpan tutup buku dengan modal dan laba
                query("INSERT INTO tutup_buku (bulan, tahun, total_transaksi, total_omzet, total_item, total_tunai, total_non_tunai, total_modal, total_laba, keterangan, user_id) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$bulan, $tahun, $summary['total_transaksi'], $summary['total_omzet'], $summary['total_item'], 
                     $summary['total_tunai'], $summary['total_non_tunai'], $labaSummary['total_modal'], $labaSummary['total_laba'], $keterangan, $_SESSION['user_id']]);
                
                $message = 'Tutup buku periode ' . getBulanIndo($bulan) . ' ' . $tahun . ' berhasil!';
                $messageType = 'success';
            }
        }
    }
    
    if ($action === 'batal_tutup') {
        $id = intval($_POST['id']);
        
        $tutupBuku = fetchOne("SELECT * FROM tutup_buku WHERE id = ?", [$id]);
        if ($tutupBuku) {
            query("DELETE FROM tutup_buku WHERE id = ?", [$id]);
            $message = 'Tutup buku periode ' . getBulanIndo($tutupBuku['bulan']) . ' ' . $tutupBuku['tahun'] . ' dibatalkan!';
            $messageType = 'success';
        }
    }
}

// Helper function
function getBulanIndo($bulan) {
    $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $namaBulan[$bulan] ?? '';
}

// Rentang periode tutup buku yang tersedia
$periodeRange = fetchOne("SELECT 
                            MIN(CONCAT(tahun, '-', LPAD(bulan, 2, '0'))) as min_periode,
                            MAX(CONCAT(tahun, '-', LPAD(bulan, 2, '0'))) as max_periode
                          FROM tutup_buku");

$minPeriode = $periodeRange['min_periode'] ?? date('Y-m');
$maxPeriode = $periodeRange['max_periode'] ?? date('Y-m');

// Filter range periode (format: YYYY-MM)
$filterMulai = $_GET['mulai'] ?? $minPeriode;
$filterAkhir = $_GET['akhir'] ?? $maxPeriode;

// Validasi format filter
if (!preg_match('/^\d{4}-\d{2}$/', $filterMulai)) {
    $filterMulai = $minPeriode;
}
if (!preg_match('/^\d{4}-\d{2}$/', $filterAkhir)) {
    $filterAkhir = $maxPeriode;
}
if ($filterMulai > $filterAkhir) {
    $temp = $filterMulai;
    $filterMulai = $filterAkhir;
    $filterAkhir = $temp;
}

// Get tutup buku records sesuai filter
$tutupBukuList = fetchAll("SELECT tb.*, u.nama_lengkap 
                          FROM tutup_buku tb 
                          JOIN users u ON tb.user_id = u.id 
                          WHERE CONCAT(tb.tahun, '-', LPAD(tb.bulan, 2, '0')) BETWEEN ? AND ?
                          ORDER BY tb.tahun DESC, tb.bulan DESC", 
                          [$filterMulai, $filterAkhir]);

// Get available months for closing (yang belum ditutup)
$currentYear = date('Y');
$currentMonth = date('n');

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-journal-check me-2"></i>Tutup Buku Bulanan</h2>
        <div class="d-flex gap-2">
            <a href="closing-print.php?mulai=<?= urlencode($filterMulai) ?>&akhir=<?= urlencode($filterAkhir) ?>"
               target="_blank" rel="noopener" id="btnPdfClosing"
               class="btn btn-danger btn-lg">
                <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF A4
            </a>
            <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#tutupBukuModal">
                <i class="bi bi-lock me-2"></i>Tutup Buku
            </button>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') ?> me-2"></i>
        <?= escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Info Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5><i class="bi bi-info-circle me-2"></i>Tentang Tutup Buku</h5>
                    <p class="text-muted mb-0">
                        Tutup buku adalah proses merekap dan mengunci data penjualan per bulan. 
                        Data yang sudah ditutup akan tersimpan sebagai arsip dan menjadi referensi laporan bulanan.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="reports.php" class="btn btn-outline-primary">
                        <i class="bi bi-graph-up me-1"></i>Lihat Laporan
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Rentang Periode -->
    <div class="card mb-4 no-print">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="filterMulai" class="form-label"><i class="bi bi-calendar-event me-1"></i>Dari Periode</label>
                    <input type="month" class="form-control" id="filterMulai" name="mulai"
                           value="<?= escape($filterMulai) ?>" min="<?= escape($minPeriode) ?>" max="<?= escape($maxPeriode) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="filterAkhir" class="form-label"><i class="bi bi-calendar-event me-1"></i>Sampai Periode</label>
                    <input type="month" class="form-control" id="filterAkhir" name="akhir"
                           value="<?= escape($filterAkhir) ?>" min="<?= escape($minPeriode) ?>" max="<?= escape($maxPeriode) ?>" required>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <a href="closing.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Riwayat Tutup Buku -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Tutup Buku</h5>
        </div>
        <div class="card-body p-0">
            <?php if (count($tutupBukuList) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Periode</th>
                            <th class="text-center">Trx</th>
                            <th class="text-end">Modal</th>
                            <th class="text-end">Omzet</th>
                            <th class="text-end text-success">Laba</th>
                            <th class="text-center">Margin</th>
                            <th>Ditutup</th>
                            <th width="100"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tutupBukuList as $tb): 
                            $margin = $tb['total_omzet'] > 0 ? ($tb['total_laba'] / $tb['total_omzet']) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?= getBulanIndo($tb['bulan']) ?> <?= $tb['tahun'] ?></strong>
                                <?php if ($tb['keterangan']): ?>
                                <br><small class="text-muted"><?= escape($tb['keterangan']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary"><?= number_format($tb['total_transaksi']) ?></span>
                            </td>
                            <td class="text-end text-danger"><?= formatRupiah($tb['total_modal'] ?? 0) ?></td>
                            <td class="text-end"><?= formatRupiah($tb['total_omzet']) ?></td>
                            <td class="text-end fw-bold text-success"><?= formatRupiah($tb['total_laba'] ?? 0) ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $margin >= 20 ? 'success' : ($margin >= 10 ? 'warning' : 'danger') ?>">
                                    <?= number_format($margin, 1) ?>%
                                </span>
                            </td>
                            <td>
                                <small><?= escape($tb['nama_lengkap']) ?></small><br>
                                <small class="text-muted"><?= date('d/m/Y', strtotime($tb['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detailLabaModal"
                                            data-bulan="<?= $tb['bulan'] ?>"
                                            data-tahun="<?= $tb['tahun'] ?>"
                                            data-periode="<?= getBulanIndo($tb['bulan']) ?> <?= $tb['tahun'] ?>"
                                            title="Detail Laba per Item">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="api/export.php?format=excel&type=summary&tanggal_mulai=<?= sprintf('%04d-%02d-01', $tb['tahun'], $tb['bulan']) ?>&tanggal_akhir=<?= date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $tb['tahun'], $tb['bulan']))) ?>" 
                                       class="btn btn-sm btn-outline-success" title="Download Excel">
                                        <i class="bi bi-file-earmark-excel"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="batalTutupBuku(<?= $tb['id'] ?>, '<?= getBulanIndo($tb['bulan']) ?> <?= $tb['tahun'] ?>')"
                                            title="Batalkan Tutup Buku">
                                        <i class="bi bi-unlock"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-journal-x display-1"></i>
                <p class="mt-3">Belum ada data tutup buku</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Detail Laba per Item -->
<div class="modal fade" id="detailLabaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-graph-up me-2"></i>Detail Laba per Item - <span id="detailPeriode"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Rincian laba tiap produk berdasarkan metode pembayaran yang digunakan (tunai, QRIS, atau transfer).
                </div>
                <div class="table-responsive" style="max-height: 60vh;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Produk</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Laba Tunai</th>
                                <th class="text-end">Laba QRIS</th>
                                <th class="text-end">Laba Transfer</th>
                                <th class="text-end">Total Laba</th>
                            </tr>
                        </thead>
                        <tbody id="detailLabaBody"></tbody>
                        <tfoot id="detailLabaFoot" class="table-light fw-bold"></tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tutup Buku -->
<div class="modal fade" id="tutupBukuModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formTutupBuku">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="tutup_buku">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-lock me-2"></i>Tutup Buku Bulanan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Pilih periode yang akan ditutup. Data penjualan akan direkap dan disimpan.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bulan <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" name="bulan" id="selectBulan" required>
                                <option value="">Pilih Bulan</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $i == $currentMonth ? 'selected' : '' ?>>
                                    <?= getBulanIndo($i) ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tahun <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" name="tahun" id="selectTahun" required>
                                <?php for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div id="previewData" class="mb-3" style="display: none;">
                        <label class="form-label">Preview Data</label>
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 id="previewTransaksi">-</h4>
                                        <small class="text-muted">Transaksi</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 id="previewItem">-</h4>
                                        <small class="text-muted">Item</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 id="previewOmzet" class="text-success">-</h4>
                                        <small class="text-muted">Omzet</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Keterangan (opsional)</label>
                        <input type="text" class="form-control" name="keterangan" 
                               placeholder="Contoh: Tutup buku rutin bulanan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-lock me-1"></i>Tutup Buku
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form Batal Tutup Buku -->
<form id="formBatalTutup" method="POST" style="display: none;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="batal_tutup">
    <input type="hidden" name="id" id="batalTutupId">
</form>

<script>
// Preview data saat pilih bulan/tahun
document.getElementById('selectBulan').addEventListener('change', loadPreview);
document.getElementById('selectTahun').addEventListener('change', loadPreview);

async function loadPreview() {
    const bulan = document.getElementById('selectBulan').value;
    const tahun = document.getElementById('selectTahun').value;
    
    if (!bulan || !tahun) {
        document.getElementById('previewData').style.display = 'none';
        return;
    }
    
    const tanggalMulai = `${tahun}-${bulan.padStart(2, '0')}-01`;
    const lastDay = new Date(tahun, bulan, 0).getDate();
    const tanggalAkhir = `${tahun}-${bulan.padStart(2, '0')}-${lastDay}`;
    
    try {
        const response = await fetch(`api/report-summary.php?tanggal_mulai=${tanggalMulai}&tanggal_akhir=${tanggalAkhir}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('previewTransaksi').textContent = data.summary.total_transaksi;
            document.getElementById('previewItem').textContent = data.summary.total_item;
            document.getElementById('previewOmzet').textContent = data.summary.total_omzet_formatted;
            document.getElementById('previewData').style.display = '';
        }
    } catch (e) {
        console.error('Error loading preview:', e);
    }
}

function batalTutupBuku(id, periode) {
    if (confirm('Batalkan tutup buku periode ' + periode + '?\n\nData rekap akan dihapus.')) {
        document.getElementById('batalTutupId').value = id;
        document.getElementById('formBatalTutup').submit();
    }
}

// Detail laba per item berdasarkan metode pembayaran
const detailLabaModal = document.getElementById('detailLabaModal');
if (detailLabaModal) {
    detailLabaModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        const bulan = btn.getAttribute('data-bulan');
        const tahun = btn.getAttribute('data-tahun');
        const periode = btn.getAttribute('data-periode');
        document.getElementById('detailPeriode').textContent = periode;
        loadDetailLaba(bulan, tahun);
    });
}

async function loadDetailLaba(bulan, tahun) {
    const body = document.getElementById('detailLabaBody');
    const foot = document.getElementById('detailLabaFoot');
    body.innerHTML = '<tr><td colspan="6" class="text-center py-3">Memuat data...</td></tr>';
    foot.innerHTML = '';

    try {
        const response = await fetch(`api/closing-detail.php?bulan=${bulan}&tahun=${tahun}`);
        const data = await response.json();

        if (!data.success) {
            body.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">${escapeHtml(data.message || 'Gagal memuat data')}</td></tr>`;
            return;
        }

        if (!data.items || data.items.length === 0) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada data penjualan</td></tr>';
            return;
        }

        let totalQty = 0, totalTunai = 0, totalQris = 0, totalTransfer = 0, totalLaba = 0;

        body.innerHTML = data.items.map(item => {
            totalQty += parseFloat(item.total_qty);
            totalTunai += parseFloat(item.laba_tunai);
            totalQris += parseFloat(item.laba_qris);
            totalTransfer += parseFloat(item.laba_transfer);
            totalLaba += parseFloat(item.total_laba);

            return `<tr>
                <td><strong>${escapeHtml(item.kode_produk)}</strong><br><small>${escapeHtml(item.nama_produk)}</small></td>
                <td class="text-center">${numberFormat(item.total_qty)}</td>
                <td class="text-end ${item.laba_tunai > 0 ? 'text-success' : 'text-muted'}">${formatRupiah(item.laba_tunai)}</td>
                <td class="text-end ${item.laba_qris > 0 ? 'text-primary' : 'text-muted'}">${formatRupiah(item.laba_qris)}</td>
                <td class="text-end ${item.laba_transfer > 0 ? 'text-info' : 'text-muted'}">${formatRupiah(item.laba_transfer)}</td>
                <td class="text-end fw-bold">${formatRupiah(item.total_laba)}</td>
            </tr>`;
        }).join('');

        foot.innerHTML = `<tr>
            <td>Total</td>
            <td class="text-center">${numberFormat(totalQty)}</td>
            <td class="text-end text-success">${formatRupiah(totalTunai)}</td>
            <td class="text-end text-primary">${formatRupiah(totalQris)}</td>
            <td class="text-end text-info">${formatRupiah(totalTransfer)}</td>
            <td class="text-end">${formatRupiah(totalLaba)}</td>
        </tr>`;
    } catch (e) {
        console.error('Error loading detail laba:', e);
        body.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Terjadi kesalahan saat memuat data</td></tr>';
    }
}

function formatRupiah(angka) {
    return 'Rp ' + Number(angka).toLocaleString('id-ID');
}

function numberFormat(angka) {
    return Number(angka).toLocaleString('id-ID');
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Update PDF link saat filter berubah
const btnPdfClosing = document.getElementById('btnPdfClosing');
if (btnPdfClosing) {
    function updatePdfLink() {
        const mulai = document.getElementById('filterMulai').value;
        const akhir = document.getElementById('filterAkhir').value;
        if (mulai && akhir) {
            btnPdfClosing.href = `closing-print.php?mulai=${encodeURIComponent(mulai)}&akhir=${encodeURIComponent(akhir)}`;
        }
    }
    document.getElementById('filterMulai').addEventListener('change', updatePdfLink);
    document.getElementById('filterAkhir').addEventListener('change', updatePdfLink);
}

// Load preview on page load
loadPreview();
</script>

<?php include 'includes/footer.php'; ?>
