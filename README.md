# POS Koperasi Al-Farmasi

Aplikasi Point of Sale (POS) sederhana untuk warung koperasi. Dirancang untuk kemudahan penggunaan oleh pengguna non-teknis dengan tampilan yang bersih dan tombol yang besar.

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green)

## 🎯 Tujuan Aplikasi

Membantu transaksi penjualan warung koperasi dengan sistem POS yang:
- ⚡ **Cepat** - Input produk dengan autocomplete
- 🎨 **Sederhana** - UI bersih dengan tombol besar
- 👩‍🦳 **Mudah** - Cocok untuk pengguna non-teknis (ibu-ibu warung)

## 📋 Fitur Utama

### 🛒 POS (Kasir)
- Pencarian produk dengan kode atau nama (autocomplete)
- Kode produk bersifat unik dan bebas (angka, huruf, atau kombinasi)
- Produk otomatis muncul setelah kode/nama dimasukkan
- Input jumlah barang dengan tombol +/-
- Perhitungan total otomatis
- **💳 Multi Metode Pembayaran**:
  - 💵 **Tunai** - Input uang diterima, hitung kembalian otomatis
  - 📱 **QRIS** - Tampilkan QR Code untuk scan (konfigurasi via admin)
  - 🏦 **Transfer Bank** - Tampilkan nomor rekening dengan tombol copy
- Tombol quick cash untuk pembayaran cepat
- Stok berkurang otomatis setelah transaksi
- 🔜 *Fitur cetak struk disiapkan untuk pengembangan masa depan*

### 📦 Manajemen Produk (Admin)
- Tambah, edit, hapus produk
- Kode produk unik (angka, huruf, atau kombinasi)
- Set nama, harga jual, dan stok
- Kategori produk

### 📊 Manajemen Stok (Admin)
- Penambahan stok masuk
- Koreksi stok (untuk penyesuaian dengan stok fisik)
- Peringatan stok menipis (< 10)
- Riwayat perubahan stok lengkap

### 📈 Laporan Penjualan
- Laporan harian/mingguan/bulanan
- Total omzet dan transaksi
- **📊 Breakdown Metode Pembayaran** - Kolom terpisah untuk Tunai, QRIS, Transfer
- Produk terlaris
- Penjualan per kategori
- **📥 Download Excel/CSV** - Export laporan dalam format spreadsheet
- Filter cepat: Hari ini, 7 hari terakhir, Bulan ini, Bulan lalu

### 📒 Tutup Buku Bulanan (Admin)
- Rekap data penjualan per bulan
- Simpan arsip omzet, transaksi, dan item terjual
- Pisah laporan tunai vs non-tunai (QRIS + Transfer)
- Download rekap periode yang sudah ditutup
- Riwayat tutup buku lengkap
- **⏰ Reminder** di dashboard jika bulan sebelumnya belum ditutup

### ⚙️ Pengaturan (Admin)

#### 🏪 Pengaturan Umum
- **Logo Aplikasi** - Upload logo untuk navbar dan halaman login
- **Favicon** - Ikon tab browser
- **Nama Aplikasi** - Kustomisasi nama toko/koperasi

#### 💳 Pengaturan Pembayaran Non-Tunai
- **QRIS** - Upload gambar QR Code, set nama QRIS
- **Transfer Bank** - Set nama bank, nomor rekening, atas nama
- Aktif/nonaktif metode pembayaran sesuai kebutuhan

### 👥 Manajemen Pengguna (Admin)
- Role-based access (Admin & Kasir)
- **Admin**: Akses penuh ke seluruh fitur
- **Kasir**: Akses POS, transaksi, dan laporan (bisa download)

## 🔄 Flow Aplikasi

```
┌─────────────┐
│   LOGIN     │ ◄── Halaman awal (tidak ada akses tanpa login)
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  DASHBOARD  │ ◄── Statistik hari ini, quick actions, reminder tutup buku
└──────┬──────┘
       │
       ├────────────────┬────────────────┬────────────────┐
       ▼                ▼                ▼                ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│     POS     │  │   PRODUK    │  │  PENGATURAN │  │   LAPORAN   │
│   (Kasir)   │  │   (Admin)   │  │   (Admin)   │  │             │
└──────┬──────┘  └─────────────┘  └─────────────┘  └─────────────┘
       │
       ▼
┌─────────────┐
│  TRANSAKSI  │ ◄── Simpan → Stok berkurang otomatis
│   BERHASIL  │     → Riwayat stok tercatat
└─────────────┘
```

### Flow Transaksi POS:
1. **Cari Produk** → Ketik kode/nama → Pilih dari suggestion
2. **Input Jumlah** → Atur qty dengan +/- atau ketik langsung
3. **Tambah ke Keranjang** → Validasi stok otomatis
4. **Pilih Pembayaran** → Tunai / QRIS / Transfer Bank
5. **Simpan Transaksi** → Stok berkurang → Riwayat tercatat

