-- ============================================
-- 1. RESET & DATABASE SETUP
-- ============================================
DROP DATABASE IF EXISTS menu_makanan;
CREATE DATABASE menu_makanan;
USE menu_makanan;

CREATE TABLE menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100),
    bahan VARCHAR(50),
    kategori_menu VARCHAR(50),
    kalori INT
);

-- ============================================
--  FOODIES — Tabel Users
--  (dibuat sebelum menus butuh foreign key ke users)
-- ============================================
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100)        NOT NULL,
    email       VARCHAR(150)        NOT NULL UNIQUE,
    `password`    VARCHAR(255)        NOT NULL,          -- disimpan hashed (password_hash)
    `role`        ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE users CHANGE `password` `password` VARCHAR(255);
ALTER TABLE users CHANGE `role` `role` ENUM('admin','user');

-- Akun admin default  (password: 123456)
INSERT INTO users (nama, email, PASSWORD, ROLE) VALUES
('Admin Foodies', 'admin@foodies.com',
 '$2y$10$THd6fycxsZc.P06KGOKVIOzfwUQfp2pue2rROfEFAMEKd9wW73EoK',
 'admin');

-- Akun user contoh   (password: 123456)
INSERT INTO users (nama, email, PASSWORD, ROLE) VALUES
('Budi Santoso', 'budi@gmail.com',
 '$2y$10$THd6fycxsZc.P06KGOKVIOzfwUQfp2pue2rROfEFAMEKd9wW73EoK',
 'user');

ALTER TABLE menus
ADD created_by INT NULL,
ADD is_premium TINYINT(1) DEFAULT 0,
ADD STATUS ENUM('pending','approved') DEFAULT 'approved';

ALTER TABLE menus
ADD CONSTRAINT fk_menu_user
FOREIGN KEY (created_by)
REFERENCES users(id)
ON DELETE SET NULL;

ALTER TABLE menus
ADD protein DECIMAL(5,2) DEFAULT 0,
ADD karbohidrat DECIMAL(5,2) DEFAULT 0,
ADD lemak DECIMAL(5,2) DEFAULT 0,
ADD gula DECIMAL(5,2) DEFAULT 0,
ADD serat DECIMAL(5,2) DEFAULT 0,
ADD sodium DECIMAL(5,2) DEFAULT 0;

INSERT INTO menus (id, nama, bahan, kategori_menu, kalori) VALUES
(1, 'Pisang Keju', 'manis', 'dessert', 250),
(2, 'Roti Bakar Cokelat', 'manis', 'dessert', 300),
(3, 'Es Susu Kelapa', 'manis', 'dessert', 220),
(4, 'Puding Cokelat', 'manis', 'dessert', 200),
(5, 'Pudding Vanilla', 'manis', 'dessert', 210),
(6, 'Ayam Kecap', 'ayam', 'utama', 400),
(7, 'Telur Dadar Sayur', 'sayuran', 'utama', 250),
(8, 'Mie Goreng', 'daging', 'utama', 450),
(9, 'Nasi Goreng', 'ayam', 'utama', 500),
(10, 'Sup Ayam', 'ayam', 'utama', 300),
(11, 'Salad Ayam', 'ayam', 'diet', 200),
(12, 'Telur Rebus Sayur', 'sayuran', 'diet', 180),
(13, 'Nasi Merah Ayam', 'ayam', 'diet', 350),
(14, 'Oatmeal Pisang', 'manis', 'diet', 220),
(15, 'Roti Gandum Telur', 'sayuran', 'diet', 270),
(16, 'Pempek', 'daging', 'tradisional', 350),
(17, 'Ayam Betutu', 'ayam', 'tradisional', 420),
(18, 'Rawon', 'daging', 'tradisional', 480),
(19, 'Mie Aceh', 'daging', 'tradisional', 500),
(20, 'Gudeg', 'sayuran', 'tradisional', 450),
(21, 'Omelette', 'ayam', 'internasional', 300),
(22, 'Aglio e Olio', 'sayuran', 'internasional', 350),
(23, 'Curry', 'ayam', 'internasional', 400),
(24, 'Sandwich', 'sayuran', 'internasional', 280),
(25, 'Egg Fried Rice', 'daging', 'internasional', 520),
(26, 'Indomie Goreng Special', 'daging', 'instant', 450),
(27, 'Sup Telur Instan', 'ayam', 'instant', 200),
(28, 'Spaghetti Instan', 'sayuran', 'instant', 380),
(29, 'Sandwich Kornet', 'daging', 'instant', 420),
(30, 'Egg Wrap', 'ayam', 'instant', 300);
CREATE TABLE bahan_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT,
    nama_bahan VARCHAR(100),
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
);
CREATE TABLE langkah_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT,
    step_ke INT,
    deskripsi TEXT,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
);

