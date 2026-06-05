-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql111.infinityfree.com
-- Generation Time: Jun 05, 2026 at 03:18 AM
-- Server version: 11.4.12-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41903214_agromarketbd`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(22, 9, 9, 1, '2026-05-13 02:09:05');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) NOT NULL,
  `shipping_address` text NOT NULL,
  `phone` varchar(15) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `payment_method`, `shipping_address`, `phone`, `notes`, `created_at`) VALUES
(1, 3, '600.00', 'pending', 'cash', 'Sayednagar(b-block),Panirpump,Vatara,Dhaka\nEuro Tower', '01710340606', '', '2026-05-12 15:06:36'),
(2, 4, '600.00', 'delivered', 'cash', 'Sayednagar,Notunbazar,Dhaka', '01521726708', 'দ্রুত দিয়েন।মেহমান আসবে।', '2026-05-12 15:14:25'),
(3, 4, '1500.00', 'processing', 'cash', 'Sayednagar,Notunbazar,Dhaka', '01521726708', 'দ্রুত দিয়েন। বাসায় সবার জন্য নিচ্ছি।ভালোগুলা দিয়েন।', '2026-05-12 15:49:36'),
(4, 4, '500.00', 'pending', 'cash', 'Sayednagar,Notunbazar,Dhaka', '01521726708', '', '2026-05-12 15:55:59'),
(5, 4, '250.00', 'pending', 'cash', 'Sayednagar,Notunbazar,Dhaka', '01521726708', '', '2026-05-12 16:05:19'),
(6, 4, '1000.00', 'shipped', 'cash', 'Sayednagar,Notunbazar,Dhaka', '01521726708', '', '2026-05-12 22:07:09'),
(7, 7, '75.00', 'delivered', 'cash', 'syednagor,badda,Dhaka', '01317734591', 'delivery taratari chai', '2026-05-13 01:08:35'),
(8, 9, '276.00', 'delivered', 'bkash', 'village:satarkul; house:uiu', '01311000000', 'taja taja aam chai.....', '2026-05-13 01:21:40'),
(9, 9, '320.00', 'delivered', 'nagad', 'village:satarkul; house:uiu', '01311000000', 'kochi lau chai.. naoile ferot pathai dibo', '2026-05-13 01:27:44'),
(10, 7, '80.00', 'delivered', 'cash', 'syednagor,badda,Dhaka', '01317734591', '', '2026-05-13 01:42:59');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `total`) VALUES
(1, 1, 1, 'Orange', 3, '200.00', '600.00'),
(2, 2, 1, 'Orange', 3, '200.00', '600.00'),
(3, 3, 2, 'Tomato', 5, '30.00', '150.00'),
(4, 3, 4, 'লিচু', 270, '5.00', '1350.00'),
(5, 4, 5, 'ব্যানানা ম্যাংগো', 5, '100.00', '500.00'),
(6, 5, 6, 'কাঁঠাল', 5, '50.00', '250.00'),
(7, 6, 7, 'কাটারিভোগ চাল', 10, '100.00', '1000.00'),
(8, 7, 4, 'লিচু', 15, '5.00', '75.00'),
(9, 8, 8, 'misti aam', 4, '69.00', '276.00'),
(10, 9, 10, 'Lau', 4, '80.00', '320.00'),
(11, 10, 11, 'mishtikumra', 1, '80.00', '80.00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `farmer_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(80) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `unit` varchar(30) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `location` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `harvest_date` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `farmer_id`, `name`, `category`, `base_price`, `unit`, `stock`, `location`, `description`, `image`, `harvest_date`, `created_at`) VALUES
(1, 2, 'Orange', 'ফল', '200.00', 'কেজি', 4, 'SayedNagar,Dhaka', 'Khub bhalo, tatka', 'uploads/product_1778623479_2.jpg', '2026-05-12 22:04:39', '2026-05-12 15:04:40'),
(4, 6, 'লিচু', 'ফল', '5.00', 'পিস', 9715, 'দিনাজপুর', 'দিনাজপুরের বিখ্যাত বোম্বাই লিচু।কীটনাশকমুক্ত এবং প্রাকৃতিক সার দিয়ে উৎপাদিত।১০০% ফরমালিনমুক্ত', 'uploads/product_1778625898_6.jpg', '2026-05-12 22:44:58', '2026-05-12 15:44:58'),
(6, 6, 'কাঁঠাল', 'ফল', '50.00', 'পিস', 195, 'দিনাজপুর', 'দিনাজপুর এর অত্যন্ত সুস্বাদু কাঁঠাল।অনেক মজার।', 'uploads/product_1778627069_6.jpg', '2026-05-12 23:04:29', '2026-05-12 16:04:29'),
(7, 6, 'কাটারিভোগ চাল', 'শস্য', '100.00', 'কেজি', 9990, 'দিনাজপুর', 'দিনাজপুরের সবচেয়ে বিখ্যাত চাল সুগন্ধি কাটারিভোগ', 'uploads/product_1778648716_6.jpg', '2026-05-13 05:05:16', '2026-05-12 22:05:16'),
(8, 8, 'misti aam', 'ফল', '69.00', 'কেজি', 496, 'shapahar', 'sei misti aam ek er ko', 'uploads/product_1778660269_8.jpg', '2026-05-13 08:17:50', '2026-05-13 01:17:49'),
(9, 8, 'Kathal', 'ফল', '599.00', 'পিস', 700, 'Rangpur', 'bisal bisal kathal', 'uploads/product_1778660583_8.jpg', '2026-05-13 08:23:04', '2026-05-13 01:23:03'),
(11, 10, 'mishtikumra', 'সবজি', '80.00', 'কেজি', 99, 'nandail,mymensingh', 'mane sera ,shade otuloniyo', 'uploads/product_1778661612_10.jpg', '2026-05-13 08:40:13', '2026-05-13 01:40:12');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('consumer','farmer','admin') NOT NULL DEFAULT 'consumer',
  `phone` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`, `created_at`) VALUES
(1, 'Admin', 'admin@agromarket.com', '$2y$10$roT.j.Sx/15f7i0ZxVhfr.EIY79SJcJZEE6NfX84ABPIStbZgkXF2', 'admin', '01710340606', 'Dhaka, Bangladesh', '2026-05-12 14:58:31'),
(2, 'Snahat Rahman', 'rafi567352@gmail.com', '$2y$10$7Q/1jwqDVBQmSdxZNNJx2uTPqbC3fNKo57g3rmggcsS7b8qxVpXze', 'farmer', '01710340606', 'Sayednagar(b-block),Panirpump,Vatara,Dhaka\nEuro Tower', '2026-05-12 15:02:23'),
(3, 'Sinbaad', 'snahat567349@gmail.com', '$2y$10$itYaQR/f76Adp7Jtjmnh5OH1nXqHiyDU8JUkLrtzv2pGICbDPa8gK', 'consumer', '01710340606', 'Sayednagar(b-block),Panirpump,Vatara,Dhaka\nEuro Tower', '2026-05-12 15:05:47'),
(4, 'Md Sadman Sadat', 'mdsadmansadat2005@gmail.com', '$2y$10$VsSBzhrbsC2HsJXSRSZoje9DsNROSmZ2f.mjmeasda.u3JZpmrm0C', 'consumer', '01521726708', 'Sayednagar,Notunbazar,Dhaka', '2026-05-12 15:10:58'),
(6, 'সাদমান', 'sadmansadatwork2006@gmail.com', '$2y$10$rtz.j4SfDbIe1X.4G8MpEOzkNGCjs3TiQeksBmiPQtIcnPUs2FZrO', 'farmer', '01857579298', 'Patuapara,Dinajpur,Sadar,Dinajpur', '2026-05-12 15:34:15'),
(7, 'nila', 'nusratnila951@gmail.com', '$2y$10$QQ9ATeC4Wz0oxg7hABSkkuRR4llYR5HW90Z/IwJHhYWd90CnHC1oq', 'consumer', '01317734591', 'syednagor,badda,Dhaka', '2026-05-13 01:06:01'),
(8, 'Asik Ali', 'aali@gmail.com', '$2y$10$3zfFIkRXQZk13trvk61dhOt0XgW4utjAQkbwb3/XiN/8UT/BODrte', 'farmer', '01700000000', 'vatara sayed nagar, badda, dhaka', '2026-05-13 01:14:52'),
(9, 'Ashique', 'farmer101@gmail.com', '$2y$10$YleHC9u3l9GpKg8wfWxuRuhgS2a0qNJlKu2YC9/lEQQv7UL5JPISO', 'consumer', '01311000000', 'village:satarkul; house:uiu', '2026-05-13 01:19:47'),
(10, 'nila', 'nusratnila@gmail.com', '$2y$10$8QBJA2GDTx7s9hROzrXZPuR8djfRRu9TgwfXp810s2oprUdDRMluu', 'farmer', '01317734591', 'nandail,mymensingh', '2026-05-13 01:35:29'),
(12, 'masfica', 'masficaalam@gmail.com', '$2y$10$BE7irvliMKYBNT5HqetxDOw3mx5ygfN50IuAHgCwhbIHDxZzxPJKe', 'consumer', '01973952067', 'dhaka', '2026-05-15 20:01:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_user_product` (`user_id`,`product_id`),
  ADD KEY `fk_cart_product` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_user` (`user_id`),
  ADD KEY `idx_order_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_oi_order` (`order_id`),
  ADD KEY `idx_oi_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_farmer` (`farmer_id`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_review_user_product` (`product_id`,`user_id`),
  ADD KEY `fk_review_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
