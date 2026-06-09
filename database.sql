-- Bioskop OOP PHP Native + MySQL
-- Import ini ke phpMyAdmin (pastikan database kosong)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;
START TRANSACTION;

CREATE DATABASE IF NOT EXISTS bioskopp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bioskopp;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','operator','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pelanggan (
  pelanggan_id VARCHAR(4) PRIMARY KEY,
  user_id INT NULL,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pelanggan_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS film (
  film_id VARCHAR(4) PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  genre VARCHAR(80) NOT NULL,
  durasi_menit INT NOT NULL,
  rating_usia VARCHAR(10) NOT NULL,
  sinopsis TEXT NULL,
  poster VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS studio (
  studio_id VARCHAR(5) PRIMARY KEY,
  nama VARCHAR(80) NOT NULL,
  kapasitas INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jadwal_tayang (
  jadwal_id VARCHAR(4) PRIMARY KEY,
  film_id VARCHAR(4) NOT NULL,
  studio_id VARCHAR(5) NOT NULL,
  tanggal DATE NOT NULL,
  jam TIME NOT NULL,
  harga INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_jadwal_film FOREIGN KEY (film_id) REFERENCES film(film_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_jadwal_studio FOREIGN KEY (studio_id) REFERENCES studio(studio_id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transaksi (
  transaksi_id VARCHAR(4) PRIMARY KEY,
  user_id INT NOT NULL,
  pelanggan_id VARCHAR(4) NOT NULL,
  jadwal_id VARCHAR(4) NOT NULL,
  jumlah_tiket INT NOT NULL,
  metode_pembayaran ENUM('Tunai','QRIS','Transfer Bank','Kartu Debit','Kartu Kredit') NOT NULL,
  status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  total_harga INT NOT NULL,
  catatan VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_trans_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_trans_pelanggan FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(pelanggan_id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_trans_jadwal FOREIGN KEY (jadwal_id) REFERENCES jadwal_tayang(jadwal_id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kursi_terpesan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jadwal_id VARCHAR(4) NOT NULL,
  transaksi_id VARCHAR(4) NOT NULL,
  nomor_kursi VARCHAR(5) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_kursi_jadwal FOREIGN KEY (jadwal_id) REFERENCES jadwal_tayang(jadwal_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_kursi_transaksi FOREIGN KEY (transaksi_id) REFERENCES transaksi(transaksi_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY unique_seat (jadwal_id, nomor_kursi)
) ENGINE=InnoDB;

-- =====================
-- SEED DATA
-- =====================
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM kursi_terpesan;
DELETE FROM transaksi;
DELETE FROM jadwal_tayang;
DELETE FROM pelanggan;
DELETE FROM studio;
DELETE FROM film;
DELETE FROM users;
SET FOREIGN_KEY_CHECKS = 1;

-- Users
INSERT INTO users(username, password_hash, role) VALUES
('admin',    SHA2('admin123',    256), 'admin'),
('operator', SHA2('operator123', 256), 'operator'),
('demo',     SHA2('demo123',     256), 'user'),
('user1',    SHA2('user123',     256), 'user'),
('user2',    SHA2('user123',     256), 'user'),
('user3',    SHA2('user123',     256), 'user'),
('user4',    SHA2('user123',     256), 'user'),
('user5',    SHA2('user123',     256), 'user'),
('user6',    SHA2('user123',     256), 'user'),
('user7',    SHA2('user123',     256), 'user'),
('user8',    SHA2('user123',     256), 'user'),
('user9',    SHA2('user123',     256), 'user'),
('user10',   SHA2('user123',     256), 'user'),
('user11',   SHA2('user123',     256), 'user'),
('user12',   SHA2('user123',     256), 'user'),
('user13',   SHA2('user123',     256), 'user'),
('user14',   SHA2('user123',     256), 'user'),
('user15',   SHA2('user123',     256), 'user');

-- Pelanggan (ID eksplisit, bukan NULL)
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone) 
VALUES('P000', NULL, 'Walk-in Customer', '-', '-');
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone) 
SELECT 'P001', u.id, 'Demo User',       'demo@example.com',   '081234567890' FROM users u WHERE u.username='demo';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P002', u.id, 'User Satu',       'user1@example.com',  '08120000001'  FROM users u WHERE u.username='user1';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P003', u.id, 'User Dua',        'user2@example.com',  '08120000002'  FROM users u WHERE u.username='user2';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P004', u.id, 'User Tiga',       'user3@example.com',  '08120000003'  FROM users u WHERE u.username='user3';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P005', u.id, 'User Empat',      'user4@example.com',  '08120000004'  FROM users u WHERE u.username='user4';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P006', u.id, 'User Lima',       'user5@example.com',  '08120000005'  FROM users u WHERE u.username='user5';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P007', u.id, 'User Enam',       'user6@example.com',  '08120000006'  FROM users u WHERE u.username='user6';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P008', u.id, 'User Tujuh',      'user7@example.com',  '08120000007'  FROM users u WHERE u.username='user7';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P009', u.id, 'User Delapan',    'user8@example.com',  '08120000008'  FROM users u WHERE u.username='user8';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P010', u.id, 'User Sembilan',   'user9@example.com',  '08120000009'  FROM users u WHERE u.username='user9';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P011', u.id, 'User Sepuluh',    'user10@example.com', '08120000010'  FROM users u WHERE u.username='user10';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P012', u.id, 'User Sebelas',    'user11@example.com', '08120000011'  FROM users u WHERE u.username='user11';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P013', u.id, 'User Duabelas',   'user12@example.com', '08120000012'  FROM users u WHERE u.username='user12';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P014', u.id, 'User Tigabelas',  'user13@example.com', '08120000013'  FROM users u WHERE u.username='user13';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P015', u.id, 'User Empatbelas', 'user14@example.com', '08120000014'  FROM users u WHERE u.username='user14';
INSERT INTO pelanggan(pelanggan_id, user_id, nama, email, phone)
SELECT 'P016', u.id, 'User Limabelas',  'user15@example.com', '08120000015'  FROM users u WHERE u.username='user15';

-- Film (15)
INSERT INTO film(film_id, judul, genre, durasi_menit, rating_usia, sinopsis, poster) VALUES
('F001','Inception',                 'Sci-Fi & Thriller',      148,'13+','Seorang pencuri yang bekerja dalam mimpi harus menanamkan sebuah ide ke dalam pikiran targetnya.','inception.jpg'),
('F002','Interstellar',              'Sci-Fi & Adventure',     169,'7+', 'Perjalanan melintasi ruang dan waktu untuk menyelamatkan umat manusia dari kepunahan.','interstellar.jpg'),
('F003','The Dark Knight',           'Action & Crime',         152,'13+','Gotham menghadapi teror Joker yang tak terduga, menguji batas kepahlawanan Batman.','batman.jpg'),
('F004','Joker',                     'Crime & Drama',          122,'17+','Seorang komedian gagal perlahan berubah menjadi simbol kekacauan kota.','joker.jpg'),
('F005','Fight Club',                'Drama & Thriller',       139,'17+','Seorang pekerja kantoran yang frustasi mendirikan klub perkelahian rahasia.','fightclub.jpg'),
('F006','The Matrix',                'Sci-Fi',                 136,'13+','Seorang hacker menemukan bahwa realitas yang ia kenal hanyalah simulasi komputer.','matrix.jpg'),
('F007','Dune: Part One',            'Epic Sci-Fi',            155,'13+','Seorang pemuda terpilih memimpin pemberontakan di planet gurun penghasil rempah terkuat.','dune.jpg'),
('F008','Oppenheimer',               'Biografi & Drama',       180,'13+','Kisah ilmuwan J. Robert Oppenheimer yang memimpin proyek pembuatan bom atom pertama.','oppenheimer.jpg'),
('F009','Gladiator',                 'Action & Historical',    155,'13+','Jenderal Romawi yang dikhianati berjuang sebagai gladiator untuk membalas dendam.','gladiator.jpg'),
('F010','The Godfather',             'Crime & Drama',          175,'17+','Kisah dinasti kejahatan keluarga Corleone yang menguasai dunia bawah tanah New York.','godfather.jpg'),
('F011','Parasite',                  'Thriller & Drama',       132,'17+','Sebuah keluarga miskin menyusup ke kehidupan keluarga kaya dengan cara yang berbahaya.','parasite.jpg'),
('F012','Pulp Fiction',              'Crime & Comedy',         154,'17+','Kisah silang penjahat, petinju, dan pasangan kriminal dalam sudut gelap Los Angeles.','pulp.jpg'),
('F013','The Shawshank Redemption',  'Drama',                  142,'7+', 'Seorang bankir yang dipenjara secara tidak adil berjuang mempertahankan harapan selama 19 tahun.','shawshank.jpg'),
('F014','Titanic',                   'Romance & Drama',        195,'13+','Kisah cinta tragis antara dua penumpang dari kelas berbeda di kapal Titanic yang karam.','titanic.jpg'),
('F015','Spider-Man: No Way Home',   'Action & Superhero',     148,'13+','Peter Parker membuka multiverse dan menghadapi musuh dari lintas semesta.','spiderman.jpg');

-- Studio (15)
INSERT INTO studio(studio_id, nama, kapasitas) VALUES
('ST001','Studio 1 - Black Panther',  120),
('ST002','Studio 2 - Red Velvet',      90),
('ST003','Studio 3 - Neon Cinema',    110),
('ST004','Studio 4 - Skyline Lux',    130),
('ST005','Studio 5 - Dolby Dark',     150),
('ST006','Studio 6 - Ultra Recliner',  80),
('ST007','Studio 7 - IMAX Sound',     200),
('ST008','Studio 8 - Gold Class',      70),
('ST009','Studio 9 - Classic Noir',   100),
('ST010','Studio 10 - Sapphire',      140),
('ST011','Studio 11 - Ruby Theatre',   95),
('ST012','Studio 12 - Emerald Hall',  105),
('ST013','Studio 13 - Platinum',      160),
('ST014','Studio 14 - Signature',      85),
('ST015','Studio 15 - Prime View',    115);

-- Jadwal: CURDATE() agar selalu relevan hari ini & beberapa hari ke depan
SET @t = CURDATE();
INSERT INTO jadwal_tayang(jadwal_id, film_id, studio_id, tanggal, jam, harga) VALUES
('J001','F001','ST001', @t,                            '10:00:00', 45000),
('J002','F002','ST002', @t,                            '12:30:00', 50000),
('J003','F003','ST003', @t,                            '15:00:00', 52000),
('J004','F004','ST004', @t,                            '17:30:00', 55000),
('J005','F005','ST005', @t,                            '20:00:00', 60000),
('J006','F006','ST006', DATE_ADD(@t, INTERVAL 1 DAY),  '10:00:00', 43000),
('J007','F007','ST007', DATE_ADD(@t, INTERVAL 1 DAY),  '13:15:00', 48000),
('J008','F008','ST008', DATE_ADD(@t, INTERVAL 1 DAY),  '16:45:00', 53000),
('J009','F009','ST009', DATE_ADD(@t, INTERVAL 2 DAY),  '12:30:00', 47000),
('J010','F010','ST010', DATE_ADD(@t, INTERVAL 2 DAY),  '15:10:00', 52000),
('J011','F011','ST011', DATE_ADD(@t, INTERVAL 3 DAY),  '11:20:00', 45000),
('J012','F012','ST012', DATE_ADD(@t, INTERVAL 3 DAY),  '14:00:00', 49000),
('J013','F013','ST013', DATE_ADD(@t, INTERVAL 4 DAY),  '16:00:00', 51000),
('J014','F014','ST014', DATE_ADD(@t, INTERVAL 4 DAY),  '18:30:00', 54000),
('J015','F015','ST015', DATE_ADD(@t, INTERVAL 5 DAY),  '20:00:00', 59000);

-- Transaksi dummy untuk user DEMO (P001) agar riwayat langsung terlihat saat login
-- T001 confirmed, T002 pending, T003 cancelled
INSERT INTO transaksi(transaksi_id, user_id, pelanggan_id, jadwal_id, jumlah_tiket, metode_pembayaran, status, total_harga, catatan)
SELECT 'T001', u.id, 'P001', 'J001', 2, 'QRIS',         'confirmed', 90000,  'Booking online' FROM users u WHERE u.username='demo';
INSERT INTO transaksi(transaksi_id, user_id, pelanggan_id, jadwal_id, jumlah_tiket, metode_pembayaran, status, total_harga, catatan)
SELECT 'T002', u.id, 'P001', 'J002', 3, 'Transfer Bank', 'pending',   150000, 'Booking online' FROM users u WHERE u.username='demo';
INSERT INTO transaksi(transaksi_id, user_id, pelanggan_id, jadwal_id, jumlah_tiket, metode_pembayaran, status, total_harga, catatan)
SELECT 'T003', u.id, 'P001', 'J003', 1, 'Tunai',         'cancelled', 52000,  'Booking online' FROM users u WHERE u.username='demo';

-- Transaksi tambahan untuk variasi operator/admin view (user1..user5)
INSERT INTO transaksi(transaksi_id, user_id, pelanggan_id, jadwal_id, jumlah_tiket, metode_pembayaran, status, total_harga, catatan)
SELECT 'T004', u.id, 'P002', 'J004', 2, 'QRIS',         'pending',   110000, 'Booking online' FROM users u WHERE u.username='user1';
INSERT INTO transaksi(transaksi_id, user_id, pelanggan_id, jadwal_id, jumlah_tiket, metode_pembayaran, status, total_harga, catatan)
SELECT 'T005', u.id, 'P003', 'J005', 4, 'Kartu Kredit', 'confirmed', 240000, 'Booking online' FROM users u WHERE u.username='user2';
INSERT INTO transaksi(transaksi_id, user_id, pelanggan_id, jadwal_id, jumlah_tiket, metode_pembayaran, status, total_harga, catatan)
SELECT 'T006', u.id, 'P004', 'J006', 1, 'Tunai',         'pending',    43000, 'Booking online' FROM users u WHERE u.username='user3';
INSERT INTO transaksi(transaksi_id, user_id, pelanggan_id, jadwal_id, jumlah_tiket, metode_pembayaran, status, total_harga, catatan)
SELECT 'T007', u.id, 'P005', 'J007', 2, 'QRIS',         'pending',    96000, 'Booking online' FROM users u WHERE u.username='user4';
INSERT INTO transaksi(transaksi_id, user_id, pelanggan_id, jadwal_id, jumlah_tiket, metode_pembayaran, status, total_harga, catatan)
SELECT 'T008', u.id, 'P006', 'J008', 3, 'Transfer Bank', 'confirmed', 159000, 'Booking online' FROM users u WHERE u.username='user5';

UPDATE transaksi SET updated_at=NOW() WHERE status IN ('confirmed','cancelled');

COMMIT;