ALTER TABLE langkah_menu
ADD gambar_step TEXT NULL,
ADD video_step TEXT NULL;

INSERT INTO bahan_menu (menu_id, nama_bahan) VALUES
-- 1
(1,'Pisang'),(1,'Keju'),(1,'Susu'),(1,'Mentega'),
-- 2
(2,'Roti'),(2,'Cokelat'),(2,'Mentega'),
-- 3
(3,'Kelapa'),(3,'Susu'),(3,'Gula'),
-- 4
(4,'Cokelat'),(4,'Susu'),(4,'Gula'),
-- 5
(5,'Vanilla'),(5,'Susu'),(5,'Gula'),
-- 6
(6,'Ayam'),(6,'Kecap'),(6,'Bawang'),
-- 7
(7,'Telur'),(7,'Sayur'),(7,'Garam'),
-- 8
(8,'Mie'),(8,'Daging'),(8,'Kecap'),
-- 9
(9,'Nasi'),(9,'Ayam'),(9,'Telur'),
-- 10
(10,'Ayam'),(10,'Air'),(10,'Bumbu'),
-- 11
(11,'Ayam'),(11,'Sayur'),(11,'Saus'),
-- 12
(12,'Telur'),(12,'Sayur'),(12,'Garam'),
-- 13
(13,'Nasi merah'),(13,'Ayam'),(13,'Bumbu'),
-- 14
(14,'Oat'),(14,'Pisang'),(14,'Susu'),
-- 15
(15,'Roti gandum'),(15,'Telur'),(15,'Sayur'),
-- 16
(16,'Ikan'),(16,'Tepung'),(16,'Bumbu'),
-- 17
(17,'Ayam'),(17,'Rempah'),(17,'Bumbu'),
-- 18
(18,'Daging'),(18,'Kluwek'),(18,'Bumbu'),
-- 19
(19,'Mie'),(19,'Daging'),(19,'Rempah'),
-- 20
(20,'Nangka'),(20,'Santan'),(20,'Gula'),
-- 21
(21,'Telur'),(21,'Ayam'),(21,'Keju'),
-- 22
(22,'Spaghetti'),(22,'Minyak'),(22,'Bawang'),
-- 23
(23,'Ayam'),(23,'Curry'),(23,'Santan'),
-- 24
(24,'Roti'),(24,'Sayur'),(24,'Saus'),
-- 25
(25,'Nasi'),(25,'Telur'),(25,'Daging'),
-- 26
(26,'Indomie'),(26,'Daging'),(26,'Telur'),
-- 27
(27,'Telur'),(27,'Air'),(27,'Bumbu'),
-- 28
(28,'Spaghetti'),(28,'Sayur'),(28,'Saus'),
-- 29
(29,'Roti'),(29,'Kornet'),(29,'Saus'),
-- 30
(30,'Telur'),(30,'Ayam'),(30,'Kulit wrap');
INSERT INTO langkah_menu (menu_id, step_ke, deskripsi) VALUES
-- 1
(1,1,'Kupas pisang'),(1,2,'Goreng'),(1,3,'Tambahkan keju'),(1,4,'Tambahkan susu'),(1,5,'Sajikan'),
-- 2
(2,1,'Oles roti'),(2,2,'Isi cokelat'),(2,3,'Panggang'),(2,4,'Sajikan'),
-- 3
(3,1,'Campur bahan'),(3,2,'Aduk'),(3,3,'Dinginkan'),(3,4,'Sajikan'),
-- 4
(4,1,'Campur bahan'),(4,2,'Masak'),(4,3,'Dinginkan'),(4,4,'Sajikan'),
-- 5
(5,1,'Campur bahan'),(5,2,'Masak'),(5,3,'Dinginkan'),(5,4,'Sajikan'),
-- 6`bahan_menu``bahan_menu`
(6,1,'Potong ayam'),(6,2,'Tumis'),(6,3,'Masak kecap'),(6,4,'Sajikan'),
-- 7
(7,1,'Kocok telur'),(7,2,'Tambah sayur'),(7,3,'Goreng'),(7,4,'Sajikan'),
-- 8
(8,1,'Rebus mie'),(8,2,'Tumis'),(8,3,'Campur'),(8,4,'Sajikan'),
-- 9
(9,1,'Tumis'),(9,2,'Masak telur'),(9,3,'Masukkan nasi'),(9,4,'Sajikan'),
-- 10
(10,1,'Rebus ayam'),(10,2,'Tambahkan bumbu'),(10,3,'Masak'),(10,4,'Sajikan'),
-- 11
(11,1,'Potong bahan'),(11,2,'Campur'),(11,3,'Tambahkan saus'),(11,4,'Sajikan'),
-- 12
(12,1,'Rebus telur'),(12,2,'Siapkan sayur'),(12,3,'Campur'),(12,4,'Sajikan'),
-- 13
(13,1,'Masak nasi'),(13,2,'Masak ayam'),(13,3,'Campur'),(13,4,'Sajikan'),
-- 14
(14,1,'Masak oat'),(14,2,'Tambahkan pisang'),(14,3,'Tambahkan susu'),(14,4,'Sajikan'),
-- 15
(15,1,'Panggang roti'),(15,2,'Masak telur'),(15,3,'Tambah sayur'),(15,4,'Sajikan'),
-- 16
(16,1,'Campur bahan'),(16,2,'Bentuk'),(16,3,'Goreng'),(16,4,'Sajikan'),
-- 17
(17,1,'Bumbui ayam'),(17,2,'Masak'),(17,3,'Panggang'),(17,4,'Sajikan'),
-- 18
(18,1,'Rebus daging'),(18,2,'Tambahkan bumbu'),(18,3,'Masak'),(18,4,'Sajikan'),
-- 19
(19,1,'Rebus mie'),(19,2,'Masak daging'),(19,3,'Campur'),(19,4,'Sajikan'),
-- 20
(20,1,'Masak nangka'),(20,2,'Tambahkan santan'),(20,3,'Masak'),(20,4,'Sajikan'),
-- 21
(21,1,'Kocok telur'),(21,2,'Masak'),(21,3,'Tambahkan isi'),(21,4,'Sajikan'),
-- 22
(22,1,'Rebus pasta'),(22,2,'Tumis bawang'),(22,3,'Campur'),(22,4,'Sajikan'),
-- 23
(23,1,'Masak ayam'),(23,2,'Tambahkan curry'),(23,3,'Masak'),(23,4,'Sajikan'),
-- 24
(24,1,'Siapkan roti'),(24,2,'Isi sayur'),(24,3,'Tambahkan saus'),(24,4,'Sajikan'),
-- 25
(25,1,'Tumis'),(25,2,'Masak telur'),(25,3,'Masukkan nasi'),(25,4,'Sajikan'),
-- 26
(26,1,'Rebus mie'),(26,2,'Masak tambahan'),(26,3,'Campur'),(26,4,'Sajikan'),
-- 27
(27,1,'Didihkan air'),(27,2,'Masukkan telur'),(27,3,'Masak'),(27,4,'Sajikan'),
-- 28
(28,1,'Rebus pasta'),(28,2,'Tambahkan saus'),(28,3,'Masak'),(28,4,'Sajikan'),
-- 29
(29,1,'Siapkan roti'),(29,2,'Isi kornet'),(29,3,'Tambahkan saus'),(29,4,'Sajikan'),
-- 30
(30,1,'Masak telur'),(30,2,'Siapkan isi'),(30,3,'Bungkus'),(30,4,'Sajikan');

