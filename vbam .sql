-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 27, 2025 at 01:18 PM
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
-- Database: `vbam` ad
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `role`, `password`, `created_at`, `updated_at`) VALUES
(1, 'James Harper', 'jimmy@gmail.com', 'admin', '$2y$10$CaqjoRyl9mLWy1Ys0wjHHeFWxXXdQfTYsVmzX7WPTKCG8j9icVNPC', '2025-03-11 11:25:58', '2025-03-27 09:46:34');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` text NOT NULL DEFAULT '\'driver\'',
  `password` varchar(255) NOT NULL DEFAULT '$2y$10$CaqjoRyl9mLWy1Ys0wjHHeFWxXXdQfTYsVmzX7WPTKCG8j9icVNPC',
  `availability_status` enum('Available','Offline','Busy') DEFAULT 'Offline',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `name`, `phone`, `email`, `role`, `password`, `availability_status`, `created_at`, `updated_at`) VALUES
(1, 'Bunastar Vina', '0712131415', 'mosesbunango@gmail.com', 'driver', '$2y$10$Oe1M1cA0VKPBvxob2MuRnutfMuLigplIfpTZMkKzCE.6dKYuF8MUK', 'Busy', '2025-03-11 11:34:21', '2025-03-27 11:45:59'),
(2, 'Chacha Maige', '0771232323', 'chacha@gmail.com', 'driver', '', 'Busy', '2025-03-11 13:04:44', '2025-03-27 08:13:47'),
(3, 'Taiko Laizer', '0712343536', 'tl@gmail.com', 'driver', '', 'Offline', '2025-03-11 13:07:01', '2025-03-27 08:13:47'),
(4, 'John Collins', '0682655678', 'joecoll@gmail.com', 'driver', '', 'Offline', '2025-03-11 13:07:01', '2025-03-27 08:13:47'),
(5, 'Jaren Jackson', '255742398600', 'jjj@gmail.com', 'driver', '', 'Busy', '2025-03-11 13:07:02', '2025-03-27 08:13:47'),
(7, 'Blaza Kaka', '255735398600', 'blazakaka@gmail.com', '\'driver\'', '$2y$10$CaqjoRyl9mLWy1Ys0wjHHeFWxXXdQfTYsVmzX7WPTKCG8j9icVNPC', 'Offline', '2025-03-27 11:58:53', '2025-03-27 11:58:53');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `location` text NOT NULL,
  `car_model` varchar(255) DEFAULT NULL,
  `problem_description` text DEFAULT NULL,
  `status` enum('Pending','Accepted','Completed','Declined','Assigned') DEFAULT 'Pending',
  `assigned_driver_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `name`, `phone`, `location`, `car_model`, `problem_description`, `status`, `assigned_driver_id`, `created_at`, `updated_at`) VALUES
(1, 'Twahili Mboga', '0771232425', 'Mabibo Hostel', 'Toyoota IST', 'Flat tyre', 'Completed', 1, '2025-03-11 12:51:36', '2025-03-27 09:08:43'),
(17, 'Dogo Janja', '0771232425', 'Mabibo Hostel', 'Toyota IST', 'Flat tyre', 'Declined', 1, '2025-03-11 13:08:37', '2025-03-27 09:09:03'),
(18, 'John Doe', '0789876543', 'Kinondoni', 'Honda Civic', 'Engine overheating', 'Pending', 2, '2025-03-11 13:08:37', '2025-03-11 13:08:37'),
(19, 'Jane Smith', '0781234567', 'Mbezi', 'Nissan Almera', 'Brake failure', 'Pending', 3, '2025-03-11 13:08:37', '2025-03-11 13:08:37'),
(20, 'Abdul Hassan', '0754433221', 'Ilala', 'Mazda Demio', 'Battery dead', 'Pending', 1, '2025-03-11 13:08:37', '2025-03-11 13:08:37'),
(21, 'Fatma Ali', '0773456789', 'Manzese', 'Toyota Corolla', 'Fuel leakage', 'Pending', 4, '2025-03-11 13:08:37', '2025-03-11 13:08:37'),
(22, 'Michael Mbwasi', '0742345678', 'Magomeni', 'Subaru Impreza', 'Engine misfire', 'Pending', 2, '2025-03-11 13:08:37', '2025-03-11 13:08:37'),
(23, 'Samuel Kiama', '0769876543', 'Kariakoo', 'Ford Focus', 'Transmission failure', 'Pending', 3, '2025-03-11 13:08:37', '2025-03-11 13:08:37'),
(24, 'Ruth Kinondoni', '0755654321', 'Tegeta', 'Hyundai Elantra', 'Broken axle', 'Pending', 4, '2025-03-11 13:08:37', '2025-03-11 13:08:37'),
(25, 'Peter Mwangaza', '0776543210', 'Gongo la Mboto', 'Kia Sorento', 'Flat tyre', 'Pending', 1, '2025-03-11 13:08:37', '2025-03-11 13:08:37'),
(26, 'Sandra Ndugu', '0785432109', 'Mwenge', 'Mitsubishi Lancer', 'Suspension issues', 'Pending', 2, '2025-03-11 13:08:37', '2025-03-11 13:08:37'),
(27, 'Juma Bina', '0751238907', 'Boma', 'Chevrolet Aveo', 'Air conditioning failure', 'Pending', 3, '2025-03-11 13:08:37', '2025-03-11 13:08:37'),
(28, 'Eliza Musa', '0768765432', 'Mikocheni', 'BMW X5', 'Oil leakage', 'Completed', 1, '2025-03-11 13:08:37', '2025-03-27 11:33:18'),
(29, 'Hassan Mwinyi', '0798765432', 'Kigamboni', 'Volkswagen Golf', 'Flat tyre', 'Accepted', 5, '2025-03-11 13:08:37', '2025-03-27 06:06:55'),
(30, 'Tina Nasser', '0786543212', 'Bungoni', 'Toyota Rav4', 'Clutch issues', 'Accepted', 5, '2025-03-11 13:08:37', '2025-03-27 06:21:11'),
(31, 'Yusuf Mzee', '0745678901', 'Pugu', 'Mercedes Benz', 'Broken alternator', 'Accepted', 5, '2025-03-11 13:08:37', '2025-03-27 06:56:15'),
(32, 'David A. Mbalike', '0742398600', 'Mwenge Mpakani', 'Mazda CX-5', 'Haiwaki', 'Pending', 1, '2025-03-12 12:28:10', '2025-03-12 13:15:22'),
(35, 'David A. Mbalike', '0742398600', 'Mwenge Mpakani', 'Mazda CX-5', 'Moshi unafuka', 'Assigned', 1, '2025-03-27 07:43:25', '2025-03-27 09:51:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_driver_id` (`assigned_driver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`assigned_driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
