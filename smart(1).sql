-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 07, 2026 at 12:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart`
--

-- --------------------------------------------------------

--
-- Table structure for table `bins`
--

CREATE TABLE `bins` (
  `id` int(11) NOT NULL,
  `bin_id` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Empty'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bins`
--

INSERT INTO `bins` (`id`, `bin_id`, `location`, `latitude`, `longitude`, `status`) VALUES
(1, 'BIN001', 'Area A', -8.9001, 33.4501, 'Empty'),
(2, 'BIN002', 'Area B', -8.9005, 33.4505, 'Full'),
(3, 'BIN003', 'Area C', -8.901, 33.451, 'Medium'),
(4, 'BIN004', 'Area D', -8.9015, 33.4515, 'Empty'),
(5, 'BIN005', 'Area E', -8.902, 33.452, 'Full'),
(6, 'BIN006', 'Area F', -8.9025, 33.4525, 'Medium'),
(7, 'BIN007', 'Area G', -8.903, 33.453, 'Empty'),
(8, 'BIN008', 'Area H', -8.9035, 33.4535, 'Full'),
(9, 'BIN009', 'Area I', -8.904, 33.454, 'Medium'),
(10, 'BIN010', 'Area J', -8.9045, 33.4545, 'Empty'),
(11, 'BIN011', 'Area K', -8.905, 33.455, 'Full'),
(12, 'BIN012', 'Area L', -8.9055, 33.4555, 'Medium'),
(13, 'BIN013', 'Area M', -8.906, 33.456, 'Empty'),
(14, 'BIN014', 'Area N', -8.9065, 33.4565, 'Full'),
(15, 'BIN015', 'Area O', -8.907, 33.457, 'Medium'),
(16, 'BIN016', 'Area P', -8.9075, 33.4575, 'Empty'),
(17, 'BIN017', 'Area Q', -8.908, 33.458, 'Full'),
(18, 'BIN018', 'Area R', -8.9085, 33.4585, 'Medium'),
(19, 'BIN019', 'Area S', -8.909, 33.459, 'Empty'),
(20, 'BIN020', 'Area T', -8.9095, 33.4595, 'Full'),
(21, 'BIN021', 'Area U', -8.91, 33.46, 'Medium'),
(22, 'BIN022', 'Area V', -8.9105, 33.4605, 'Empty'),
(23, 'BIN023', 'Area W', -8.911, 33.461, 'Full'),
(24, 'BIN024', 'Area X', -8.9115, 33.4615, 'Medium'),
(25, 'BIN025', 'Area Y', -8.912, 33.462, 'Empty'),
(26, 'BIN026', 'Area Z', -8.9125, 33.4625, 'Full'),
(27, 'BIN027', 'Area AA', -8.913, 33.463, 'Medium'),
(28, 'BIN028', 'Area AB', -8.9135, 33.4635, 'Empty'),
(29, 'BIN029', 'Area AC', -8.914, 33.464, 'Full'),
(30, 'BIN030', 'Area AD', -8.9145, 33.4645, 'Medium');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `driver_share` decimal(10,2) DEFAULT 0.00,
  `collector_share` decimal(10,2) DEFAULT 0.00,
  `installer_share` decimal(10,2) DEFAULT 0.00,
  `government_share` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `amount`, `driver_share`, `collector_share`, `installer_share`, `government_share`, `created_at`, `status`) VALUES
(5, 5, 1000.00, 200.00, 150.00, 150.00, 500.00, '2026-04-06 19:43:38', 'Paid'),
(6, 6, 1000.00, 200.00, 150.00, 150.00, 500.00, '2026-04-06 19:54:56', 'Paid'),
(7, 1, 1000.00, 200.00, 150.00, 150.00, 500.00, '2026-04-06 19:56:04', 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bin_id` varchar(20) NOT NULL,
  `location` varchar(100) NOT NULL,
  `report_text` enum('Full','Damage') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `bin_id`, `location`, `report_text`, `created_at`) VALUES
(1, 1, 'B101', 'Mbeya Town', 'Full', '2026-04-06 11:14:08'),
(2, 2, 'B102', 'Uyole', 'Damage', '2026-04-06 11:14:08'),
(3, 3, 'B103', 'Tukuyu', 'Full', '2026-04-06 11:14:08'),
(4, 1, 'B102', 'Uyole', 'Full', '2026-04-06 11:14:08'),
(5, 2, 'B101', 'Mbeya Town', 'Damage', '2026-04-06 11:14:08'),
(6, 5, '100', '', '', '2026-04-06 12:47:59');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task_name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `lat` decimal(10,7) NOT NULL,
  `lng` decimal(10,7) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `status` enum('Pending','Ongoing','Completed','Failed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `task_name`, `location`, `lat`, `lng`, `assigned_to`, `role`, `status`, `created_at`) VALUES
(12, 'bins installation mbeya airpot', 'mbaya airpot', -8.9000000, 33.4500000, 0, NULL, 'Pending', '2026-04-06 17:46:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(1, 'jully', 'jully@gmail.com', 'j9090', 'admin'),
(2, 'driver1', 'driver@gmail.com', 'j9090', 'driver'),
(3, 'collector1', 'collector@gmail.com', 'j9090', 'collector'),
(4, 'installer1', 'installer@gmail.com', 'j9090', 'installer'),
(5, 'given', 'given@gmail.com', 'j9090', 'citizen'),
(6, 'juma', 'juma@gmail.com', 'j9090', 'citizen'),
(11, 'kelvin', 'k@gmail.com', 'j9090', 'collector');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bins`
--
ALTER TABLE `bins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`);

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
-- AUTO_INCREMENT for table `bins`
--
ALTER TABLE `bins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
