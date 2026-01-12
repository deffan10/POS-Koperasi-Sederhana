<?php
/**
 * POS Koperasi Al-Farmasi
 * Header Template
 */

// Get app settings
$appName = getSetting('app_name', APP_NAME);
$appLogo = getSetting('app_logo', '');
$appFavicon = getSetting('app_favicon', '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= escape($appName) ?></title>
    
    <?php if ($appFavicon && file_exists($appFavicon)): ?>
    <link rel="icon" type="image/png" href="<?= $appFavicon ?>">
    <?php endif; ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: #5D4E6D;">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
                <?php if ($appLogo && file_exists($appLogo)): ?>
                <img src="<?= $appLogo ?>" alt="Logo" style="max-height: 35px;" class="me-2">
                <?php else: ?>
                <i class="bi bi-shop me-2"></i>
                <?php endif; ?>
                <?= escape($appName) ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : '' ?>" href="pos.php">
                            <i class="bi bi-cart3 me-1"></i> Kasir
                        </a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                    <!-- Dropdown Manajemen Produk -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'stock.php', 'categories.php']) ? 'active' : '' ?>" 
                           href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-box-seam me-1"></i> Produk
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>" href="products.php">
                                    <i class="bi bi-box-seam me-2"></i>Daftar Produk
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'stock.php' ? 'active' : '' ?>" href="stock.php">
                                    <i class="bi bi-boxes me-2"></i>Manajemen Stok
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>" href="categories.php">
                                    <i class="bi bi-tags me-2"></i>Kategori
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Dropdown Laporan -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['reports.php', 'transactions.php', 'closing.php']) ? 'active' : '' ?>" 
                           href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-graph-up me-1"></i> Laporan
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>" href="reports.php">
                                    <i class="bi bi-graph-up me-2"></i>Laporan Penjualan
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : '' ?>" href="transactions.php">
                                    <i class="bi bi-receipt me-2"></i>Riwayat Transaksi
                                </a>
                            </li>
                            <?php if (isAdmin()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'closing.php' ? 'active' : '' ?>" href="closing.php">
                                    <i class="bi bi-journal-check me-2"></i>Tutup Buku
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                    <!-- Dropdown Pengaturan -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'settings.php', 'settings-general.php']) ? 'active' : '' ?>" 
                           href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i> Pengaturan
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>" href="users.php">
                                    <i class="bi bi-people me-2"></i>Pengguna
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'settings-general.php' ? 'active' : '' ?>" href="settings-general.php">
                                    <i class="bi bi-shop me-2"></i>Umum (Logo & Favicon)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>" href="settings.php">
                                    <i class="bi bi-credit-card me-2"></i>Pembayaran Non-Tunai
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="https://ksp-alfarma.ummada.ac.id" class="nav-link text-white" target="_blank">
                            <i class="bi bi-building"></i> KSP
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= escape($_SESSION['nama_lengkap']) ?>
                            <span class="badge bg-light ms-1" style="color: #5D4E6D;"><?= ucfirst($_SESSION['role']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text text-muted small">Login sebagai <?= ucfirst($_SESSION['role']) ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="logout.php" method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Keluar
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="<?= isLoggedIn() ? 'main-content' : '' ?>">
