-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2025 at 04:24 AM
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
-- Database: `van`
--

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `base_fare` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `base_fare`) VALUES
(1, 'Manila Terminal', 350.00),
(2, 'Infanta Terminal', 350.00),
(8, 'Real, Quezon Province', 350.00),
(9, 'Sta. Maria, Laguna', 350.00),
(10, 'Baras, Rizal', 350.00),
(11, 'Pinugay, Antipolo', 350.00),
(12, 'Boso-Boso, Antipolo', 350.00),
(13, 'Padilla, Antipolo', 350.00),
(14, 'Cogeo Avenue, Antipolo', 350.00),
(15, 'Marikina', 350.00),
(16, 'Rizal', 350.00),
(17, 'Quezon City', 350.00),
(18, 'Sta. Mesa, Manila', 350.00),
(19, 'Famy, Laguna', 350.00),
(20, 'Mabitac, Laguna', 350.00),
(21, 'Pililla, Rizal', 350.00),
(22, 'Tanay, Rizal', 350.00),
(23, 'Morong, Rizal', 350.00),
(24, 'Teresa, Rizal', 350.00),
(25, 'Antipolo, Rizal', 350.00),
(26, 'Masinag, Antipolo', 350.00),
(27, 'San Juan, Manila', 350.00),
(28, 'Legarda, Sampaloc, Manila', 350.00),
(29, 'Sta. Teresita, Sampaloc, Manila', 350.00);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_records`
--

CREATE TABLE `maintenance_records` (
  `id` int(11) NOT NULL,
  `van_id` varchar(50) NOT NULL,
  `maintenance_date` date NOT NULL,
  `maintenance_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `stars` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `ticket_id`, `stars`, `comment`, `created_at`) VALUES