CREATE TABLE rating (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    menu_id    INT          NOT NULL,
    user_id    INT          NOT NULL,
    nilai      TINYINT      NOT NULL CHECK (nilai BETWEEN 1 AND 5),
    review     TEXT,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_menu (user_id, menu_id)   -- 1 user 1 review per menu
);

-- Tambah kolom gambar di tabel menus
ALTER TABLE menus ADD COLUMN gambar VARCHAR(500);

-- Update setiap row dengan URL gambarnya
UPDATE menus SET gambar = 'https://i.pinimg.com/originals/cc/25/23/cc25236a216e164e3e0bc953af7f7baf.jpg' WHERE id = 1;
UPDATE menus SET gambar = 'https://i.pinimg.com/736x/2f/0c/ae/2f0caee7efde2556b5158d016c97f9e5.jpg' WHERE id = 2;
UPDATE menus SET gambar = 'https://cdn1-production-images-kly.akamaized.net/Cvv41F6jMA1k3yswX4qrJ5Inl4I=/1x86:1000x649/480x270/filters:quality(75):strip_icc():format(webp)/kly-media-production/medias/3424002/original/047782000_1617937793-shutterstock_605036183.jpg' WHERE id = 3;
UPDATE menus SET gambar = 'https://awsimages.detik.net.id/community/media/visual/2023/05/16/resep-puding-cokelat-saus-vanili.jpeg?w=600&q=90' WHERE id = 4;
UPDATE menus SET gambar = 'https://joyfoodsunshine.com/wp-content/uploads/2023/08/homemade-vanilla-pudding-recipe-8.jpg' WHERE id = 5;
UPDATE menus SET gambar = 'https://assets.unileversolutions.com/recipes-v2/255873.jpg' WHERE id = 6;
UPDATE menus SET gambar = 'https://assets.unileversolutions.com/v1/1770120.jpg' WHERE id = 7;
UPDATE menus SET gambar = 'https://www.dapurkobe.co.id/wp-content/uploads/mie-goreng-korea.jpg' WHERE id = 8;
UPDATE menus SET gambar = 'https://allofresh.id/blog/wp-content/uploads/2023/07/resep-nasi-goreng-sederhana-4-1.jpg' WHERE id = 9;
UPDATE menus SET gambar = 'https://asset.kompas.com/crops/vC51StTe42MpsBFJKfu2oVLA2XE=/0x0:968x645/1200x800/data/photo/2024/04/12/66189a0a2b318.jpg' WHERE id = 10;
UPDATE menus SET gambar = 'https://www.giverecipe.com/wp-content/uploads/2019/05/Turkish-Chicken-Salad-Featured-2.jpg' WHERE id = 11;
UPDATE menus SET gambar = 'https://media.istockphoto.com/id/1194610986/id/foto/sarapan-telur-dan-sayuran-rebus.jpg' WHERE id = 12;
UPDATE menus SET gambar = 'https://img.freepik.com/foto-premium/ayam-setengah-panggang-dengan-nasi-merah-di-dalam-wajan_268847-4837.jpg' WHERE id = 13;
UPDATE menus SET gambar = 'https://www.tasteofhome.com/wp-content/uploads/2025/01/Brown-Sugar-Banana-Oatmeal_EXPS_TOHD24_85141_AbbeyLittlejohn_04.jpg' WHERE id = 14;
UPDATE menus SET gambar = 'https://d18zdz9g6n5za7.cloudfront.net/blog/1075-quick-egg-salad-af06.jpg' WHERE id = 15;
UPDATE menus SET gambar = 'https://assets.unileversolutions.com/recipes-v2/258158.jpg' WHERE id = 16;
UPDATE menus SET gambar = 'https://indonesiakaya.com/wp-content/uploads/2023/04/ab_Artboard_1.jpg' WHERE id = 17;
UPDATE menus SET gambar = 'https://asset.kompas.com/crops/T-lA2XBmceDVBtkg1dxHSsZzUCE=/0x38:1000x705/750x500/data/photo/2023/09/08/64faa742ca9ae.jpg' WHERE id = 18;
UPDATE menus SET gambar = 'https://www.astronauts.id/blog/wp-content/uploads/2023/10/Resep-Mie-Aceh-Goreng-Simpel-Untuk-Masak-di-Rumah-1024x678.jpg' WHERE id = 19;
UPDATE menus SET gambar = 'https://cdn-1.timesmedia.co.id/images/2022/12/21/gudeg-2.jpg' WHERE id = 20;
UPDATE menus SET gambar = 'https://www.foodnetwork.com/content/dam/images/food/fullset/2020/01/15/KC2309_family-sized-omelet_s4x3.jpg' WHERE id = 21;
UPDATE menus SET gambar = 'https://images.services.kitchenstories.io/UY89c4f471NaK1C-BQ07FGZ_qVA=/3840x0' WHERE id = 22;
UPDATE menus SET gambar = 'https://www.foodandwine.com/thmb/f4uf4WXHz-waXLB_oqG-U1p4Y7A=/750x0' WHERE id = 23;
UPDATE menus SET gambar = 'https://www.thecateringbutcher.co.uk/wp-content/uploads/2023/05/Sandwich-Week.jpg' WHERE id = 24;
UPDATE menus SET gambar = 'https://greedy-panda.com/wp-content/uploads/2021/05/38_Egg-Fried-Rice-pg-13_001-1600x1600.jpg.webp' WHERE id = 25;
UPDATE menus SET gambar = 'https://blog.kecipir.com/wp-content/uploads/2023/03/mie-goreng-spesial.jpg' WHERE id = 26;
UPDATE menus SET gambar = 'https://akcdn.detik.net.id/api/wm/2024/02/16/ilustrasi-sup-telur_169.jpeg' WHERE id = 27;
UPDATE menus SET gambar = 'https://www.allrecipes.com/thmb/N3hqMgkSlKbPmcWCkHmxekKO61I=/1500x0' WHERE id = 28;
UPDATE menus SET gambar = 'https://www.dapurkobe.co.id/wp-content/uploads/sandwich-goreng-isi-kornet.jpg' WHERE id = 29;
UPDATE menus SET gambar = 'https://feelgoodfoodie.net/wp-content/uploads/2023/01/Low-Carb-Egg-Wrap-08.jpg' WHERE id = 30;

CREATE TABLE premium_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    aktif_sampai DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    menu_id INT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_bookmark(user_id, menu_id),

    FOREIGN KEY(user_id)
    REFERENCES users(id)
    ON DELETE CASCADE,

    FOREIGN KEY(menu_id)
    REFERENCES menus(id)
    ON DELETE CASCADE
);

-- ============================================
-- VALIDASI RESEP USER
-- status: 'pending' (baru diinput user, menunggu validasi admin)
--         'approved' (tervalidasi admin / dibuat admin)
--         'rejected' (ditolak admin, tidak tampil ke publik)
-- validated_at: waktu admin memvalidasi, dipakai untuk urutan tampilan
-- ============================================
ALTER TABLE menus
MODIFY status ENUM('pending','approved','rejected') DEFAULT 'approved';

ALTER TABLE menus
ADD COLUMN validated_at DATETIME NULL,
ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
