<?php
/**
 * POS Koperasi Al-Farmasi
 * Logout
 */

require_once 'includes/auth.php';

// Logout hanya bisa via POST dengan CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (validateCSRFToken($csrfToken)) {
        logout();
    }
}

// Jika GET atau CSRF invalid, redirect ke dashboard
header('Location: dashboard.php');
exit;
