
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `google_id` varchar(100) DEFAULT NULL COMMENT 'Google OAuth User ID',
  `email` varchar(255) DEFAULT NULL COMMENT 'Email dari Google OAuth',
  `google_picture` text DEFAULT NULL COMMENT 'URL foto profil dari Google',
  `nama` varchar(100) NOT NULL,
  `nomor_hp` varchar(20) DEFAULT NULL,
  `alamat` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_google_id` (`google_id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Password default untuk admin dan user1: password
INSERT INTO `users` (`id`, `username`, `password`, `nama`, `nomor_hp`, `alamat`, `latitude`, `longitude`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, 'Kantor Admin', -6.20000000, 106.81666600, 'admin', '2025-10-26 10:04:45'),
(2, 'user1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User Sample', NULL, 'Jl. Sample No. 1', 3.51067891, 98.67994116, 'user', '2025-10-26 10:04:45'),
(3, 'rahmi', '$2y$10$hXBEiha2cluy8A9hiJVAGOWtwCpH9Ahd6Q76YHE/aVAA1nPTiEYVu', 'rahmi OG', '081263636214', 'jalan damai no 23', 3.51037600, 98.67981000, 'user', '2025-10-29 00:08:38');


CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `kategori` enum('organik','anorganik','b3') NOT NULL,
  `jenis_sampah` enum(
    'daun','makanan','kayu','buah_sayur','kotoran_hewan','tulang','kulit_telur','ampas_kopi','kotoran_dapur','kertas_tisu','serbuk_gergaji',
    'botol_plastik','kantong_plastik','gelas_plastik','sedotan_plastik','styrofoam','kemasan_plastik','ember_plastik','mainan_plastik','jerigen_plastik','plastik_lainnya','plastik',
    'kertas','koran','buku','karton','kertas_kantor','amplop','dus_bekas',
    'kaleng_minuman','kaleng_makanan','logam','kawat','paku','seng','foil_aluminium',
    'botol_kaca','pecahan_kaca','cermin','stoples_kaca','kaca',
    'kain','sepatu','tas','selimut','boneka','topi',
    'ban_bekas','sandal_karet','sarung_tangan','balon','pipa_pralon',
    'keramik','bata','karpet','kasur','furniture','gabus','lilin',
    'baterai','lampu','elektronik','hp_bekas','komputer','tv_bekas','kabel_elektronik','charger','aki',
    'oli','cat','obat','pestisida','semprot_serangga','tinta_printer','kaleng_aerosol','termometer',
    'masker','jarum_suntik','sarung_tangan_medis','perban',
    'tidak_diketahui','lainnya'
  ) DEFAULT NULL COMMENT 'Jenis sampah spesifik - 80+ kategori',
  `gambar` varchar(255) NOT NULL,
  `additional_images` text DEFAULT NULL COMMENT 'JSON array untuk gambar tambahan',
  `deskripsi` text DEFAULT NULL,
  `lokasi_latitude` decimal(10,8) NOT NULL,
  `lokasi_longitude` decimal(11,8) NOT NULL,
  `alamat_lokasi` text NOT NULL,
  `whatsapp_number` varchar(20) DEFAULT NULL COMMENT 'Nomor WhatsApp user untuk notifikasi',
  `confidence` decimal(5,2) DEFAULT NULL COMMENT 'AI confidence score (0-100)',
  `ai_prediction` enum('organik','anorganik','b3') DEFAULT NULL,
  `is_corrected` tinyint(1) DEFAULT 0 COMMENT 'Apakah sudah dikoreksi manual oleh admin',
  `correction_note` text DEFAULT NULL COMMENT 'Catatan koreksi dari admin',
  `tags` varchar(500) DEFAULT NULL COMMENT 'Comma-separated tags untuk analitik',
  `status` enum('pending','diproses','selesai') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `reports` (`id`, `user_id`, `kategori`, `jenis_sampah`, `gambar`, `additional_images`, `deskripsi`, `lokasi_latitude`, `lokasi_longitude`, `alamat_lokasi`, `confidence`, `ai_prediction`, `is_corrected`, `correction_note`, `tags`, `status`, `created_at`) VALUES
