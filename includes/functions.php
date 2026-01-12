<?php
/**
 * Helper Functions
 * POS Koperasi Al-Farmasi
 */

/**
 * Format currency to Indonesian Rupiah
 */
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Generate unique transaction number
 */
function generateTransactionNumber() {
    return 'TRX' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * JSON response helper
 */
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Format date to Indonesian format
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Check AJAX request
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
