-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Jun 2026 pada 13.04
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sibansos`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `bantuan_sosial`
--

CREATE TABLE `bantuan_sosial` (
  `id_bansos` int(11) NOT NULL,
  `nama_bantuan` varchar(100) NOT NULL,
  `jenis_bantuan` varchar(50) DEFAULT NULL,
  `tanggal_bantuan` date DEFAULT NULL,
  `jumlah_bantuan` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bantuan_sosial`
--

INSERT INTO `bantuan_sosial` (`id_bansos`, `nama_bantuan`, `jenis_bantuan`, `tanggal_bantuan`, `jumlah_bantuan`) VALUES
(4, 'Program Keluarga Harapan (PKH)', 'Uang Tunai', '2026-07-01', 1000000.00),
(5, 'Bantuan Pangan Non Tunai (BPNT)', 'Sembako', '2026-07-01', 200000.00),
(6, 'Bantuan Langsung Tunai (BLT)', 'Uang Tunai', '2026-07-01', 600000.00),
(7, 'Bantuan Pendidikan', 'Beasiswa', '2026-07-01', 1500000.00),
(8, 'Bantuan Kesehatan', 'Biaya Pengobatan', '2026-07-01', 500000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `hasil_ai`
--

CREATE TABLE `hasil_ai` (
  `id` int(11) NOT NULL,
  `nik` varchar(30) DEFAULT NULL,
  `penghasilan` bigint(20) DEFAULT NULL,
  `tanggungan` int(11) DEFAULT NULL,
  `kondisi_rumah` varchar(100) DEFAULT NULL,
  `hasil` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jenis_bantuan`
--

CREATE TABLE `jenis_bantuan` (
  `id` int(11) NOT NULL,
  `nama_jenis` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jenis_bantuan`
--

INSERT INTO `jenis_bantuan` (`id`, `nama_jenis`) VALUES
(1, 'Sembako'),
(2, 'Pendidikan'),
(3, 'Kesehatan'),
(4, 'UMKM'),
(5, 'Lansia'),
(6, 'Disabilitas'),
(7, 'rumah');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `aksi` enum('login','logout') DEFAULT 'login',
  `ip_address` varchar(50) DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `user_id`, `username`, `nama`, `role`, `aksi`, `ip_address`, `waktu`) VALUES
(97, 19, 'taqiya@superadmin.com', 'Taqiya', 'superadmin', 'logout', '::1', '2026-06-29 01:51:04'),
(98, 16, '', 'Aminah', 'masyarakat', 'logout', '::1', '2026-06-29 01:51:55'),
(99, 17, 'riagni@petugas.com', 'Riagni', 'petugas', 'logout', '::1', '2026-06-29 02:00:04'),
(100, 16, '', 'Aminah', 'masyarakat', 'logout', '::1', '2026-06-29 02:05:51'),
(101, 17, 'riagni@petugas.com', 'Riagni', 'petugas', 'logout', '::1', '2026-06-29 02:36:54'),
(102, 17, 'riagni@petugas.com', 'Riagni', 'petugas', 'logout', '::1', '2026-06-29 02:41:14'),
(103, 17, 'riagni@petugas.com', 'Riagni', 'petugas', 'logout', '::1', '2026-06-29 02:49:41'),
(104, 16, '', 'Aminah', 'masyarakat', 'logout', '::1', '2026-06-29 03:06:49'),
(105, 17, 'riagni@petugas.com', 'Riagni', 'petugas', 'logout', '::1', '2026-06-29 03:48:16'),
(106, 18, 'indri@admin.com', 'Indri', 'admin', 'logout', '::1', '2026-06-29 03:53:34'),
(107, 17, 'riagni@petugas.com', 'Riagni', 'petugas', 'logout', '::1', '2026-06-29 04:16:16'),
(108, 16, '', 'Aminah', 'masyarakat', 'logout', '::1', '2026-06-29 04:30:55'),
(109, 17, 'riagni@petugas.com', 'Riagni', 'petugas', 'logout', '::1', '2026-06-29 08:48:16'),
(110, 19, 'taqiya@superadmin.com', 'Taqiya', 'superadmin', 'logout', '::1', '2026-06-29 09:01:20'),
(111, 19, 'taqiya@superadmin.com', 'Taqiya', 'superadmin', 'logout', '::1', '2026-06-29 09:02:59'),
(112, 17, 'riagni@petugas.com', 'Riagni', 'petugas', 'logout', '::1', '2026-06-29 09:04:27'),
(113, 22, '1234567890123456', 'ranayana', 'masyarakat', 'logout', '::1', '2026-06-29 09:22:14'),
(114, 22, '1234567890123456', 'ranayana', 'masyarakat', 'logout', '::1', '2026-06-29 09:28:01'),
(115, 19, 'taqiya@superadmin.com', 'Taqiya', 'superadmin', 'logout', '::1', '2026-06-29 09:29:58'),
(116, 17, 'riagni@petugas.com', 'Riagni', 'petugas', 'logout', '::1', '2026-06-29 09:36:21'),
(117, 17, 'riagni@petugas.com', 'Riagni', 'petugas', 'logout', '::1', '2026-06-29 09:52:44'),
(118, 19, 'taqiya@superadmin.com', 'Taqiya', 'superadmin', 'login', '::1', '2026-06-29 09:59:36'),
(119, 19, 'taqiya@superadmin.com', 'Taqiya', 'superadmin', 'logout', '::1', '2026-06-29 10:28:27'),
(120, 23, 'imelda@petugas', 'imelda', 'petugas', 'login', '::1', '2026-06-29 10:28:43'),
(121, 23, 'imelda@petugas', 'imelda', 'petugas', 'logout', '::1', '2026-06-29 10:29:05'),
(122, 17, 'riagni@petugas.com', 'Riagni', 'petugas', 'login', '::1', '2026-06-29 10:38:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `masyarakat`
--

CREATE TABLE `masyarakat` (
  `nik` char(16) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `penghasilan` decimal(12,2) DEFAULT NULL,
  `tanggungan` int(11) DEFAULT 0,
  `kondisi_rumah` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `status_rumah` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `masyarakat`
--

INSERT INTO `masyarakat` (`nik`, `nama`, `alamat`, `pekerjaan`, `penghasilan`, `tanggungan`, `kondisi_rumah`, `no_hp`, `foto`, `status`, `status_rumah`) VALUES
('1234567890123456', 'ranayana', 'jln.mawar melat', 'Buruh Harian', 500000.00, 0, NULL, '08969494885586', NULL, 'aktif', 'Milik Sendiri'),
('3276011234567890', 'Aminah', 'Jl. Melati No. 10', '', 500000.00, 0, NULL, '081234567890', NULL, 'aktif', 'Milik Sendiri'),
('3578123456789001', 'Andi Saputra', 'Surabaya', 'Buruh', 1500000.00, 0, NULL, NULL, NULL, 'aktif', NULL),
('3578123456789002', 'Siti Rahma', 'Sidoarjo', 'Ibu Rumah Tangga', 800000.00, 0, NULL, NULL, NULL, 'aktif', NULL),
('3578123456789003', 'Budi Hartono', 'Gresik', 'Sopir', 2000000.00, 0, NULL, NULL, NULL, 'aktif', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `penerima_bantuan`
--

CREATE TABLE `penerima_bantuan` (
  `id_penerima` int(11) NOT NULL,
  `nik` char(16) DEFAULT NULL,
  `id_bansos` int(11) DEFAULT NULL,
  `id_petugas` int(11) DEFAULT NULL,
  `status_verifikasi` enum('pending','diterima','ditolak') DEFAULT 'pending',
  `tanggal_verifikasi` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penerima_bantuan`
--

INSERT INTO `penerima_bantuan` (`id_penerima`, `nik`, `id_bansos`, `id_petugas`, `status_verifikasi`, `tanggal_verifikasi`) VALUES
(11, '3276011234567890', 7, 1, 'diterima', '2026-06-28');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengajuan`
--

CREATE TABLE `pengajuan` (
  `id_pengajuan` int(11) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `id_bansos` int(11) DEFAULT NULL,
  `alasan` text DEFAULT NULL,
  `dokumen` varchar(255) DEFAULT NULL,
  `status` enum('pending','diterima','ditolak') DEFAULT 'pending',
  `catatan_admin` text DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `tanggal_pengajuan` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_proses` date DEFAULT NULL,
  `ktp` varchar(255) DEFAULT NULL,
  `kk` varchar(255) DEFAULT NULL,
  `sktm` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengajuan`
--

INSERT INTO `pengajuan` (`id_pengajuan`, `nik`, `id_bansos`, `alasan`, `dokumen`, `status`, `catatan_admin`, `id_admin`, `tanggal_pengajuan`, `tanggal_proses`, `ktp`, `kk`, `sktm`) VALUES
(1, '7372043011678', 3, 'butuh dana pendidikan', 'DOK_7372043011678_1781105759.png', 'diterima', '', 8, '2026-06-10 15:35:59', '2026-06-10', NULL, NULL, NULL),
(2, '7372043011678', 1, 'butuh ', 'DOK_7372043011678_1781105913.png', 'ditolak', 'tidak memenuhi syarat', 8, '2026-06-10 15:38:33', '2026-06-10', NULL, NULL, NULL),
(3, '7372043011678', 1, 'butuh ', 'DOK_7372043011678_1781106000.png', 'diterima', NULL, NULL, '2026-06-10 15:40:00', '2026-06-29', NULL, NULL, NULL),
(4, '3276011234567890', 7, 'kurang mampu', '', 'diterima', '', 18, '2026-06-28 09:15:16', '2026-06-28', NULL, NULL, NULL),
(5, '3276011234567890', 6, 'kebuthan', NULL, 'diterima', NULL, NULL, '2026-06-28 10:32:16', '2026-06-29', 'KTP_3276011234567890_1782642736.png', 'KK_3276011234567890_1782642736.png', 'SKTM_3276011234567890_1782642736.png');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `kunci` varchar(100) DEFAULT NULL,
  `nilai` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `kunci`, `nilai`) VALUES
(1, 'nama_sistem', 'SIBANSOS'),
(2, 'versi', '1.0.0'),
(3, 'alamat_inst', ''),
(4, 'nama_inst', ''),
(5, 'kontak', '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id_pengumuman` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `isi` text NOT NULL,
  `id_pembuat` int(11) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengumuman`
--

INSERT INTO `pengumuman` (`id_pengumuman`, `judul`, `isi`, `id_pembuat`, `status`, `tanggal`) VALUES
(1, 'PECAIRAN BULANAN BANTUAN BERAS ', 'KELURAHAN BUMI HARAPAN', 8, 'nonaktif', '2026-06-10 15:39:51'),
(2, 'PECAIRAN BULANAN BANTUAN BERAS ', 'KELURAHAN BUMI HARAPAN', 8, 'nonaktif', '2026-06-10 16:29:50'),
(3, 'PENCAIRAN BEASISWA', 'penerima beasiswa akan ada pencairan jam 15.00', 18, 'aktif', '2026-06-29 03:50:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penyaluran_bantuan`
--

CREATE TABLE `penyaluran_bantuan` (
  `id_penyaluran` int(11) NOT NULL,
  `id_masyarakat` char(16) NOT NULL,
  `id_bansos` int(11) NOT NULL,
  `tanggal_penyaluran` date DEFAULT NULL,
  `jumlah` decimal(15,2) DEFAULT NULL,
  `status` enum('belum_disalurkan','sudah_disalurkan') DEFAULT 'belum_disalurkan',
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penyaluran_bantuan`
--

INSERT INTO `penyaluran_bantuan` (`id_penyaluran`, `id_masyarakat`, `id_bansos`, `tanggal_penyaluran`, `jumlah`, `status`, `keterangan`) VALUES
(10, '3578123456789001', 5, '2026-06-29', 160000.00, 'sudah_disalurkan', NULL),
(11, '3276011234567890', 4, '2026-06-16', 600000.00, 'sudah_disalurkan', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `petugas`
--

CREATE TABLE `petugas` (
  `id_petugas` int(11) NOT NULL,
  `nama_petugas` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `petugas`
--

INSERT INTO `petugas` (`id_petugas`, `nama_petugas`, `username`, `password`) VALUES
(1, 'juna', 'jujuna1', '12345');

-- --------------------------------------------------------

--
-- Struktur dari tabel `survei_iot`
--

CREATE TABLE `survei_iot` (
  `id` int(11) NOT NULL,
  `nik` varchar(30) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `foto_rumah` varchar(255) DEFAULT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `survey`
--

CREATE TABLE `survey` (
  `id_survey` int(11) NOT NULL,
  `id_pengajuan` int(11) NOT NULL,
  `id_masyarakat` char(16) NOT NULL,
  `tanggal_survey` date DEFAULT NULL,
  `petugas` varchar(100) DEFAULT NULL,
  `hasil_survey` text DEFAULT NULL,
  `status` enum('jadwal','proses','selesai') DEFAULT 'jadwal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `foto_rumah` varchar(255) DEFAULT NULL,
  `jumlah_tanggungan` int(11) DEFAULT NULL,
  `penghasilan` decimal(12,2) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `rekomendasi` text DEFAULT NULL,
  `kelayakan` enum('layak','tidak_layak') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `survey`
--

INSERT INTO `survey` (`id_survey`, `id_pengajuan`, `id_masyarakat`, `tanggal_survey`, `petugas`, `hasil_survey`, `status`, `created_at`, `foto_rumah`, `jumlah_tanggungan`, `penghasilan`, `catatan`, `rekomendasi`, `kelayakan`) VALUES
(12, 0, '3276011234567890', '2026-07-01', 'Riagni', 'h', 'selesai', '2026-06-29 09:03:28', '[\"survey_12_1782723851_0.png\"]', NULL, NULL, NULL, 'f', 'layak');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','petugas','masyarakat','superadmin') DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `penghasilan` varchar(50) DEFAULT NULL,
  `tanggungan` int(11) DEFAULT NULL,
  `status_rumah` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `username`, `password`, `role`, `no_hp`, `alamat`, `foto`, `nik`, `pekerjaan`, `penghasilan`, `tanggungan`, `status_rumah`) VALUES
(16, 'Aminah', NULL, '', '12345678', 'masyarakat', '081234567890', 'Jl. Melati No. 10', '1782666163_16.jpg', '3276011234567890', '', '<500.000', 0, 'Milik Sendiri'),
(17, 'Riagni', 'riagni@petugas.com', 'riagni@petugas.com', '$2y$10$G..h.iaJu9zXLPRn9.bM1uaMLLuthTIS38BPxRa1AS7vfY2mGGasu', 'petugas', '081234567891', 'Kantor Desa', 'petugas_17_1782701084.jpg', NULL, NULL, NULL, NULL, NULL),
(18, 'Indri', NULL, 'indri@admin.com', '12345678', 'admin', '081234567892', 'Kantor Kecamatan', '', NULL, NULL, NULL, NULL, NULL),
(19, 'Taqiya', NULL, 'taqiya@superadmin.com', '$2y$10$QDHIDwZjM9tMxKAVXk7cH.j3bCqTcjn4/lTnaLsseRN6NpNidA0oS', 'superadmin', '081234567893', 'Dinas Sosial', '', NULL, NULL, NULL, NULL, NULL),
(22, 'ranayana', NULL, '1234567890123456', '$2y$10$pALZuZFvEXl5vLLyrNn0HOv2GkyhqxtySftjSla3opqiLWFMdjet2', 'masyarakat', '08969494885586', 'jln.mawar melat', '', '1234567890123456', 'Buruh Harian', '<500.000', 0, 'Milik Sendiri'),
(23, 'imelda', NULL, 'imelda@petugas', '$2y$10$2e1wWVoionk0sM1PMSOUx.sUGrIlF1dtY8NPz2GDSmTBlpUnlYaSe', 'petugas', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `bantuan_sosial`
--
ALTER TABLE `bantuan_sosial`
  ADD PRIMARY KEY (`id_bansos`);

--
-- Indeks untuk tabel `hasil_ai`
--
ALTER TABLE `hasil_ai`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jenis_bantuan`
--
ALTER TABLE `jenis_bantuan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `masyarakat`
--
ALTER TABLE `masyarakat`
  ADD PRIMARY KEY (`nik`);

--
-- Indeks untuk tabel `penerima_bantuan`
--
ALTER TABLE `penerima_bantuan`
  ADD PRIMARY KEY (`id_penerima`),
  ADD KEY `nik` (`nik`),
  ADD KEY `id_bansos` (`id_bansos`),
  ADD KEY `id_petugas` (`id_petugas`);

--
-- Indeks untuk tabel `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD PRIMARY KEY (`id_pengajuan`),
  ADD KEY `nik` (`nik`),
  ADD KEY `id_bansos` (`id_bansos`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_nik` (`nik`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kunci` (`kunci`);

--
-- Indeks untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id_pengumuman`);

--
-- Indeks untuk tabel `penyaluran_bantuan`
--
ALTER TABLE `penyaluran_bantuan`
  ADD PRIMARY KEY (`id_penyaluran`);

--
-- Indeks untuk tabel `petugas`
--
ALTER TABLE `petugas`
  ADD PRIMARY KEY (`id_petugas`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `survei_iot`
--
ALTER TABLE `survei_iot`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `survey`
--
ALTER TABLE `survey`
  ADD PRIMARY KEY (`id_survey`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `bantuan_sosial`
--
ALTER TABLE `bantuan_sosial`
  MODIFY `id_bansos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `hasil_ai`
--
ALTER TABLE `hasil_ai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jenis_bantuan`
--
ALTER TABLE `jenis_bantuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT untuk tabel `penerima_bantuan`
--
ALTER TABLE `penerima_bantuan`
  MODIFY `id_penerima` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `pengajuan`
--
ALTER TABLE `pengajuan`
  MODIFY `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id_pengumuman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `penyaluran_bantuan`
--
ALTER TABLE `penyaluran_bantuan`
  MODIFY `id_penyaluran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `petugas`
--
ALTER TABLE `petugas`
  MODIFY `id_petugas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `survei_iot`
--
ALTER TABLE `survei_iot`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `survey`
--
ALTER TABLE `survey`
  MODIFY `id_survey` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `penerima_bantuan`
--
ALTER TABLE `penerima_bantuan`
  ADD CONSTRAINT `penerima_bantuan_ibfk_1` FOREIGN KEY (`nik`) REFERENCES `masyarakat` (`nik`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `penerima_bantuan_ibfk_2` FOREIGN KEY (`id_bansos`) REFERENCES `bantuan_sosial` (`id_bansos`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `penerima_bantuan_ibfk_3` FOREIGN KEY (`id_petugas`) REFERENCES `petugas` (`id_petugas`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