(23, 50, 5, 'THANNKK YOU FOR THE BEST RIDDE!!!!!!!!!', '2025-06-16 04:52:03'),
(24, 49, 4, 'WHHAT IFFFFFFFFFFFFFFF', '2025-06-16 04:52:12'),
(25, 48, 3, 'SANA PUMALDDO AKO NGAYONG ARAW', '2025-06-16 04:52:30'),
(26, 46, 5, 'VIRY COMFORTABLE', '2025-06-16 04:52:40'),
(27, 47, 5, 'MISS MO NA BA?', '2025-06-16 04:52:51'),
(28, 55, 4, 'IDK SUPREME LORD GODD IDK', '2025-06-16 04:53:15'),
(29, 51, 3, 'A ROBUST SYSTEM MADE BY JAY-AR', '2025-06-16 04:53:29'),
(30, 54, 4, 'WHAT IFF', '2025-06-16 04:53:38');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_type` enum('daily','weekly','monthly','yearly') NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `total_sales` decimal(10,2) NOT NULL,
  `total_tickets` int(11) NOT NULL,
  `completed_tickets` int(11) NOT NULL,
  `cancelled_tickets` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `report_type`, `period_start`, `period_end`, `total_sales`, `total_tickets`, `completed_tickets`, `cancelled_tickets`, `created_at`) VALUES
(38, 'monthly', '2025-06-01', '2025-06-30', 8260.00, 9, 6, 0, '2025-06-16 13:07:19');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'company_name', 'VanTastic', 'The name of the company', '2025-04-29 09:23:34'),
(2, 'base_fare', '50.00', 'Base fare amount in pesos', '2025-04-29 09:23:34'),
(3, 'fare_per_km', '10.00', 'Additional fare per kilometer', '2025-04-29 09:23:34'),
(4, 'max_passengers', '12', 'Maximum passengers per van', '2025-04-29 09:24:05'),
(5, 'maintenance_interval', '90', 'Days between van maintenance checks', '2025-04-29 09:23:34'),
(6, 'cancellation_fee', '20.00', 'Fee for cancelled tickets', '2025-04-29 09:23:34'),
(7, 'admin_email', 'admin@vantastic.com', 'Primary admin email address', '2025-04-29 09:23:34');

-- --------------------------------------------------------

--
-- Table structure for table `terminals`
--

CREATE TABLE `terminals` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `terminals`
--

INSERT INTO `terminals` (`id`, `name`, `location`) VALUES
(1, 'Manila Terminal', 'Manila, Philippines'),
(2, 'Infanta Terminal', 'Infanta Terminal');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `terminal_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `passenger_count` int(11) NOT NULL,
  `baggage_count` int(11) NOT NULL DEFAULT 0,
  `travel_date` date NOT NULL,
  `travel_time` time NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('upcoming','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `fully_paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `terminal_id`, `destination_id`, `passenger_count`, `baggage_count`, `travel_date`, `travel_time`, `payment_method`, `total_amount`, `discount_amount`, `status`, `fully_paid`, `created_at`) VALUES
(45, 1, 1, 23, 5, 3, '2025-06-16', '16:00:00', 'gcash', 1690.00, 90.00, 'upcoming', 0, '2025-06-16 04:31:40'),
(46, 1, 1, 23, 4, 2, '2025-06-24', '16:00:00', 'gcash', 1390.00, 30.00, 'completed', 1, '2025-06-16 04:31:58'),
(47, 1, 2, 9, 7, 0, '2025-06-23', '10:00:00', 'gcash', 2450.00, 0.00, 'completed', 1, '2025-06-16 04:32:18'),
(48, 1, 1, 26, 4, 2, '2025-06-25', '18:00:00', 'gcash', 1330.00, 90.00, 'completed', 1, '2025-06-16 04:32:34'),
(49, 1, 1, 20, 4, 3, '2025-06-27', '10:00:00', 'gcash', 1310.00, 120.00, 'completed', 1, '2025-06-16 04:32:54'),
(50, 1, 1, 18, 4, 3, '2025-06-30', '10:00:00', 'gcash', 1430.00, 0.00, 'completed', 1, '2025-06-16 04:33:10'),
(51, 39, 1, 20, 3, 0, '2025-07-16', '16:00:00', 'gcash', 1050.00, 0.00, 'completed', 1, '2025-06-16 04:46:19'),
(52, 39, 1, 27, 1, 0, '2025-07-25', '16:00:00', 'gcash', 320.00, 30.00, 'completed', 0, '2025-06-16 04:47:11'),
(53, 39, 1, 22, 2, 2, '2025-07-28', '16:00:00', 'gcash', 720.00, 0.00, 'completed', 0, '2025-06-16 04:47:27'),
(54, 39, 1, 21, 1, 0, '2025-06-26', '12:00:00', 'gcash', 350.00, 0.00, 'completed', 1, '2025-06-16 04:47:37'),
(55, 39, 1, 23, 1, 0, '2025-07-25', '14:00:00', 'gcash', 350.00, 0.00, 'completed', 1, '2025-06-16 04:47:48'),
(56, 1, 1, 25, 1, 0, '2025-06-16', '18:00:00', 'gcash', 350.00, 0.00, 'upcoming', 1, '2025-06-16 12:38:12'),
(57, 1, 1, 22, 1, 0, '2025-06-25', '18:00:00', 'gcash', 320.00, 30.00, 'upcoming', 0, '2025-06-16 13:04:37');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_barcodes`
--

CREATE TABLE `ticket_barcodes` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `barcode_value` varchar(255) NOT NULL,
  `scan_status` enum('unscanned','scanned') NOT NULL DEFAULT 'unscanned',
  `scan_time` datetime DEFAULT NULL,
  `scanned_by` int(11) DEFAULT NULL COMMENT 'User ID of who scanned the ticket'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_barcodes`
--

INSERT INTO `ticket_barcodes` (`id`, `ticket_id`, `barcode_value`, `scan_status`, `scan_time`, `scanned_by`) VALUES
(5, 45, 'VT000045-9f4b86', 'unscanned', NULL, NULL),
(6, 46, 'VT000046-3cab01', 'scanned', '2025-06-16 12:43:49', 38),
(7, 47, 'VT000047-24e2b8', 'scanned', '2025-06-16 12:43:31', 38),
(8, 48, 'VT000048-5f7b02', 'scanned', '2025-06-16 12:38:18', 3),
(9, 49, 'VT000049-815b3c', 'scanned', '2025-06-16 12:38:13', 3),
(10, 50, 'VT000050-970ba3', 'scanned', '2025-06-16 12:38:09', 3),
(11, 51, 'VT000051-ec17fe', 'scanned', '2025-06-16 12:48:55', 38),
(12, 52, 'VT000052-f36935', 'unscanned', NULL, NULL),
(13, 53, 'VT000053-449ecd', 'unscanned', NULL, NULL),
(14, 54, 'VT000054-65353d', 'scanned', '2025-06-16 12:48:48', 38),
(15, 55, 'VT000055-012ebf', 'scanned', '2025-06-16 12:48:58', 38),
(16, 56, 'VT000056-c6f098', 'scanned', '2025-06-16 20:40:26', 3),
(17, 57, 'VT000057-fdfae3', 'unscanned', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','manager','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_token_expiry` datetime DEFAULT NULL,
  `last_login_session` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `login_lockout` datetime DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `created_at`, `reset_token`, `reset_token_expiry`, `is_verified`, `verification_token`, `verification_token_expiry`, `last_login_session`, `login_attempts`, `login_lockout`, `security_answer`) VALUES
(1, 'MangJuan', 'jayaruntalanb22@gmail.com', '$2y$10$fonzPYUIxsFgGDdkRr.44.pl6H13B45dTMP0gNFGSN4Lyx3i6d/2m', 'user', '2025-04-25 11:24:44', '1439cd230d2fc711e2c1f9cc2f01c2ae951378aaa60b6c8f9c2ce6b34537c9ed', '2025-05-10 07:53:53', 1, NULL, NULL, '2025-06-23 09:01:08', 0, NULL, 'hhello'),
(2, 'Tetsuko', 'jayaruntalanb223@gmail.com', '$2y$10$MgmDjABcb5PwU55oYOBQd.Amrm/h15Wkd.OgVfeDCkh5xUNEUhvra', 'admin', '2025-04-26 02:33:30', NULL, NULL, 1, NULL, NULL, '2025-06-19 21:26:59', 0, NULL, NULL),
(3, 'Seiko', 'mourned3@gmail.com', '$2y$10$DRw4cfxEKPMelXGJ0lEeauJzuWuDoutP5vypQ/FvmMEKLC4CUcRga', 'manager', '2025-04-28 03:22:32', NULL, NULL, 1, NULL, NULL, '2025-06-19 21:35:19', 0, NULL, 'roc'),
(38, 'Meiko', 'Meiko33@gmail.com', '$2y$10$zv5dG/oq31dN8S7iJzIRDuc.cChShKo4.XGJzIfUq9xas4oXzjlGa', 'manager', '2025-06-16 04:39:11', NULL, NULL, 1, NULL, NULL, '2025-06-16 12:48:40', 0, NULL, NULL),
(39, 'Evangelist', 'eqweqweqweqw@gmail.com', '$2y$10$KZwxNWTht5ocen1Zy11SpeExUWOJoPtwlkQeN10Aeb2fBLo2dqwcK', 'user', '2025-06-16 04:45:31', NULL, NULL, 1, NULL, NULL, '2025-06-16 12:53:01', 0, NULL, NULL),
(92, 'Shinrasdasdasdasd', 'jayaruntalaShinrasdasdasdasdShinrasdasdasdasdnb22@gmail.com', '$2y$10$QKPjSCarBeQbvj0J9BVQ9uZ1RmUojtBXfiNKLzN.OPQhfFh0/dwMa', 'user', '2025-06-20 21:56:18', NULL, NULL, 0, '028514', '2025-06-21 00:06:18', NULL, 0, NULL, '$2y$10$04uL/Nxfna/VF0aytpQX0et4cGfU9WKaDfm5On7Q5RzaBMmAfqXRK'),
(94, 'jayaruntalansdsdsds', 'jayaruntalanbjayaruntalansdsdsds223@gmail.com', '$2y$10$yJ/0VckKxRqP.Uk6EBp/O.hmNPKfYKP1O2SW.rsdzHfGnc47S9OPu', 'user', '2025-06-20 21:59:32', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, '$2y$10$NXfArnHtzsHEerm60xmG/uwqa7ID/z7rWlq/sybE4GiUhuMtGO7gK'),
(96, 'xczxczxczxczc', 'jayaruntalxczxczxczxczcanb22@gmail.com', '$2y$10$DuJ/DY/NZN7lIWWukjjxSuZz0P6.Q51hjeGlEQJVdnyX5p1PxizXW', 'user', '2025-06-20 22:00:41', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, '$2y$10$35sdvdnKbGGbI7Yj48364.En22dZxUdJpsoSv2tsAcUPBX5SksP7G'),
(98, 'dasdddddd', 'mournedasddddddd3@gmail.com', '$2y$10$ImARMQqFAiOBBwpeThEDseKaVeMBu3WXv2Mjgu3qowtR2Kwsf6lfi', 'user', '2025-06-20 22:02:58', NULL, NULL, 0, '840069', '2025-06-21 00:12:57', NULL, 0, NULL, '$2y$10$3jdhyBJxxQfNDzSh8w3DVOjvabHSQRxBwu0gea2XOAg1qs8tK4QYW'),
(100, 'asdasdddddd', 'jayaruasdasddddddntalanb22@gmail.com', '$2y$10$QBdEDyBLzdPF8KeBFKdZKumVrQwooozwWOl6fx1nGreiRkwEskCD6', 'user', '2025-06-20 22:03:37', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, '$2y$10$Y1ILG031Qr6bqk0pcJyC3OTBv2T6KJ4p1G716fPQtR0kQKOnRPAU.'),
(102, 'jayaruntalansddsadasd', 'jayaruntalasdsadnb22@gmail.com', '$2y$10$3d0qI0kxkp0Zu8zJ3ewOTOf1PGfDKHe7M324UVJx6qdPWBGSwGXwq', 'user', '2025-06-23 00:12:35', NULL, NULL, 0, '832042', '2025-06-23 02:22:34', NULL, 0, NULL, '$2y$10$h8LIXWfJg/ngkp63/p0/8u8OFbLx7CQR5qu5ZT58jdHxLQoe0/pMG');

-- --------------------------------------------------------

--
-- Table structure for table `vans`
--

CREATE TABLE `vans` (
  `id` varchar(20) NOT NULL,
  `license_plate` varchar(20) NOT NULL,
  `model` varchar(100) NOT NULL,
  `terminal_id` int(11) NOT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `status` enum('active','maintenance','inactive') NOT NULL DEFAULT 'active',
  `driver_name` varchar(100) DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_boundary` decimal(10,2) DEFAULT 0.00,
  `boundary_date` date DEFAULT NULL,
  `boundary_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vans`
--

INSERT INTO `vans` (`id`, `license_plate`, `model`, `terminal_id`, `destination_id`, `status`, `driver_name`, `last_maintenance`, `notes`, `created_at`, `current_boundary`, `boundary_date`, `boundary_count`) VALUES
('1', '123', 'Toyota HiAce Gray Van', 1, NULL, 'active', 'Juan Dela Cruz', '0000-00-00', '', '2025-06-04 04:58:27', 3750.00, '2025-06-10', 3),
('10', 'BCD890', 'Toyota HiAce White Van', 2, NULL, 'maintenance', 'Alfredo Santiago', '2025-03-22', 'For sale', '2025-04-10 14:30:05', 0.00, NULL, 0),
('11', 'EFG123', 'Toyota HiAce White Van', 1, NULL, 'active', 'Roberto Aquino', '2025-05-10', 'New battery installed', '2025-05-12 07:22:18', 1100.00, '2025-06-10', 1),
('12', 'HIJ456', 'Toyota HiAce White Van', 2, NULL, 'active', 'Luzviminda Cortez', '2025-04-28', 'Regular cleaning service', '2025-05-05 10:15:42', 0.00, NULL, 0),
('13', 'KLM789', 'Toyota HiAce White Van', 1, NULL, 'active', 'Armando Salazar', '2025-03-15', 'Brake system check', '2025-04-01 13:30:55', 0.00, NULL, 0),
('14', 'NOP012', 'Toyota HiAce Gray Van', 1, NULL, 'active', 'Corazon Navarro', '0000-00-00', '', '2025-06-03 08:45:33', 0.00, NULL, 0),
('15', 'QRS345', 'Toyota HiAce White Van', 1, NULL, 'inactive', 'Gregorio Lim', '2025-02-20', 'Waiting for parts', '2025-03-10 15:20:10', 0.00, NULL, 0),
('16', 'TUV678', 'Toyota HiAce White Van', 2, NULL, 'active', 'Rosalinda Ortega', '2025-05-22', 'Oil change completed', '2025-05-25 09:05:27', 1500.00, '2025-06-10', 1),
('17', 'WXY901', 'Toyota HiAce White Van', 1, NULL, 'active', 'Dominador Castro', '2025-04-10', 'New wipers installed', '2025-04-15 12:40:19', 0.00, NULL, 0),
('18', 'ZAB234', 'Toyota HiAce Gray Van', 2, NULL, 'maintenance', 'Imelda Romero', '0000-00-00', '', '2025-05-08 14:25:44', 0.00, NULL, 0),
('19', 'CDE567', 'Toyota HiAce White Van', 1, NULL, 'active', 'Rogelio dela Rosa', '2025-06-01', 'Fully serviced', '2025-06-02 06:15:30', 1600.00, '2025-06-10', 1),
('2', 'ABC123', 'Toyota HiAce White Van', 1, NULL, 'active', 'Pedro Santos', '2025-06-04', '', '2025-06-04 05:01:49', 2550.00, '2025-06-10', 2),
('20', 'FGH890', 'Toyota HiAce White Van', 2, NULL, 'active', 'Esmeralda Villanueva', '2025-05-15', 'Seat covers replaced', '2025-05-18 11:10:05', 0.00, NULL, 0),
('3', 'GHI789', 'Toyota HiAce White Van', 1, NULL, 'maintenance', 'Maria Reyes', '2025-03-10', 'Needs engine check', '2025-04-22 13:20:45', 0.00, NULL, 0),
('4', 'JKL012', 'Toyota HiAce White Van', 1, NULL, 'active', 'Josefina Bautista', '2025-05-30', '', '2025-06-01 08:05:12', 850.00, '2025-06-10', 1),
('5', 'MNO345', 'Toyota HiAce White Van', 2, NULL, 'inactive', 'Antonio Lopez', '2025-01-15', 'For repainting', '2025-02-18 11:30:00', 0.00, NULL, 0),
('6', 'PQR678', 'Toyota HiAce White Van', 1, NULL, 'active', 'Ricardo Garcia', '2025-06-01', 'New tires installed', '2025-06-03 15:45:22', 1800.00, '2025-06-10', 1),
('7', 'STU901', 'Toyota HiAce White Van', 1, NULL, 'active', 'Lourdes Mendoza', '2025-05-25', 'AC system serviced', '2025-05-28 09:10:10', 0.00, NULL, 0),
('8', 'VWX234', 'Toyota HiAce White Van', 1, NULL, 'maintenance', 'Fernando Torres', '2025-04-05', 'Transmission issues', '2025-05-15 12:25:40', 0.00, NULL, 0),
('9', 'YZA567', 'Toyota HiAce White Van', 1, NULL, 'maintenance', 'Cristina Ramos', '2025-05-18', 'Regular service', '2025-05-20 06:50:15', 0.00, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `van_assignments`
--

CREATE TABLE `van_assignments` (
  `id` int(11) NOT NULL,
  `van_id` varchar(20) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL COMMENT 'User ID who made the assignment',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('assigned','completed','cancelled') NOT NULL DEFAULT 'assigned',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `van_boundaries`
--

CREATE TABLE `van_boundaries` (
  `id` int(11) NOT NULL,
  `van_id` varchar(50) NOT NULL,
  `passenger_count` int(11) NOT NULL,
  `boundary_amount` decimal(10,2) NOT NULL,
  `boundary_time` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `van_boundaries`
--

INSERT INTO `van_boundaries` (`id`, `van_id`, `passenger_count`, `boundary_amount`, `boundary_time`, `notes`, `created_at`) VALUES
(1, '1', 12, 1500.00, '2025-06-10 08:30:00', 'Morning trip to Makati', '2025-06-10 00:35:22'),
(2, '2', 10, 1200.00, '2025-06-10 09:15:00', 'Regular route', '2025-06-10 01:20:05'),
(3, '1', 8, 1000.00, '2025-06-10 12:45:00', 'Afternoon trip', '2025-06-10 04:50:18'),
(4, '6', 14, 1800.00, '2025-06-10 10:30:00', 'Full capacity', '2025-06-10 02:35:42'),
(5, '11', 9, 1100.00, '2025-06-10 11:20:00', 'School service', '2025-06-10 03:25:15'),
(6, '2', 11, 1350.00, '2025-06-10 14:00:00', 'Airport transfer', '2025-06-10 06:05:30'),
(7, '19', 13, 1600.00, '2025-06-10 07:45:00', 'First trip', '2025-06-09 23:50:10'),
(8, '4', 7, 850.00, '2025-06-10 13:15:00', 'Short route', '2025-06-10 05:20:25'),
(9, '16', 12, 1500.00, '2025-06-10 16:30:00', 'Evening trip', '2025-06-10 08:35:50'),
(10, '1', 10, 1250.00, '2025-06-10 17:45:00', 'Last trip of the day', '2025-06-10 09:50:12');

-- --------------------------------------------------------

--
-- Table structure for table `van_destinations`
--

CREATE TABLE `van_destinations` (
  `van_id` varchar(50) NOT NULL,
  `destination_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `van_id` (`van_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ratings_ibfk_1` (`ticket_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `terminals`
--
ALTER TABLE `terminals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `terminal_id` (`terminal_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `ticket_barcodes`
--
ALTER TABLE `ticket_barcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode_value` (`barcode_value`),
  ADD KEY `ticket_barcodes_ibfk_1` (`ticket_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vans`
--
ALTER TABLE `vans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `terminal_id` (`terminal_id`),
  ADD KEY `fk_vans_destinations` (`destination_id`);

--
-- Indexes for table `van_assignments`
--
ALTER TABLE `van_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_id` (`ticket_id`),
  ADD KEY `van_id` (`van_id`),
  ADD KEY `van_assignments_ibfk_3` (`assigned_by`);

--
-- Indexes for table `van_boundaries`
--
ALTER TABLE `van_boundaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `van_id` (`van_id`);

--
-- Indexes for table `van_destinations`
--
ALTER TABLE `van_destinations`
  ADD PRIMARY KEY (`van_id`,`destination_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `terminals`
--
ALTER TABLE `terminals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `ticket_barcodes`
--
ALTER TABLE `ticket_barcodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `van_assignments`
--
ALTER TABLE `van_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `van_boundaries`
--
ALTER TABLE `van_boundaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD CONSTRAINT `maintenance_records_ibfk_1` FOREIGN KEY (`van_id`) REFERENCES `vans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`terminal_id`) REFERENCES `terminals` (`id`),
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`);

--
-- Constraints for table `ticket_barcodes`
--
ALTER TABLE `ticket_barcodes`
  ADD CONSTRAINT `ticket_barcodes_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vans`
--
ALTER TABLE `vans`
  ADD CONSTRAINT `fk_vans_destinations` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`),
  ADD CONSTRAINT `vans_ibfk_1` FOREIGN KEY (`terminal_id`) REFERENCES `terminals` (`id`);

--
-- Constraints for table `van_assignments`
--
ALTER TABLE `van_assignments`
  ADD CONSTRAINT `van_assignments_ibfk_1` FOREIGN KEY (`van_id`) REFERENCES `vans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `van_assignments_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `van_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `van_boundaries`
--
ALTER TABLE `van_boundaries`
  ADD CONSTRAINT `van_boundaries_ibfk_1` FOREIGN KEY (`van_id`) REFERENCES `vans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `van_destinations`
--
ALTER TABLE `van_destinations`
  ADD CONSTRAINT `van_destinations_ibfk_1` FOREIGN KEY (`van_id`) REFERENCES `vans` (`id`),
  ADD CONSTRAINT `van_destinations_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
