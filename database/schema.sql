-- =====================================================
-- POS Koperasi Al-Farmasi Database Schema
-- Compatible with MySQL / MariaDB 11.8.3
-- =====================================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS pos_koperasi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pos_koperasi;

-- =====================================================
-- Users Table
-- =====================================================
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

-- =====================================================
-- Categories Table
-- =====================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Products Table
-- =====================================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_produk VARCHAR(50) NOT NULL UNIQUE,
    nama_produk VARCHAR(200) NOT NULL,
    category_id INT,
    harga_jual DECIMAL(12,2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    satuan VARCHAR(50) DEFAULT 'pcs',
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Index for faster product search
CREATE INDEX idx_kode_produk ON products(kode_produk);
CREATE INDEX idx_nama_produk ON products(nama_produk);

-- =====================================================
-- Transactions Table
-- =====================================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_transaksi VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    metode_pembayaran ENUM('tunai', 'non-tunai') NOT NULL DEFAULT 'tunai',
    jumlah_bayar DECIMAL(12,2) DEFAULT 0,
    kembalian DECIMAL(12,2) DEFAULT 0,
    catatan TEXT,
    tanggal_transaksi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Index for faster transaction search
CREATE INDEX idx_tanggal_transaksi ON transactions(tanggal_transaksi);
CREATE INDEX idx_nomor_transaksi ON transactions(nomor_transaksi);

-- =====================================================
-- Transaction Items Table
-- =====================================================
CREATE TABLE IF NOT EXISTS transaction_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    kode_produk VARCHAR(50) NOT NULL,
    nama_produk VARCHAR(200) NOT NULL,
    harga_satuan DECIMAL(12,2) NOT NULL,
    jumlah INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- =====================================================
-- Stock History Table
-- =====================================================
CREATE TABLE IF NOT EXISTS stock_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    jenis_perubahan ENUM('masuk', 'keluar', 'penyesuaian') NOT NULL,
    jumlah INT NOT NULL,
    stok_sebelum INT NOT NULL,
    stok_sesudah INT NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Index for faster stock history search
CREATE INDEX idx_product_stock ON stock_history(product_id);
CREATE INDEX idx_created_at ON stock_history(created_at);

-- =====================================================
-- Default Data
-- =====================================================

-- Default Admin User (password: admin123)
INSERT INTO users (username, password, nama_lengkap, role, status) VALUES 
('admin', '$2y$10$AKlHOaNQAIlD8rw/JYiBjuuPW9FIoi0Xqy0sBkUCwi3hinVMV.hsa', 'Administrator', 'admin', 'aktif');

-- Default Kasir User (password: kasir123)
INSERT INTO users (username, password, nama_lengkap, role, status) VALUES 
('kasir', '$2y$10$EzK7GWdVmJbhK60OH82j/eoIodIWDPpPX8bLxKbEvYcSMrQCO0BB.', 'Kasir Utama', 'kasir', 'aktif');

-- Default Categories
INSERT INTO categories (nama_kategori, deskripsi) VALUES 
('Makanan', 'Produk makanan ringan dan berat'),
('Minuman', 'Produk minuman dalam kemasan'),
('Kebutuhan Pokok', 'Beras, gula, minyak, dan kebutuhan dapur'),
('Obat-obatan', 'Obat generik dan vitamin'),
('Lainnya', 'Produk lainnya');

-- Sample Products
INSERT INTO products (kode_produk, nama_produk, category_id, harga_jual, stok, satuan) VALUES 
('MKN001', 'Indomie Goreng', 1, 3500, 100, 'pcs'),
('MKN002', 'Chitato Original 68g', 1, 12000, 50, 'pcs'),
('MNM001', 'Aqua Botol 600ml', 2, 4000, 200, 'botol'),
('MNM002', 'Teh Pucuk Harum 350ml', 2, 5000, 150, 'botol'),
('KBP001', 'Beras 5kg', 3, 65000, 30, 'karung'),
('KBP002', 'Gula Pasir 1kg', 3, 15000, 50, 'pack'),
('KBP003', 'Minyak Goreng 1L', 3, 18000, 40, 'botol'),
('OBT001', 'Paracetamol', 4, 2000, 100, 'strip'),
('OBT002', 'Vitamin C', 4, 5000, 80, 'strip');
