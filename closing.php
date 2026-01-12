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
                
                // Simpan tutup buku
                query("INSERT INTO tutup_buku (bulan, tahun, total_transaksi, total_omzet, total_item, total_tunai, total_non_tunai, keterangan, user_id) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$bulan, $tahun, $summary['total_transaksi'], $summary['total_omzet'], $summary['total_item'], 
                     $summary['total_tunai'], $summary['total_non_tunai'], $keterangan, $_SESSION['user_id']]);
                
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

// Get all tutup buku records
$tutupBukuList = fetchAll("SELECT tb.*, u.nama_lengkap 
                          FROM tutup_buku tb 
                          JOIN users u ON tb.user_id = u.id 
                          ORDER BY tb.tahun DESC, tb.bulan DESC");

// Get available months for closing (yang belum ditutup)
$currentYear = date('Y');
$currentMonth = date('n');

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-journal-check me-2"></i>Tutup Buku Bulanan</h2>
        <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#tutupBukuModal">
            <i class="bi bi-lock me-2"></i>Tutup Buku
        </button>
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
                            <th class="text-center">Transaksi</th>
                            <th class="text-center">Item</th>
                            <th class="text-end">Tunai</th>
                            <th class="text-end">Non-Tunai</th>
                            <th class="text-end">Total Omzet</th>
                            <th>Ditutup Oleh</th>
                            <th>Tanggal</th>
                            <th width="120"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tutupBukuList as $tb): ?>
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
                            <td class="text-center">
                                <span class="badge bg-info"><?= number_format($tb['total_item']) ?></span>
                            </td>
                            <td class="text-end"><?= formatRupiah($tb['total_tunai']) ?></td>
                            <td class="text-end"><?= formatRupiah($tb['total_non_tunai']) ?></td>
                            <td class="text-end fw-bold text-success"><?= formatRupiah($tb['total_omzet']) ?></td>
                            <td><?= escape($tb['nama_lengkap']) ?></td>
                            <td><small><?= date('d/m/Y H:i', strtotime($tb['created_at'])) ?></small></td>
                            <td>
                                <div class="btn-group">
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

// Load preview on page load
loadPreview();
</script>

<?php include 'includes/footer.php'; ?>
