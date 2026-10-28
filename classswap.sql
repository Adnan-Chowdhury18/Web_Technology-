-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2026 at 04:59 PM
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
-- Database: `classswap`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'Motiur Rahman', 'motiurrahman4444444@gmail.com', '$2y$10$von2s2QxuJP1.tmXxyUofu2JJKVI1QQFTHCA90yG5SFSTBB3o9afG', '2026-10-24 00:03:14');

-- --------------------------------------------------------

--
-- Table structure for table `areas`
--

CREATE TABLE `areas` (
  `area_id` int(10) UNSIGNED NOT NULL,
  `area_name` varchar(100) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `areas`
--

INSERT INTO `areas` (`area_id`, `area_name`, `latitude`, `longitude`, `is_active`, `created_at`) VALUES
(1, 'Khulshi', 22.3667000, 91.8183000, 1, '2026-10-15 01:11:30'),
(2, 'GEC', 22.3590000, 91.8296000, 1, '2026-10-15 01:11:30'),
(3, 'Chawkbazar', 22.3570000, 91.8260000, 1, '2026-10-15 01:11:30'),
(4, 'Muradpur', 22.3712000, 91.8282000, 1, '2026-10-15 01:11:30'),
(5, 'Agrabad', 22.3267000, 91.8130000, 1, '2026-10-15 01:11:30'),
(6, 'Bahaddarhat', 22.3660000, 91.8420000, 1, '2026-10-15 01:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `icon` varchar(60) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `icon`, `is_active`, `created_at`) VALUES
(1, 'Textbooks', 'book-half', 1, '2026-10-15 01:11:30'),
(2, 'Reference Books', 'journal-bookmark-fill', 1, '2026-10-15 01:11:30'),
(3, 'Calculators', 'calculator', 1, '2026-10-15 01:11:30'),
(4, 'Laptops and Computers', 'laptop-fill', 1, '2026-10-15 01:11:30'),
(5, 'Mobile Phones', 'phone-fill', 1, '2026-10-15 01:11:30'),
(6, 'Tablets', 'tablet-fill', 1, '2026-10-15 01:11:30'),
(7, 'Electronics and Accessories', 'cpu-fill', 1, '2026-10-15 01:11:30'),
(8, 'Lab Equipment', 'tools', 1, '2026-10-15 01:11:30'),
(9, 'Stationery', 'pencil-fill', 1, '2026-10-15 01:11:30'),
(10, 'Hostel and Room Essentials', 'house-door-fill', 1, '2026-10-15 01:11:30'),
(11, 'Bicycles and Transport', 'bicycle', 1, '2026-10-15 01:11:30'),
(12, 'Bags and Backpacks', 'backpack-fill', 1, '2026-10-15 01:11:30'),
(13, 'Other', 'box-seam-fill', 1, '2026-10-15 01:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `favorite_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`favorite_id`, `user_id`, `product_id`, `created_at`) VALUES
(1, 3, 6, '2026-10-17 17:30:10');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `inquiry_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','closed') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `area_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `item_condition` enum('New','Like New','Good','Fair') NOT NULL,
  `status` enum('Available','Reserved','Sold','Hidden') NOT NULL DEFAULT 'Available',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `user_id`, `category_id`, `area_id`, `title`, `description`, `price`, `item_condition`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'Calculus Early Transcendentals (8th Edition)', 'Lightly used calculus textbook, all pages intact, no markings. Great for first-year engineering students.', 650.00, 'Good', 'Available', '2026-10-15 01:11:30', '2026-10-15 01:11:30'),
(2, 1, 4, 1, 'Dell Inspiron 15 Laptop', 'Core i5, 8GB RAM, 256GB SSD. Used for 1 year, works perfectly. Charger included.', 32000.00, 'Like New', 'Available', '2026-10-15 01:11:30', '2026-10-15 01:11:30'),
(3, 2, 3, 2, 'Casio fx-991EX Scientific Calculator', 'Barely used, bought for a course that no longer needs it.', 1200.00, 'New', 'Available', '2026-10-15 01:11:30', '2026-10-15 01:11:30'),
(4, 2, 9, 2, 'Drafting Set with Geometry Box', 'Complete drafting kit for engineering drawing courses.', 350.00, 'Fair', 'Available', '2026-10-15 01:11:30', '2026-10-15 01:11:30'),
(5, 3, 3, 1, 'Scientific Calculator', 'Using for the error check for this website', 700.00, 'Good', 'Available', '2026-10-16 00:18:04', '2026-10-16 00:18:04'),
(6, 3, 4, 1, 'Laptop', 'error detection', 24000.00, 'Good', 'Available', '2026-10-16 00:20:45', '2026-10-16 00:20:45');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `image_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_path`, `is_main`, `created_at`) VALUES
(1, 5, 'uploads/products/c779edea6791048ac7bf2a3300007844.webp', 1, '2026-10-16 00:18:04'),
(2, 6, 'uploads/products/558b1ba94ac121bbe89c25d9c4b44126.png', 1, '2026-10-16 00:20:45');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `reported_by` int(10) UNSIGNED NOT NULL,
  `reason_category` enum('Spam','Fraud','Prohibited Item','Inappropriate','Other') NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('pending','reviewed','dismissed','action_taken') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash on Meetup','bKash','Nagad','Card') NOT NULL DEFAULT 'Cash on Meetup',
  `payment_status` enum('Pending','Completed','Cancelled','Failed') NOT NULL DEFAULT 'Pending',
  `transaction_status` enum('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `university` varchar(150) DEFAULT NULL,
  `area_id` int(10) UNSIGNED DEFAULT NULL,
  `whatsapp_number` varchar(20) DEFAULT NULL,
  `role` enum('student','moderator') NOT NULL DEFAULT 'student',
  `status` enum('active','suspended','deleted') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `profile_photo`, `university`, `area_id`, `whatsapp_number`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Demo Student One', 'demo.student1@classswap.test', '$2y$10$7EqJtq98hPqEX7fNZaFWoOhi5bUOQ0WgE.5uPy8OZjF64Z1zD9sJO', NULL, 'University of Chittagong', 1, NULL, 'student', 'active', '2026-10-15 01:11:30', '2026-10-15 01:11:30'),
(2, 'Demo Student Two', 'demo.student2@classswap.test', '$2y$10$7EqJtq98hPqEX7fNZaFWoOhi5bUOQ0WgE.5uPy8OZjF64Z1zD9sJO', NULL, 'CUET', 2, NULL, 'student', 'active', '2026-10-15 01:11:30', '2026-10-15 01:11:30'),
(3, 'Motiur Rahman', 'motiurrahman4444444@gmail.com', '$2y$10$701vott2yiIk5bFYRDT3EOVCAxMX1R/4AqQSkRfnLhz.Bus1b55nm', 'uploads/profiles/0d08a54ef78fa1a987c9d93e710ab3c4.jpg', 'Port City International University', 1, NULL, 'student', 'active', '2026-10-16 00:01:53', '2026-10-16 00:01:53'),
(4, 'Abdullah Al Adnan', 'adnanabdullah4488@gmail.com', '$2y$10$F8yLDu.AZEVJ5RQV/3dJquf0u5Ml1bFJ1z6ohTGgQHF/8nECKAeba', 'uploads/profiles/d145254d96c170bbff543cc5c0387890.jpeg', 'Port City International University', 2, NULL, 'student', 'active', '2026-10-28 21:46:36', '2026-10-28 21:46:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`area_id`),
  ADD UNIQUE KEY `uq_area_name` (`area_name`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `uq_category_name` (`category_name`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `uq_user_product` (`user_id`,`product_id`),
  ADD KEY `fk_fav_product` (`product_id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`inquiry_id`),
  ADD KEY `fk_inq_product` (`product_id`),
  ADD KEY `fk_inq_buyer` (`buyer_id`),
  ADD KEY `idx_inq_seller` (`seller_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_products_user` (`user_id`),
  ADD KEY `idx_products_status` (`status`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_area` (`area_id`),
  ADD KEY `idx_products_created` (`created_at`),
  ADD KEY `idx_products_price` (`price`);
ALTER TABLE `products` ADD FULLTEXT KEY `ft_products_title_desc` (`title`,`description`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_images_product` (`product_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_report_product` (`product_id`),
  ADD KEY `fk_report_user` (`reported_by`),
  ADD KEY `idx_reports_status` (`status`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `idx_tx_product` (`product_id`),
  ADD KEY `idx_tx_buyer` (`buyer_id`),
  ADD KEY `idx_tx_seller` (`seller_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_area` (`area_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `area_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `favorite_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `inquiry_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_fav_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `fk_inq_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inq_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inq_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`area_id`),
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_report_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_report_user` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_tx_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_tx_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `fk_tx_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_area` FOREIGN KEY (`area_id`) REFERENCES `areas` (`area_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