(1, 2, 'organik', 'botol_plastik', '68fdf681e3ff0_1761474177.jpg', NULL, 'sampah plastik bertebaran', 3.51037600, 98.67981000, 'jl damai no 22', 93.12, 'organik', 1, '', NULL, 'selesai', '2025-10-26 10:22:57'),
(2, 2, 'anorganik', 'botol_plastik', '68fdf76d45603_1761474413.jpg', NULL, 'sampah botol plastik', 3.51037600, 98.67981000, 'jl damai no 22', 93.12, 'organik', 1, 'ini sampah platik dan botol plastik', NULL, 'selesai', '2025-10-26 10:26:53'),
(3, 2, 'anorganik', 'botol_plastik', '68fe11ff12d53_1761481215.jpg', NULL, 'kacamata', 3.51037600, 98.67981000, 'jl damai no 11', 99.87, 'organik', 1, 'plastik kacamata', NULL, 'pending', '2025-10-26 11:46:02'),
(4, 2, 'organik', 'kertas', '68fec93d16597_1761528125.jpeg', NULL, 'sampah kardus', 3.51033525, 98.67981025, 'jl damai no 45', 99.74, 'organik', 1, '', NULL, 'pending', '2025-10-27 01:22:05'),
(5, 2, 'organik', 'kertas', '68feca45c5e70_1761528389.jpeg', NULL, 'sampah kardus', 3.51033525, 98.67981025, 'jl damai no 45', 99.74, 'organik', 1, '', NULL, 'pending', '2025-10-27 01:26:29'),
(6, 2, 'anorganik', 'botol_plastik', '68fed061a9581_1761529953.jpg', NULL, 'sampah plastik', 3.51033525, 98.67981025, 'Jalan Damai, Suka Makmur, Mekar Sari, Deli Serdang, Sumatera Utara, Sumatra, 20144, Indonesia', 93.12, 'organik', 1, '', NULL, 'pending', '2025-10-27 01:52:33'),
(7, 2, 'organik', 'kertas', '69000463e7715_1761608803.jpg', NULL, 'sampah kertas', 3.51033525, 98.67981025, 'Jalan Damai, Suka Makmur, Mekar Sari, Deli Serdang, Sumatera Utara, Sumatra, 20144, Indonesia', 99.91, 'organik', 1, '', NULL, 'pending', '2025-10-27 23:46:43'),
(8, 2, 'organik', 'kertas', '6900124e0449d_1761612366.jpg', NULL, 'sampah kardus', 3.51037600, 98.67981000, 'Jalan Damai, Suka Makmur, Mekar Sari, Deli Serdang, Sumatera Utara, Sumatra, 20144, Indonesia', 97.49, 'organik', 1, '', 'besar,kering', 'diproses', '2025-10-28 00:46:06'),
(9, 2, 'organik', 'karton', '690151498b275_1761694025.jpg', NULL, 'sampah karton yang beserakan', 3.51037600, 98.67981000, 'Lokasi: 3.510376, 98.679810', 97.49, 'organik', 1, '', 'kering,basah', 'pending', '2025-10-28 23:27:05'),
(10, 2, 'organik', 'karton', '6901584b779ec_1761695819.jpg', NULL, 'kardus', 3.51037600, 98.67981000, 'Jalan Damai, Suka Makmur, Mekar Sari, Deli Serdang, Sumatera Utara, Sumatra, 20144, Indonesia', 97.49, 'organik', 1, '', 'berbau,jalanan,rumah', 'pending', '2025-10-28 23:56:59'),
(11, 2, 'b3', 'oli', '690159489daa6_1761696072.jpg', NULL, 'sampah oli', 3.51033525, 98.67981025, 'Lokasi: 3.510335, 98.679810', 99.83, 'b3', 1, '', 'berbau,menumpuk', 'pending', '2025-10-29 00:01:12'),
(12, 2, 'organik', 'karton', '690159eb08332_1761696235.jpeg', NULL, 'sadad', 3.51033525, 98.67981025, 'Jalan Damai, Suka Makmur, Mekar Sari, Deli Serdang, Sumatera Utara, Sumatra, 20144, Indonesia', 99.74, 'organik', 1, '', 'berbau,rumah', 'pending', '2025-10-29 00:03:55');


CREATE TABLE IF NOT EXISTS `user_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Nama lokasi (rumah, kantor, dll)',
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address` text NOT NULL,
  `last_used` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Terakhir digunakan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_location` (`user_id`,`name`),
  KEY `idx_user_last_used` (`user_id`,`last_used`),
  CONSTRAINT `user_locations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- DATA: user_locations
-- ============================================
INSERT INTO `user_locations` (`id`, `user_id`, `name`, `latitude`, `longitude`, `address`, `last_used`, `created_at`) VALUES
(1, 2, 'rumah', 3.51033525, 98.67981025, 'Jalan Damai, Suka Makmur, Mekar Sari, Deli Serdang, Sumatera Utara, Sumatra, 20144, Indonesia', '2025-10-27 01:52:33', '2025-10-27 01:52:33');

-- ============================================
-- TABLE: report_comments (Komentar Admin)
-- ============================================
CREATE TABLE IF NOT EXISTS `report_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL COMMENT 'ID admin yang memberi komentar',
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `report_comments_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_comments_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `users` AUTO_INCREMENT = 4;
ALTER TABLE `reports` AUTO_INCREMENT = 13;
ALTER TABLE `user_locations` AUTO_INCREMENT = 2;
ALTER TABLE `report_comments` AUTO_INCREMENT = 1;


COMMIT;