## 🚀 Instalasi

### Persyaratan Sistem
- PHP 7.4 atau lebih tinggi
- MySQL 5.7+ / MariaDB 10.3+
- Web Server (Apache/Nginx/Laragon)
- Browser modern (Chrome, Firefox, Edge, Safari)

### Langkah Instalasi

1. **Clone atau download** repository ini ke folder web server:
   ```bash
   git clone https://github.com/deffan10/POS-Koperasi-Sederhana.git
   # atau download ZIP dan extract ke folder web server
   ```

2. **Import database**:
   ```bash
   # Via command line
   mysql -u root -p < database.sql
   
   # Atau via phpMyAdmin:
   # 1. Buat database baru: pos_koperasi
   # 2. Import file database.sql
   ```

3. **Konfigurasi database** (jika diperlukan):
   
   Edit file `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'pos_koperasi');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Sesuaikan dengan password MySQL Anda
   ```

4. **Set folder permissions** (untuk upload logo/QRIS):
   ```bash
   chmod 755 assets/uploads/
   ```

5. **Akses aplikasi**:
   ```
   http://localhost/POS-Koperasi-Sederhana/
   ```

### 🔑 Akun Default

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `password` |
| Kasir | `kasir` | `password` |

> ⚠️ **PENTING**: Segera ubah password setelah login pertama melalui menu **Pengaturan → Pengguna**!

## 📁 Struktur Folder

```
POS-Koperasi-Sederhana/
├── api/                    # REST API endpoints
│   ├── export.php          # Export laporan Excel/CSV
│   ├── products.php        # API pencarian produk
│   ├── report-summary.php  # API ringkasan laporan
│   └── transactions.php    # API simpan & detail transaksi
├── assets/
│   ├── css/
│   │   └── style.css       # Custom stylesheet
│   ├── js/
│   │   ├── app.js          # JavaScript global
│   │   └── pos.js          # JavaScript khusus halaman POS
│   └── uploads/            # Upload folder (logo, favicon, QRIS)
│       └── .htaccess       # Security: hanya izinkan gambar
├── config/
│   └── database.php        # Konfigurasi database & helper functions
├── includes/
│   ├── auth.php            # Sistem autentikasi & session
│   ├── header.php          # Template header & navbar
│   └── footer.php          # Template footer
├── categories.php          # CRUD kategori (Admin)
├── closing.php             # Tutup buku bulanan (Admin)
├── dashboard.php           # Dashboard & statistik
├── database.sql            # Schema database
├── index.php               # Halaman login
├── logout.php              # Handler logout
├── pos.php                 # Halaman kasir/POS
├── products.php            # CRUD produk (Admin)
├── reports.php             # Laporan penjualan + Download
├── settings.php            # Pengaturan pembayaran non-tunai (Admin)
├── settings-general.php    # Pengaturan umum - logo, favicon (Admin)
├── stock.php               # Manajemen stok (Admin)
├── transactions.php        # Riwayat transaksi
├── users.php               # CRUD pengguna (Admin)
├── .htaccess               # Apache security config
└── README.md
```

## 🗄️ Struktur Database

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│    users     │     │   kategori   │     │    produk    │
├──────────────┤     ├──────────────┤     ├──────────────┤
│ id           │     │ id           │     │ id           │
│ username     │     │ nama_kategori│◄────│ kategori_id  │
│ password     │     │ created_at   │     │ kode_produk  │
│ nama_lengkap │     └──────────────┘     │ nama_produk  │
│ role         │                          │ harga_jual   │
│ status       │                          │ stok         │
└──────┬───────┘                          │ status       │
       │                                  └──────┬───────┘
       │                                         │
       │     ┌──────────────┐                    │
       │     │  transaksi   │                    │
       │     ├──────────────┤                    │
       ├────►│ user_id      │                    │
       │     │ no_transaksi │                    │
       │     │ total_harga  │     ┌──────────────┴───┐
       │     │ metode_bayar │     │ detail_transaksi │
       │     │ (tunai/qris/ │     ├──────────────────┤
       │     │  transfer)   │     │ transaksi_id     │◄──┘
       │     └──────┬───────┘     │ produk_id        │
       │            │             │ jumlah           │
       │            └────────────►│ subtotal         │
       │                          └──────────────────┘
       │                                   │
       │            ┌──────────────────────┘
       │            │
       │     ┌──────┴───────┐     ┌──────────────┐
       │     │ riwayat_stok │     │  tutup_buku  │
       │     ├──────────────┤     ├──────────────┤
       │     │ produk_id    │     │ bulan        │
       │     │ jenis        │     │ tahun        │
       │     │ jumlah       │     │ total_omzet  │
       │     │ stok_sebelum │     │ total_transaksi│
       │     │ stok_sesudah │     │ total_tunai  │
       └────►│ user_id      │◄────│ user_id      │
             └──────────────┘     └──────────────┘

