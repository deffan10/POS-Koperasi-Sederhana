<?php
/**
 * Authentication & Session Helper
 * POS Koperasi Al-Farmasi
 */

require_once __DIR__ . '/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND status = 'aktif'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            return true;
        }
        return false;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    /**
     * Check if user is kasir
     */
    public function isKasir() {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'kasir';
    }
    
    /**
     * Get current user data
     */
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'nama_lengkap' => $_SESSION['nama_lengkap'],
            'role' => $_SESSION['role']
        ];
    }
    
    /**
     * Require login - redirect if not logged in
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * Require admin role - redirect if not admin
     */
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: index.php?error=access_denied');
            exit;
        }
    }
}
