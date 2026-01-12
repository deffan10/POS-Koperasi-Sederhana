# POS Koperasi Al-Farmasi

Aplikasi Point of Sale (POS) sederhana untuk warung koperasi. Dirancang untuk membantu transaksi penjualan dengan sistem yang cepat, sederhana, dan mudah digunakan oleh pengguna non-teknis.

## Fitur Utama

### 🛒 POS (Point of Sale)
- Input transaksi menggunakan kode produk atau nama produk
- Pencarian produk otomatis
- Input jumlah barang dengan tombol +/-
- Perhitungan total otomatis
- Pilihan metode pembayaran (Tunai / Non-Tunai)
- Perhitungan kembalian otomatis untuk pembayaran tunai
- Stok berkurang otomatis setelah transaksi
- *Fitur cetak struk disiapkan untuk pengembangan di masa depan*

### 📦 Manajemen Produk (Admin)
- Tambah, edit, hapus produk
- Set kode produk unik (angka, huruf, atau kombinasi)
- Set nama produk, harga jual, stok, dan satuan
- Kategori produk

### 📊 Manajemen Stok (Admin)
- Penambahan stok masuk
- Pengurangan stok keluar
- Penyesuaian stok
- Riwayat perubahan stok

### 📈 Laporan Penjualan
- Laporan penjualan harian
- Total omzet
- Daftar transaksi dengan detail

### 👥 Sistem Akses
- **Admin**: Akses penuh ke seluruh fitur
- **Kasir**: Akses halaman POS dan melihat transaksi

## Teknologi

- **Backend**: PHP 7.4+
- **Database**: MySQL / MariaDB 11.8.3
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Icons**: Font Awesome 6

## Instalasi

### 1. Persyaratan Sistem
- Web Server (Apache/Nginx) dengan PHP 7.4+
- MySQL 5.7+ atau MariaDB 10.3+
- Browser modern (Chrome, Firefox, Safari, Edge)

### 2. Setup Database

```bash
# Import database schema
mysql -u root -p < database/schema.sql
```

Atau import file `database/schema.sql` melalui phpMyAdmin.

### 3. Konfigurasi

Edit file `config/config.php` sesuai dengan konfigurasi database Anda:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pos_koperasi');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Akses Aplikasi

Buka browser dan akses aplikasi:
```
http://localhost/POS-Koperasi-Sederhana/
```

## Default Login

| Role  | Username | Password   |
|-------|----------|------------|
| Admin | admin    | admin123   |
| Kasir | kasir    | kasir123   |

## Struktur Direktori

```
POS-Koperasi-Sederhana/
├── api/                    # API endpoints
│   ├── categories.php
│   ├── products.php
│   ├── reports.php
│   ├── stock.php
│   └── transactions.php
├── config/                 # Konfigurasi
│   └── config.php
├── css/                    # Stylesheet
│   └── style.css
├── database/               # Database schema
│   └── schema.sql
├── includes/               # PHP helpers
│   ├── auth.php
│   ├── database.php
│   └── functions.php
├── js/                     # JavaScript
│   └── app.js
├── index.php               # Dashboard
├── login.php               # Halaman login
├── logout.php              # Handler logout
├── pos.php                 # Halaman kasir/POS
├── products.php            # Manajemen produk
├── reports.php             # Laporan penjualan
├── stock.php               # Manajemen stok
└── README.md
```

## Tampilan

### Responsif
- Desktop: Layout 2 kolom pada halaman POS
- Mobile: Layout 1 kolom dengan sidebar tersembunyi

### User-Friendly
- Tombol besar dan jelas
- Warna kontras untuk kemudahan membaca
- Notifikasi toast untuk feedback aksi
- Loading indicator untuk proses async

## Keamanan

- Password di-hash menggunakan `password_hash()` (bcrypt)
- Prepared statements untuk mencegah SQL Injection
- Session-based authentication
- Role-based access control
- XSS protection dengan `htmlspecialchars()`

## Pengembangan Selanjutnya

- [ ] Fitur cetak struk (thermal printer)
- [ ] Laporan per periode (mingguan, bulanan)
- [ ] Export laporan ke Excel/PDF
- [ ] Manajemen user (tambah/edit user)
- [ ] Backup dan restore database
- [ ] Multi-outlet support

## Lisensi

MIT License - Silakan gunakan dan modifikasi sesuai kebutuhan.

---

**POS Koperasi Al-Farmasi** - Sistem Kasir Sederhana untuk Warung Koperasi