┌──────────────┐
│   settings   │  ◄── Tabel baru untuk konfigurasi
├──────────────┤
│ setting_key  │      • qris_aktif, qris_nama, qris_image
│ setting_value│      • transfer_aktif, bank_nama, bank_rekening
│ created_at   │      • app_name, app_logo, app_favicon
└──────────────┘
```

## 📱 Menu Navigasi

### Admin
```
┌─────────────┬─────────────┬─────────────┬─────────────┬─────────────┐
│  Dashboard  │    Kasir    │   Produk ▼  │  Laporan ▼  │ Pengaturan ▼│
└─────────────┴─────────────┼─────────────┼─────────────┼─────────────┘
                            │ • Produk    │ • Laporan   │ • Pengguna
                            │ • Stok      │ • Transaksi │ • Umum (Logo)
                            │ • Kategori  │ • Tutup Buku│ • Non-Tunai
                            └─────────────┴─────────────┴─────────────
```

### Kasir
```
┌─────────────┬─────────────┬─────────────┐
│  Dashboard  │    Kasir    │  Laporan ▼  │
└─────────────┴─────────────┼─────────────┘
                            │ • Laporan
                            │ • Transaksi
                            └─────────────
```

## ⌨️ Keyboard Shortcuts

| Shortcut | Fungsi |
|----------|--------|
| `F2` | Focus ke kolom pencarian produk |
| `F9` | Simpan transaksi |
| `Enter` | Tambah produk ke keranjang (saat di input jumlah) |
| `↑` `↓` | Navigasi suggestion produk |
| `Escape` | Tutup dropdown / clear focus |

## 🔒 Keamanan

Aplikasi ini dilengkapi dengan berbagai lapisan keamanan:

### Proteksi Data
- ✅ **Password Hashing** - Menggunakan bcrypt (industry standard)
- ✅ **Prepared Statements** - Mencegah SQL Injection
- ✅ **XSS Prevention** - Escape output dengan `htmlspecialchars`

### Proteksi Session
- ✅ **HTTP-Only Cookies** - Session cookie tidak bisa diakses JavaScript
- ✅ **SameSite Strict** - Mencegah CSRF via cookie
- ✅ **Session Regeneration** - Token diperbarui setiap 30 menit
- ✅ **Secure Session Settings** - Menggunakan cookies only

### Proteksi Akses
- ✅ **CSRF Protection** - Token validasi pada semua form POST
- ✅ **Brute Force Protection** - 5 percobaan gagal = blokir 15 menit
- ✅ **Role-Based Access Control** - Admin & Kasir dengan hak akses berbeda

### Security Headers
- ✅ **X-Content-Type-Options: nosniff**
- ✅ **X-Frame-Options: SAMEORIGIN** - Mencegah clickjacking
- ✅ **X-XSS-Protection: 1; mode=block**
- ✅ **Referrer-Policy: strict-origin-when-cross-origin**

### File Upload Security
- ✅ **Validasi tipe file** - Hanya JPG, PNG, GIF, ICO
- ✅ **Maksimal ukuran** - 2MB per file
- ✅ **.htaccess protection** - Folder uploads hanya izinkan gambar

### Best Practices
- ✅ Error tidak ditampilkan ke user (production mode)
- ✅ Error logging untuk debugging
- ✅ Input validation pada semua form

## 📱 Responsif

Aplikasi dirancang responsif untuk:
- 💻 Desktop (1920px - 1024px)
- 📱 Tablet (1024px - 768px)
- 📱 Mobile (768px - 320px)

## 🔮 Roadmap Pengembangan

- [x] ~~Multi metode pembayaran (QRIS, Transfer)~~ ✅
- [x] ~~Export laporan ke Excel/CSV~~ ✅
- [x] ~~Kustomisasi logo & nama aplikasi~~ ✅
- [x] ~~Menu dropdown terorganisir~~ ✅
- [ ] 🖨️ Cetak struk (thermal printer support)
- [ ] 📷 Barcode scanner support
- [ ] 💰 Harga modal dan perhitungan laba
- [ ] 🏪 Multi-outlet support
- [ ] 💾 Backup database otomatis
- [ ] 📧 Notifikasi stok menipis via email/WhatsApp
- [ ] 🌙 Dark mode

## 🐛 Known Issues

- Belum ada fitur cetak struk thermal printer

## 🤝 Kontribusi

Kontribusi dalam bentuk apapun sangat diterima:
1. Fork repository
2. Buat branch baru (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📝 Lisensi

Distributed under the MIT License. Lihat `LICENSE` untuk informasi lebih lanjut.

## 📞 Kontak & Support

- 📧 Issues: [GitHub Issues](https://github.com/deffan10/POS-Koperasi-Sederhana/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/deffan10/POS-Koperasi-Sederhana/discussions)

---

<p align="center">
  <b>POS Koperasi Al-Farmasi</b><br>
  Dibuat dengan ❤️ untuk kemudahan usaha warung koperasi
</p>
