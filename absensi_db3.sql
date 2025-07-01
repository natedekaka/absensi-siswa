-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 01, 2025 at 08:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi_db3`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Hadir','Sakit','Izin','Alfa','Terlambat') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `siswa_id`, `tanggal`, `status`) VALUES
(28, 19, '2025-06-30', 'Hadir'),
(29, 20, '2025-06-30', 'Hadir'),
(30, 21, '2025-06-30', 'Hadir'),
(31, 22, '2025-06-30', 'Hadir'),
(32, 19, '2025-07-01', 'Terlambat'),
(33, 20, '2025-07-01', 'Terlambat'),
(34, 21, '2025-07-01', 'Hadir'),
(35, 22, '2025-07-01', 'Hadir');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(10) NOT NULL,
  `wali_kelas` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `wali_kelas`) VALUES
(17, 'X1', 'xxx'),
(18, 'XX-2', 'aniX'),
(19, 'XX-3', 'xxx3'),
(20, 'XX-4', 'xxx4');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `nis`, `nisn`, `nama`, `kelas_id`, `jenis_kelamin`) VALUES
(19, '123', '123', 'nate', 20, 'Laki-laki'),
(20, '2023001', '1234567890', 'Andi Wijaya', 20, 'Laki-laki'),
(21, '2023002', '2345678901', 'Dewi Lestari', 20, 'Perempuan'),
(22, '2023003', '3456789012', 'Budi Santoso 1', 20, 'Laki-laki'),
(23, '2023004', '3456789013', 'Budi Santoso 2', 20, 'Laki-laki'),
(24, '2023005', '3456789014', 'Budi Santoso 3', 20, 'Perempuan'),
(25, '2023006', '3456789015', 'Budi Santoso 4', 20, 'Laki-laki'),
(26, '2023007', '3456789016', 'Budi Santoso 5', 20, 'Laki-laki'),
(27, '2023008', '3456789017', 'Budi Santoso 6', 20, 'Perempuan'),
(28, '2023009', '3456789018', 'Budi Santoso 7', 20, 'Laki-laki'),
(29, '2023010', '3456789019', 'Budi Santoso 8', 20, 'Laki-laki'),
(30, '2023011', '3456789020', 'Budi Santoso 9', 20, 'Perempuan'),
(31, '2023012', '3456789021', 'Budi Santoso 10', 20, 'Laki-laki'),
(32, '2023013', '3456789022', 'Budi Santoso 11', 20, 'Laki-laki'),
(33, '2023014', '3456789023', 'Budi Santoso 12', 20, 'Perempuan'),
(34, '2023015', '3456789024', 'Budi Santoso 13', 20, 'Laki-laki'),
(35, '2023016', '3456789025', 'Budi Santoso 14', 20, 'Laki-laki'),
(36, '2023017', '3456789026', 'Budi Santoso 15', 20, 'Perempuan'),
(37, '2023018', '3456789027', 'Budi Santoso 16', 20, 'Laki-laki'),
(38, '2023019', '3456789028', 'Budi Santoso 17', 20, 'Laki-laki'),
(39, '2023020', '3456789029', 'Budi Santoso 18', 20, 'Perempuan'),
(40, '2023021', '3456789030', 'Budi Santoso 19', 20, 'Laki-laki'),
(41, '2023022', '3456789031', 'Budi Santoso 20', 20, 'Laki-laki'),
(42, '2023023', '3456789032', 'Budi Santoso 21', 20, 'Perempuan'),
(43, '2023024', '3456789033', 'Budi Santoso 22', 20, 'Laki-laki'),
(44, '2023025', '3456789034', 'Budi Santoso 23', 20, 'Laki-laki'),
(45, '2023026', '3456789035', 'Budi Santoso 24', 20, 'Perempuan'),
(46, '2023027', '3456789036', 'Budi Santoso 25', 20, 'Laki-laki'),
(47, '2023028', '3456789037', 'Budi Santoso 26', 20, 'Laki-laki'),
(48, '2023029', '3456789038', 'Budi Santoso 27', 20, 'Perempuan'),
(49, '2023030', '3456789039', 'Budi Santoso 28', 20, 'Laki-laki'),
(50, '2023031', '3456789040', 'Budi Santoso 29', 20, 'Laki-laki'),
(52, '2023033', '3456789042', 'Budi Santoso 31', 20, 'Laki-laki'),
(53, '2023034', '3456789043', 'Budi Santoso 32', 20, 'Laki-laki'),
(54, '2023035', '3456789044', 'Budi Santoso 33', 20, 'Perempuan'),
(55, '2023036', '3456789045', 'Budi Santoso 34', 20, 'Laki-laki'),
(56, '2023037', '3456789046', 'Budi Santoso 35', 20, 'Laki-laki'),
(57, '2023038', '3456789047', 'Budi Santoso 36', 20, 'Perempuan'),
(58, '2023039', '3456789048', 'Budi Santoso 37', 20, 'Laki-laki'),
(59, '2023040', '3456789049', 'Budi Santoso 38', 20, 'Laki-laki'),
(60, '2023041', '3456789050', 'Budi Santoso 39', 20, 'Perempuan'),
(61, '2023042', '3456789051', 'Budi Santoso 40', 20, 'Laki-laki'),
(62, '2023043', '3456789052', 'Budi Santoso 41', 20, 'Laki-laki');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `role` enum('admin','guru') NOT NULL DEFAULT 'guru'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama`, `role`) VALUES
(1, 'admin', '$2y$10$3.dK/h.S4dqfZ9qYVO0DSeCA5nyk7c3OOi5B3UmAOwIks.4RZW8mS', 'Administrator', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`);

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
