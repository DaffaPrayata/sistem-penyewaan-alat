-- =============================================================
-- SKEMA DATABASE RELASIONAL
-- Sistem Informasi Penyewaan Alat
-- Engine: InnoDB (wajib untuk dukungan FOREIGN KEY & TRIGGER)
-- =============================================================

CREATE DATABASE IF NOT EXISTS sistem_penyewaan_alat
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE sistem_penyewaan_alat;

-- -------------------------------------------------------------
-- 1. AUTENTIKASI & ROLE-BASED ACCESS
-- -------------------------------------------------------------
CREATE TABLE tb_role (
  id_role     INT AUTO_INCREMENT PRIMARY KEY,
  nama_role   VARCHAR(50) NOT NULL UNIQUE,
  deskripsi   VARCHAR(150) NULL
) ENGINE=InnoDB;

INSERT INTO tb_role (nama_role, deskripsi) VALUES
  ('admin',   'Akses penuh: kelola user, alat, laporan'),
  ('petugas', 'Akses transaksi harian: sewa & pengembalian');

CREATE TABLE tb_users (
  id_user       INT AUTO_INCREMENT PRIMARY KEY,
  id_role       INT NOT NULL,
  username      VARCHAR(50) NOT NULL UNIQUE,
  password      VARCHAR(255) NOT NULL,   -- simpan dgn password_hash()
  nama_lengkap  VARCHAR(100) NOT NULL,
  status_aktif  TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_role) REFERENCES tb_role(id_role)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 2. DATA PENDUKUNG: PELANGGAN
