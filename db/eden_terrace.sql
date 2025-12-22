-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2025 at 11:42 AM
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
-- Database: `eden_terrace`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(100) DEFAULT NULL,
  `guest_phone` varchar(20) DEFAULT NULL,
  `booking_type` enum('room','restaurant','food_order') NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `check_in` date DEFAULT NULL,
  `check_out` date DEFAULT NULL,
  `num_guests` int(11) DEFAULT NULL,
  `room_total` decimal(10,2) DEFAULT NULL,
  `table_id` int(11) DEFAULT NULL,
  `reservation_time` datetime DEFAULT NULL,
  `party_size` int(11) DEFAULT NULL,
  `menu_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`menu_items`)),
  `food_total` decimal(10,2) DEFAULT NULL,
  `delivery_room` int(11) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','confirmed','paid','cancelled','completed') DEFAULT 'pending',
  `payment_method` enum('cash','card','paypal','stripe') DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `guest_name`, `guest_email`, `guest_phone`, `booking_type`, `room_id`, `check_in`, `check_out`, `num_guests`, `room_total`, `table_id`, `reservation_time`, `party_size`, `menu_items`, `food_total`, `delivery_room`, `special_requests`, `total_amount`, `status`, `payment_method`, `payment_id`, `notes`, `created_at`, `updated_at`) VALUES
(2, 9, 'Burnaboy', 'burn@gmail.com', '(078) 487-3032', 'room', 2, '2025-12-23', '2025-12-26', 2, 477.00, NULL, NULL, NULL, NULL, NULL, NULL, 'jhkjlgl', 477.00, 'pending', NULL, NULL, NULL, '2025-12-22 09:11:56', '2025-12-22 09:11:56'),
(3, 10, 'Ndahayo', 'ndahayo@gmail.com', '(078) 487-3032', 'room', 3, '2025-12-22', '2025-12-25', 2, 897.00, NULL, NULL, NULL, NULL, NULL, NULL, 'jvkhjlbknlm', 897.00, 'pending', NULL, NULL, NULL, '2025-12-22 09:19:55', '2025-12-22 09:19:55'),
(4, 11, 'Test Guest', 'guest@test.com', '(078) 487-3032', 'room', 1, '2025-12-22', '2025-12-25', 1, 597.00, NULL, NULL, NULL, NULL, NULL, NULL, 'ytucyvkbln', 597.00, 'pending', NULL, NULL, NULL, '2025-12-22 09:58:45', '2025-12-22 09:58:45'),
(5, 13, 'Queen', 'queen@gmail.com', '(078) 487-3039', 'room', 4, '2025-12-22', '2025-12-25', 2, 1497.00, NULL, NULL, NULL, NULL, NULL, NULL, '', 1497.00, 'pending', NULL, NULL, NULL, '2025-12-22 10:02:28', '2025-12-22 10:02:28');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` enum('appetizer','main_course','dessert','beverage','alcohol','special') DEFAULT 'main_course',
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category`, `image_url`, `is_available`, `created_at`) VALUES
(1, 'Truffle Arancini', 'Crispy risotto balls with black truffle and mozzarella', 16.00, 'appetizer', 'https://images.unsplash.com/photo-1563379091339-03246963d9d6?fit=crop&w=400&h=300', 1, '2025-12-22 08:36:59'),
(2, 'Caesar Salad', 'Classic Caesar with romaine, parmesan, and house-made dressing', 14.00, 'appetizer', 'https://images.unsplash.com/photo-1546793665-c74683f339c1?fit=crop&w=400&h=300', 1, '2025-12-22 08:36:59'),
(3, 'Grilled Salmon', 'Atlantic salmon with lemon butter sauce and seasonal vegetables', 32.00, 'main_course', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?fit=crop&w=400&h=300', 1, '2025-12-22 08:36:59'),
(4, 'Wagyu Beef Steak', 'Premium grade wagyu with truffle mashed potatoes', 68.00, 'main_course', 'https://images.unsplash.com/photo-1600891964092-4316c288032e?fit=crop&w=400&h=300', 1, '2025-12-22 08:36:59'),
(5, 'Vegetable Risotto', 'Creamy arborio rice with seasonal mushrooms and herbs', 24.00, 'main_course', 'https://images.unsplash.com/photo-1476124369491-e7addf5db371?fit=crop&w=400&h=300', 1, '2025-12-22 08:36:59'),
(6, 'Chocolate Soufflé', 'Warm chocolate soufflé with vanilla bean ice cream', 18.00, 'dessert', 'https://images.unsplash.com/photo-1563729784474-d77dbb933a9e?fit=crop&w=400&h=300', 1, '2025-12-22 08:36:59'),
(7, 'Crème Brûlée', 'Classic vanilla bean crème brûlée with berries', 14.00, 'dessert', 'https://images.unsplash.com/photo-1558301211-0d8c8ddee6ec?fit=crop&w=400&h=300', 1, '2025-12-22 08:36:59'),
(8, 'House Red Wine', 'Glass of our signature Cabernet Sauvignon', 12.00, 'alcohol', 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?fit=crop&w=400&h=300', 1, '2025-12-22 08:36:59'),
(9, 'Craft Cocktail', 'Seasonal craft cocktail with local ingredients', 16.00, 'alcohol', 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?fit=crop&w=400&h=300', 1, '2025-12-22 08:36:59');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(10) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `capacity` int(11) NOT NULL,
  `amenities` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `name`, `description`, `price_per_night`, `capacity`, `amenities`, `image_url`, `is_available`, `created_at`) VALUES
(1, '101', 'Deluxe Room', 'Spacious room with city view, king bed, and luxury bathroom. Includes free WiFi, flat-screen TV, air conditioning, mini bar, and in-room safe.', 199.00, 2, 'WiFi,TV,Air Conditioning,Mini Bar,Safe,City View', 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?fit=crop&w=800&h=600', 1, '2025-12-22 08:36:59'),
(2, '102', 'Superior Double', 'Comfortable double room with garden view, perfect for business or leisure travelers.', 159.00, 2, 'WiFi,TV,Air Conditioning,Work Desk', 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?fit=crop&w=800&h=600', 1, '2025-12-22 08:36:59'),
(3, '201', 'Executive Suite', 'Luxury suite with separate living area, balcony, and panoramic city views. Includes jacuzzi tub.', 299.00, 3, 'WiFi,TV,Air Conditioning,Minibar,Safe,Jacuzzi,Balcony', 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?fit=crop&w-800&h=600', 1, '2025-12-22 08:36:59'),
(4, '301', 'Presidential Suite', 'Ultimate luxury with panoramic views, living room, dining area, and butler service.', 499.00, 4, 'WiFi,TV,Air Conditioning,Minibar,Safe,Jacuzzi,Butler Service,Dining Area', 'https://images.unsplash.com/photo-1582719508461-905c673771fd?fit=crop&w=800&h=600', 1, '2025-12-22 08:36:59'),
(5, '202', 'Family Room', 'Perfect for families, featuring two double beds and extra space for children.', 249.00, 4, 'WiFi,TV,Air Conditioning,Minibar,Extra Beds', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?fit=crop&w=800&h=600', 1, '2025-12-22 08:36:59');

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `table_number` varchar(10) NOT NULL,
  `capacity` int(11) NOT NULL,
  `location` enum('main_hall','patio','private_room','bar') DEFAULT 'main_hall',
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `table_number`, `capacity`, `location`, `is_available`, `created_at`) VALUES
(1, 'T1', 2, 'patio', 1, '2025-12-22 08:36:59'),
(2, 'T2', 2, 'main_hall', 1, '2025-12-22 08:36:59'),
(3, 'T3', 4, 'main_hall', 1, '2025-12-22 08:36:59'),
(4, 'T4', 4, 'patio', 1, '2025-12-22 08:36:59'),
(5, 'T5', 6, 'private_room', 1, '2025-12-22 08:36:59'),
(6, 'T6', 2, 'bar', 1, '2025-12-22 08:36:59'),
(7, 'T7', 4, 'main_hall', 1, '2025-12-22 08:36:59'),
(8, 'T8', 8, 'private_room', 1, '2025-12-22 08:36:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('guest','admin') DEFAULT 'guest',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `phone`, `address`, `role`, `created_at`) VALUES
(1, 'admin@edenterrace.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', '(123) 456-7890', NULL, 'admin', '2025-12-22 08:36:59'),
(2, 'gihozondahayogermain@gmail.com', '$2y$10$KloglhBy7pBkzh0x34pFVOkwi72e9yGEXRzxuz3V4wcHhjGmnzj4q', 'Germain', '0784873039', NULL, 'guest', '2025-12-22 08:43:14'),
(4, 'gihozonahayogermain@gmail.com', '$2y$10$lhP2c67B/cVbo7YgbzUh0.Ebgty0vmaytb1OTleOqCnhTBlvktG2C', 'IGIHOZO', '0784873030', NULL, 'guest', '2025-12-22 08:46:46'),
(6, 'guest1@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', '(555) 123-4567', NULL, 'guest', '2025-12-22 09:09:35'),
(7, 'guest2@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', '(555) 987-6543', NULL, 'guest', '2025-12-22 09:09:35'),
(9, 'burn@gmail.com', '$2y$10$nMiQa6343Mhzh2z0o5ZYGOun01tbWXK9FdnzIfMMP4sn5bv5qhD1i', 'Burnaboy', '(078) 487-3032', NULL, 'guest', '2025-12-22 09:11:56'),
(10, 'ndahayo@gmail.com', '$2y$10$mr3x4ffkFBj30rpgjb0qxegzmmB5xHlxNbMRoIpdfuRbKehD5KF6q', 'Ndahayo', '(078) 487-3032', NULL, 'guest', '2025-12-22 09:19:55'),
(11, 'guest@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test Guest', '(555) 123-4567', NULL, 'guest', '2025-12-22 09:25:20'),
(13, 'queen@gmail.com', '$2y$10$LDMGdCUZo00EP.Dv/LGATO9HdncSK/xksFynLNO4lxD3FYZ9Lken2', 'Queen', '(078) 487-3039', NULL, 'guest', '2025-12-22 10:02:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `delivery_room` (`delivery_room`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_number` (`table_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_ibfk_4` FOREIGN KEY (`delivery_room`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
