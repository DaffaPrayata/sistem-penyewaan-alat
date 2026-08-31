# Sistem Informasi Penyewaan Alat

Studi kasus contoh untuk tugas "Pengembangan Sistem Informasi Berbasis Web" — PHP native + MySQL.
Ganti nama entitas/tema sesuai kebutuhan Anda; struktur dan alurnya bisa dipakai untuk tema apa pun
(perpustakaan, klinik, inventaris, dsb) karena polanya sama: data utama, data pendukung, transaksi, penyelesaian transaksi.

## 1. Instalasi

1. Salin folder `sistem-penyewaan-alat/` ke `htdocs/` (XAMPP) atau `www/` (Laragon/WAMP).
2. Buka phpMyAdmin, import file **`database.sql`** — ini akan otomatis:
   - Membuat database `sistem_penyewaan_alat`
   - Membuat seluruh tabel + trigger
   - Mengisi data awal (2 akun user, kategori, alat, dan pelanggan contoh)
3. Buka `config/database.php`, sesuaikan `$user` dan `$pass` dengan MySQL Anda (default XAMPP: user `root`, password kosong — biasanya tidak perlu diubah).
4. Buka `config/konstanta.php`, sesuaikan `BASE_URL` dengan lokasi folder project Anda.
5. Akses `http://localhost/sistem-penyewaan-alat/` di browser.

## 2. Akun Default

| Role     | Username  | Password  |
|----------|-----------|-----------|
| admin    | admin     | admin123  |
| petugas  | petugas1  | admin123  |

**Wajib diganti** setelah login pertama (fitur ganti password belum dibuat di draf ini — bisa ditambahkan sebagai pengembangan lanjutan di modul `auth`).

## 3. Alur Pemakaian

1. **Login** sebagai admin atau petugas.
2. **Data Alat** — admin menambah/mengubah alat & kategori (`modules/alat/`).
3. **Data Pelanggan** — admin/petugas mendaftarkan pelanggan (`modules/pelanggan/`).
4. **Transaksi Sewa Baru** — pilih pelanggan, pilih alat + jumlah, sistem otomatis:
   - Mengecek stok tersedia (dengan row locking `FOR UPDATE` agar aman dari race condition)
   - Menghitung total biaya = harga/hari × jumlah × durasi sewa
   - Mengurangi `stok_tersedia` lewat trigger database `trg_kurangi_stok`
5. **Pengembalian** — pilih transaksi yang masih berjalan, input tanggal kembali aktual:
   - Sistem otomatis menghitung hari terlambat & denda (lihat `PERSEN_DENDA_PER_HARI` di `config/konstanta.php`)
   - Trigger `trg_kembalikan_stok` otomatis mengembalikan stok dan mengubah status transaksi jadi `selesai`
6. **Dashboard** — ringkasan alat aktif, stok tersedia, transaksi berjalan/terlambat, dan pendapatan bulan berjalan.
7. **Cetak PDF** — laporan transaksi 30 hari terakhir (butuh library FPDF, lihat komentar di `modules/laporan/cetak_pdf.php`).

## 4. Keamanan yang Sudah Diterapkan

- Semua query memakai **PDO prepared statement** dengan `EMULATE_PREPARES => false` (anti SQL Injection).
- Semua output ke HTML melewati fungsi `h()` (alias `htmlspecialchars`) untuk mencegah XSS.
- Password disimpan dengan `password_hash()` / diverifikasi dengan `password_verify()`, tidak pernah plain text.
- Role-based access lewat `cek_role([...])` di setiap halaman sensitif (contoh: hanya admin yang bisa hapus data alat).
- Transaksi database (`beginTransaction`/`commit`/`rollBack`) + `SELECT ... FOR UPDATE` dipakai di proses sewa & pengembalian agar stok tidak salah hitung jika diakses bersamaan.

## 5. Struktur Folder

Lihat `struktur_folder.md` (dokumen terpisah) untuk penjelasan lengkap tiap folder.

## 6. Pengembangan Lanjutan yang Bisa Ditambahkan

- Halaman ganti password & kelola user (khusus admin).
- Validasi lebih ketat di sisi klien (JS) sebelum submit.
- Export laporan ke Excel selain PDF.
- Notifikasi transaksi yang mendekati/melewati jatuh tempo (email/WhatsApp gateway).
- Riwayat log aktivitas (audit trail) untuk setiap perubahan data.