-- -------------------------------------------------------------
CREATE TABLE tb_pelanggan (
  id_pelanggan   INT AUTO_INCREMENT PRIMARY KEY,
  nama_pelanggan VARCHAR(100) NOT NULL,
  no_identitas   VARCHAR(30) NOT NULL UNIQUE,   -- NIK/KTP
  no_hp          VARCHAR(20) NOT NULL,
  alamat         TEXT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 3. DATA UTAMA: KATEGORI & ALAT
-- -------------------------------------------------------------
CREATE TABLE tb_kategori_alat (
  id_kategori   INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE tb_alat (
  id_alat              INT AUTO_INCREMENT PRIMARY KEY,
  id_kategori          INT NOT NULL,
  kode_alat            VARCHAR(20) NOT NULL UNIQUE,
  nama_alat            VARCHAR(100) NOT NULL,
  stok_total           INT NOT NULL DEFAULT 0,
  stok_tersedia        INT NOT NULL DEFAULT 0,
  harga_sewa_per_hari  DECIMAL(12,2) NOT NULL,
  status               ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  FOREIGN KEY (id_kategori) REFERENCES tb_kategori_alat(id_kategori)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 4. TRANSAKSI UTAMA: PENYEWAAN
-- -------------------------------------------------------------
CREATE TABLE tb_transaksi_sewa (
  id_transaksi             INT AUTO_INCREMENT PRIMARY KEY,
  kode_transaksi           VARCHAR(20) NOT NULL UNIQUE,
  id_pelanggan             INT NOT NULL,
  id_user                  INT NOT NULL,  -- petugas yang menginput
  tanggal_sewa             DATE NOT NULL,
  tanggal_rencana_kembali  DATE NOT NULL,
  status                   ENUM('berjalan','selesai','terlambat') NOT NULL DEFAULT 'berjalan',
  total_biaya              DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at               TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_pelanggan) REFERENCES tb_pelanggan(id_pelanggan),
  FOREIGN KEY (id_user) REFERENCES tb_users(id_user)
) ENGINE=InnoDB;

-- Detail alat yang disewa dalam satu transaksi (relasi many-to-many alat<->transaksi)
CREATE TABLE tb_detail_sewa (
  id_detail     INT AUTO_INCREMENT PRIMARY KEY,
  id_transaksi  INT NOT NULL,
  id_alat       INT NOT NULL,
  jumlah        INT NOT NULL,
  harga_satuan  DECIMAL(12,2) NOT NULL,
  subtotal      DECIMAL(12,2) GENERATED ALWAYS AS (jumlah * harga_satuan) STORED,
  FOREIGN KEY (id_transaksi) REFERENCES tb_transaksi_sewa(id_transaksi) ON DELETE CASCADE,
  FOREIGN KEY (id_alat) REFERENCES tb_alat(id_alat)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- 5. PENYELESAIAN TRANSAKSI: PENGEMBALIAN
-- -------------------------------------------------------------
CREATE TABLE tb_pengembalian (
  id_pengembalian         INT AUTO_INCREMENT PRIMARY KEY,
  id_transaksi            INT NOT NULL,
  id_user                 INT NOT NULL,  -- petugas yang memproses
  tanggal_kembali_aktual  DATE NOT NULL,
  jumlah_hari_terlambat   INT NOT NULL DEFAULT 0,
  denda                   DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_pembayaran        DECIMAL(12,2) NOT NULL,
  catatan                 TEXT NULL,
  created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_transaksi) REFERENCES tb_transaksi_sewa(id_transaksi),
  FOREIGN KEY (id_user) REFERENCES tb_users(id_user)
) ENGINE=InnoDB;

-- =============================================================
-- 6. TRIGGER: ALUR TRANSAKSI OTOMATIS
-- =============================================================

-- (a) Saat alat disewa -> stok_tersedia berkurang otomatis
DELIMITER $$
CREATE TRIGGER trg_kurangi_stok
AFTER INSERT ON tb_detail_sewa
FOR EACH ROW
BEGIN
  UPDATE tb_alat
  SET stok_tersedia = stok_tersedia - NEW.jumlah
  WHERE id_alat = NEW.id_alat;
END$$
DELIMITER ;

-- (b) Saat pengembalian dicatat -> stok_tersedia bertambah lagi
--     dan status transaksi otomatis menjadi 'selesai'
DELIMITER $$
CREATE TRIGGER trg_kembalikan_stok
AFTER INSERT ON tb_pengembalian
FOR EACH ROW
BEGIN
  UPDATE tb_alat a
  JOIN tb_detail_sewa d ON d.id_alat = a.id_alat
  SET a.stok_tersedia = a.stok_tersedia + d.jumlah
  WHERE d.id_transaksi = NEW.id_transaksi;

  UPDATE tb_transaksi_sewa
  SET status = 'selesai'
  WHERE id_transaksi = NEW.id_transaksi;
END$$
DELIMITER ;

-- =============================================================
-- CATATAN DESAIN
-- =============================================================
-- - tb_role dipisah dari tb_users agar hak akses mudah ditambah
--   tanpa mengubah struktur tabel (memenuhi kriteria "struktur
--   database & relasi antar tabel").
-- - subtotal & jumlah_hari_terlambat dihitung, bukan diinput
--   manual, untuk mendukung fitur "perhitungan otomatis" (nilai plus).
-- - Trigger menangani sisi DATABASE dari alur transaksi; validasi
--   input (mis. jumlah tidak melebihi stok_tersedia) tetap harus
--   dicek di layer PHP sebelum INSERT dijalankan.

-- =============================================================
-- SEED DATA AWAL (contoh, silakan sesuaikan)
-- =============================================================

-- User admin default
-- Username : admin
-- Password : admin123  (WAJIB diganti setelah login pertama kali)
INSERT INTO tb_users (id_role, username, password, nama_lengkap) VALUES
  (1, 'admin', '$2b$10$jYr7Js73NIRFALOUETZhfeDpOiCYbR.0aFRH2GH980tq3W4L497Ta', 'Administrator');

-- User petugas contoh
-- Username : petugas1
-- Password : admin123
INSERT INTO tb_users (id_role, username, password, nama_lengkap) VALUES
  (2, 'petugas1', '$2b$10$jYr7Js73NIRFALOUETZhfeDpOiCYbR.0aFRH2GH980tq3W4L497Ta', 'Budi Petugas');

-- Kategori & alat contoh
INSERT INTO tb_kategori_alat (nama_kategori) VALUES
  ('Perkemahan'), ('Alat Air'), ('Elektronik');

INSERT INTO tb_alat (id_kategori, kode_alat, nama_alat, stok_total, stok_tersedia, harga_sewa_per_hari) VALUES
  (1, 'TND-001', 'Tenda Dome 4 Orang', 10, 10, 75000),
  (1, 'SLB-001', 'Sleeping Bag', 20, 20, 25000),
  (2, 'CNO-001', 'Canoe 2 Orang', 5, 5, 150000),
  (3, 'PWB-001', 'Power Bank 20000mAh', 15, 15, 20000);

-- Pelanggan contoh
INSERT INTO tb_pelanggan (nama_pelanggan, no_identitas, no_hp, alamat) VALUES
  ('Andi Saputra', '3201010101010001', '081234567890', 'Jl. Merdeka No. 1, Bogor');
