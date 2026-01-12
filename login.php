<?php
/**
 * Login Page
 * POS Koperasi Al-Farmasi
 */

require_once __DIR__ . '/includes/auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } elseif ($auth->login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password salah';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS Koperasi Al-Farmasi</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-store"></i> POS Koperasi</h1>
                <p>Al-Farmasi</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" class="form-control form-control-lg" 
                           placeholder="Masukkan username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" class="form-control form-control-lg" 
                           placeholder="Masukkan password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> MASUK
                </button>
            </form>
            
            <div class="text-center mt-2 text-muted" style="font-size: 0.85rem;">
                <p>Default Login:</p>
                <p>Admin: admin / admin123</p>
                <p>Kasir: kasir / kasir123</p>
            </div>
        </div>
    </div>
</body>
</html>
