-- =============================================
-- POS Koperasi Al-Farmasi - Database Schema
-- Database: MySQL/MariaDB 11.8.3 Compatible
-- =============================================

CREATE DATABASE IF NOT EXISTS pos_koperasi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pos_koperasi;

-- =============================================
-- Tabel Users (Admin & Kasir)
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Tabel Kategori Produk
-- =============================================
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- Tabel Produk
-- =============================================
CREATE TABLE IF NOT EXISTS produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_produk VARCHAR(50) NOT NULL UNIQUE,
    nama_produk VARCHAR(200) NOT NULL,
    kategori_id INT,
    harga_modal DECIMAL(12, 2) NOT NULL DEFAULT 0,
    harga_jual DECIMAL(12, 2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL,
    INDEX idx_kode_produk (kode_produk),
    INDEX idx_nama_produk (nama_produk)
) ENGINE=InnoDB;

-- =============================================
-- Tabel Transaksi (Header)
-- =============================================
CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(50) NOT NULL UNIQUE,
    tanggal_transaksi DATETIME NOT NULL,
    user_id INT NOT NULL,
    total_item INT NOT NULL DEFAULT 0,
    total_harga DECIMAL(15, 2) NOT NULL DEFAULT 0,
    metode_pembayaran ENUM('tunai', 'qris', 'transfer') NOT NULL DEFAULT 'tunai',
    uang_diterima DECIMAL(15, 2) DEFAULT 0,
    kembalian DECIMAL(15, 2) DEFAULT 0,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_no_transaksi (no_transaksi),
    INDEX idx_tanggal (tanggal_transaksi)
) ENGINE=InnoDB;

-- =============================================
-- Tabel Detail Transaksi
-- =============================================
CREATE TABLE IF NOT EXISTS detail_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    produk_id INT NOT NULL,
    kode_produk VARCHAR(50) NOT NULL,
    nama_produk VARCHAR(200) NOT NULL,
    harga_modal DECIMAL(12, 2) NOT NULL DEFAULT 0,
    harga_satuan DECIMAL(12, 2) NOT NULL,
    jumlah INT NOT NULL,
    subtotal DECIMAL(15, 2) NOT NULL,
    laba DECIMAL(15, 2) NOT NULL DEFAULT 0,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id),
    INDEX idx_transaksi_id (transaksi_id)
) ENGINE=InnoDB;

-- =============================================
-- Tabel Riwayat Stok
-- =============================================
CREATE TABLE IF NOT EXISTS riwayat_stok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT NOT NULL,
    jenis_perubahan ENUM('masuk', 'keluar', 'koreksi') NOT NULL,
    jumlah INT NOT NULL,
    stok_sebelum INT NOT NULL,
    stok_sesudah INT NOT NULL,
    keterangan VARCHAR(255),
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_produk_id (produk_id),
    INDEX idx_tanggal (created_at)
) ENGINE=InnoDB;

-- =============================================
-- Tabel Tutup Buku Bulanan
-- =============================================
CREATE TABLE IF NOT EXISTS tutup_buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulan TINYINT NOT NULL,
    tahun SMALLINT NOT NULL,
    total_transaksi INT NOT NULL DEFAULT 0,
    total_omzet DECIMAL(15, 2) NOT NULL DEFAULT 0,
    total_modal DECIMAL(15, 2) NOT NULL DEFAULT 0,
    total_laba DECIMAL(15, 2) NOT NULL DEFAULT 0,
    total_item INT NOT NULL DEFAULT 0,
    total_tunai DECIMAL(15, 2) NOT NULL DEFAULT 0,
    total_non_tunai DECIMAL(15, 2) NOT NULL DEFAULT 0,
    keterangan VARCHAR(255),
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_periode (bulan, tahun),
    INDEX idx_periode (tahun, bulan)
) ENGINE=InnoDB;

-- =============================================
-- Tabel Settings (Konfigurasi Sistem)
-- =============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB;

-- Data Default Settings Pembayaran
INSERT INTO settings (setting_key, setting_value) VALUES
('qris_aktif', '0'),
('qris_nama', 'QRIS Koperasi Al-Farmasi'),
('qris_image', ''),
('transfer_aktif', '0'),
('transfer_bank', ''),
('transfer_rekening', ''),
('transfer_atas_nama', '')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- =============================================
-- Data Awal - User Default
-- Password default: password (sudah di-hash dengan bcrypt)
-- PENTING: Segera ganti password setelah login pertama!
-- =============================================
INSERT INTO users (username, password, nama_lengkap, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('kasir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Koperasi', 'kasir');

-- =============================================
-- Data Awal - Kategori
-- =============================================
INSERT INTO kategori (nama_kategori) VALUES 
('Makanan'),
('Minuman'),
('Sembako'),
('Obat-obatan'),
('Alat Tulis'),
('Lainnya');

-- =============================================
-- Data Contoh - Produk
-- =============================================
INSERT INTO produk (kode_produk, nama_produk, kategori_id, harga_jual, stok) VALUES 
('MKN001', 'Mie Instan Goreng', 1, 3500, 100),
('MKN002', 'Mie Instan Kuah', 1, 3000, 100),
('MKN003', 'Roti Tawar', 1, 15000, 20),
('MNM001', 'Aqua 600ml', 2, 4000, 50),
('MNM002', 'Teh Botol Sosro', 2, 5000, 30),
('MNM003', 'Kopi Sachet', 2, 2000, 200),
('SMB001', 'Beras 1kg', 3, 15000, 50),
('SMB002', 'Gula Pasir 1kg', 3, 18000, 30),
('SMB003', 'Minyak Goreng 1L', 3, 20000, 25),
('OBT001', 'Paracetamol', 4, 5000, 50),
('OBT002', 'Vitamin C', 4, 8000, 40),
('ATK001', 'Pulpen', 5, 3000, 100),
('ATK002', 'Buku Tulis', 5, 5000, 50);
