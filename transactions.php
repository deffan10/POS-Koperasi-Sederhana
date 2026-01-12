<?php
/**
 * POS Koperasi Al-Farmasi
 * Daftar Transaksi
 */

require_once 'includes/auth.php';
requireLogin();

$pageTitle = 'Daftar Transaksi';

// Filter
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

$where = ["DATE(t.tanggal_transaksi) = ?"];
$params = [$tanggal];

if ($search) {
    $where[] = "(t.no_transaksi LIKE ? OR u.nama_lengkap LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(' AND ', $where);
$transaksi = fetchAll("SELECT t.*, u.nama_lengkap as kasir 
                      FROM transaksi t 
                      JOIN users u ON t.user_id = u.id 
                      WHERE $whereClause 
                      ORDER BY t.tanggal_transaksi DESC", $params);

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-receipt me-2"></i>Daftar Transaksi</h2>
        <a href="pos.php" class="btn btn-success btn-lg">
            <i class="bi bi-plus-circle me-2"></i>Transaksi Baru
        </a>
    </div>
    
    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Tanggal</label>
                    <input type="date" class="form-control form-control-lg" name="tanggal" 
                           value="<?= escape($tanggal) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Cari</label>
                    <input type="text" class="form-control form-control-lg" name="search" 
                           value="<?= escape($search) ?>" placeholder="No. Transaksi / Kasir">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-search me-1"></i>Cari
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Transaction List -->
    <div class="card">
        <div class="card-header bg-white">
            <strong><?= date('l, d F Y', strtotime($tanggal)) ?></strong>
            <span class="badge bg-primary ms-2"><?= count($transaksi) ?> transaksi</span>
        </div>
        <div class="card-body p-0">
            <?php if (count($transaksi) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Waktu</th>
                            <th>Kasir</th>
                            <th class="text-center">Item</th>
                            <th class="text-center">Pembayaran</th>
                            <th class="text-end">Total</th>
                            <th width="80"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transaksi as $trx): ?>
                        <tr>
                            <td><code class="fs-6"><?= escape($trx['no_transaksi']) ?></code></td>
                            <td><?= date('H:i:s', strtotime($trx['tanggal_transaksi'])) ?></td>
                            <td><?= escape($trx['kasir']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-info"><?= $trx['total_item'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php 
                                $metodeBadge = [
                                    'tunai' => ['bg-success', 'bi-cash', 'Tunai'],
                                    'qris' => ['bg-primary', 'bi-qr-code', 'QRIS'],
                                    'transfer' => ['bg-info', 'bi-bank', 'Transfer']
                                ];
                                $badge = $metodeBadge[$trx['metode_pembayaran']] ?? ['bg-secondary', 'bi-credit-card', 'Lainnya'];
                                ?>
                                <span class="badge <?= $badge[0] ?>"><i class="bi <?= $badge[1] ?> me-1"></i><?= $badge[2] ?></span>
                            </td>
                            <td class="text-end fw-bold text-success"><?= formatRupiah($trx['total_harga']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="viewDetail(<?= $trx['id'] ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-success">
                        <tr>
                            <td colspan="5"><strong>TOTAL</strong></td>
                            <td class="text-end">
                                <strong><?= formatRupiah(array_sum(array_column($transaksi, 'total_harga'))) ?></strong>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox display-4"></i>
                <p class="mb-0 mt-2">Tidak ada transaksi pada tanggal ini</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Detail Transaksi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <!-- Print button for future development -->
                <button type="button" class="btn btn-outline-primary" disabled title="Fitur akan tersedia di versi mendatang">
                    <i class="bi bi-printer me-1"></i>Cetak Struk
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetail(id) {
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
    
    fetch('api/transactions.php?action=detail&id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const trx = data.transaksi;
                const items = data.items;
                
                let html = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted">No. Transaksi</td>
                                    <td><strong>${trx.no_transaksi}</strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal</td>
                                    <td>${new Date(trx.tanggal_transaksi).toLocaleString('id-ID')}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Kasir</td>
                                    <td>${trx.kasir}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted">Pembayaran</td>
                                    <td>${trx.metode_pembayaran.toUpperCase()}</td>
                                </tr>
                                ${trx.metode_pembayaran === 'tunai' ? `
                                <tr>
                                    <td class="text-muted">Uang Diterima</td>
                                    <td>${formatRupiah(trx.uang_diterima)}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Kembalian</td>
                                    <td>${formatRupiah(trx.kembalian)}</td>
                                </tr>
                                ` : ''}
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="80">Kode</th>
                                <th>Produk</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                items.forEach(item => {
                    html += `
                        <tr>
                            <td><code>${item.kode_produk}</code></td>
                            <td>${item.nama_produk}</td>
                            <td class="text-end">${formatRupiah(item.harga_satuan)}</td>
                            <td class="text-center">${item.jumlah}</td>
                            <td class="text-end">${formatRupiah(item.subtotal)}</td>
                        </tr>
                    `;
                });
                
                html += `
                        </tbody>
                        <tfoot class="table-success">
                            <tr>
                                <td colspan="4" class="text-end"><strong>TOTAL</strong></td>
                                <td class="text-end"><strong>${formatRupiah(trx.total_harga)}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                `;
                
                document.getElementById('detailContent').innerHTML = html;
            } else {
                document.getElementById('detailContent').innerHTML = '<div class="alert alert-danger">Gagal memuat data</div>';
            }
        })
        .catch(error => {
            document.getElementById('detailContent').innerHTML = '<div class="alert alert-danger">Terjadi kesalahan</div>';
        });
}

function formatRupiah(angka) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
}
</script>

<?php include 'includes/footer.php'; ?>
