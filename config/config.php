<?php
/**
 * Database Configuration
 * POS Koperasi Al-Farmasi
 */

// Database settings - modify these according to your environment
define('DB_HOST', 'localhost');
define('DB_NAME', 'pos_koperasi');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'POS Koperasi Al-Farmasi');
define('APP_VERSION', '1.0.0');

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
