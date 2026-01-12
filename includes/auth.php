<?php
/**
 * POS Koperasi Al-Farmasi
 * Sistem Autentikasi
 */

// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    // Regenerate session ID every 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF Token Input Field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Cek apakah user adalah admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Cek apakah user adalah kasir
 */
function isKasir() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'kasir';
}

/**
 * Redirect jika belum login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Redirect jika bukan admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: pos.php');
        exit;
    }
}

/**
 * Validate CSRF for POST requests
 */
function requireCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCSRFToken($token)) {
            die('Invalid CSRF token. Akses ditolak.');
        }
    }
}

/**
 * Login user with brute force protection
 */
function login($username, $password) {
    // Simple brute force protection
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $attempts_key = 'login_attempts_' . md5($ip . $username);
    
    if (!isset($_SESSION[$attempts_key])) {
        $_SESSION[$attempts_key] = ['count' => 0, 'time' => time()];
    }
    
    // Reset after 15 minutes
    if (time() - $_SESSION[$attempts_key]['time'] > 900) {
        $_SESSION[$attempts_key] = ['count' => 0, 'time' => time()];
    }
    
    // Block after 5 failed attempts
    if ($_SESSION[$attempts_key]['count'] >= 5) {
        return false;
    }
    
    $sql = "SELECT * FROM users WHERE username = ? AND status = 'aktif' LIMIT 1";
    $user = fetchOne($sql, [$username]);
    
    if ($user && password_verify($password, $user['password'])) {
        // Reset attempts on successful login
        unset($_SESSION[$attempts_key]);
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['created'] = time();
        
        return true;
    }
    
    // Increment failed attempts
    $_SESSION[$attempts_key]['count']++;
    
    return false;
}

/**
 * Check if login is blocked due to too many attempts
 */
function isLoginBlocked($username) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $attempts_key = 'login_attempts_' . md5($ip . $username);
    
    if (isset($_SESSION[$attempts_key]) && $_SESSION[$attempts_key]['count'] >= 5) {
        $remaining = 900 - (time() - $_SESSION[$attempts_key]['time']);
        if ($remaining > 0) {
            return ceil($remaining / 60); // Return minutes remaining
        }
    }
    return false;
}

/**
 * Logout user
 */
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
    
    header('Location: index.php');
    exit;
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nama_lengkap' => $_SESSION['nama_lengkap'],
        'role' => $_SESSION['role']
    ];
}
