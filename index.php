<?php
/**
 * POS Koperasi Al-Farmasi
 * Halaman Login (Index)
 */

require_once 'includes/auth.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$blocked = false;

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        $error = 'Sesi keamanan tidak valid. Silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Username dan password harus diisi!';
        } else {
            // Check if blocked
            $blockedMinutes = isLoginBlocked($username);
            if ($blockedMinutes) {
                $error = "Terlalu banyak percobaan login. Coba lagi dalam $blockedMinutes menit.";
                $blocked = true;
            } else {
                if (login($username, $password)) {
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Username atau password salah!';
                }
            }
        }
    }
}

$pageTitle = 'Login';

// Get app settings for login page
$appName = getSetting('app_name', APP_NAME);
$appLogo = getSetting('app_logo', '');
$appFavicon = getSetting('app_favicon', '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= escape($appName) ?></title>
    <?php if ($appFavicon && file_exists($appFavicon)): ?>
    <link rel="icon" type="image/png" href="<?= $appFavicon ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <?php if ($appLogo && file_exists($appLogo)): ?>
                <img src="<?= $appLogo ?>" alt="Logo" class="login-logo mb-3" style="max-height: 80px;">
                <?php else: ?>
                <i class="bi bi-shop-window login-icon"></i>
                <?php endif; ?>
                <h1 class="login-title"><?= escape($appName) ?></h1>
                <p class="login-subtitle">Sistem Point of Sale</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= escape($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <?= csrfField() ?>
                <div class="mb-4">
                    <label for="username" class="form-label">
                        <i class="bi bi-person me-1"></i>Username
                    </label>
                    <input type="text" 
                           class="form-control form-control-lg" 
                           id="username" 
                           name="username" 
                           placeholder="Masukkan username"
                           value="<?= escape($_POST['username'] ?? '') ?>"
                           autofocus 
                           required>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock me-1"></i>Password
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control form-control-lg" 
                               id="password" 
                               name="password" 
                               placeholder="Masukkan password"
                               required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success btn-lg w-100 login-btn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
                </button>
            </form>
            
            <div class="login-footer">
                <small class="text-muted">
                    &copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?>
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    </script>
</body>
</html>
