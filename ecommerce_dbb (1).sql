-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 10:46 AM
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
-- Database: `ecommerce_dbb`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(48, 3, 3, 3, '2025-09-05 14:20:26'),
(49, 3, 6, 1, '2025-09-05 14:20:33'),
(50, 6, 3, 1, '2025-09-05 15:12:12'),
(140, 9, 20, 3, '2025-10-04 05:05:54'),
(141, 9, 26, 2, '2025-10-04 05:05:55'),
(143, 9, 34, 1, '2025-10-04 05:08:23'),
(144, 9, 39, 2, '2025-10-04 05:08:26'),
(145, 9, 37, 1, '2025-10-04 06:25:46'),
(146, 1, 20, 3, '2025-10-04 07:18:16'),
(147, 1, 26, 1, '2025-10-04 07:18:21'),
(148, 1, 30, 1, '2025-10-04 07:18:21');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `parent_id`, `seller_id`) VALUES
(1, 'Herbicides (weed killers)', '', 76, NULL),
(2, 'Fungicides', '', 76, NULL),
(3, 'Rodenticides (rat &amp; mouse control)', '', 3, NULL),
(75, '🌱 By Target Pest', NULL, NULL, NULL),
(76, '🧴 By Product Type', NULL, NULL, NULL),
(77, '🧑‍🌾 By User / Application', NULL, NULL, NULL),
(78, '🧪 By Formulation', NULL, NULL, NULL),
(79, '🔧 Accessories &amp; Equipment', NULL, NULL, NULL),
(80, '🌍 Eco-Friendly &amp; Alternatives', NULL, NULL, NULL),
(81, 'Insecticides', NULL, 76, NULL),
(84, 'Mosquito &amp; Fly Control', NULL, 75, NULL),
(85, 'Agricultural Use', NULL, 77, NULL),
(86, 'Liquid Concentrates', NULL, 78, NULL),
(87, 'Sprayers (manual, battery, knapsack)', NULL, 79, NULL),
(88, 'Organic &amp; Natural Pesticides', NULL, 80, NULL),
(89, 'Molluscicides (snails &amp; slugs)', NULL, 76, NULL),
(90, 'Termiticides', NULL, 76, NULL),
(91, 'Nematicides (worms &amp; soil pests)', NULL, 76, NULL),
(92, 'Growth Regulators / Plant Protectants', NULL, 76, NULL),
(93, 'Organic / Bio-based Pesticides', NULL, 76, NULL),
(94, 'Household Pest Control', NULL, 76, NULL),
(95, 'Cockroach &amp; Ant Control', NULL, 75, NULL),
(96, 'Bed Bug Control', NULL, 75, NULL),
(97, 'Termite Control', NULL, 75, NULL),
(98, 'Rat &amp; Rodent Control', NULL, 75, NULL),
(99, 'Weed Control', NULL, 75, NULL),
(100, 'Crop-Specific Pest Control (rice, corn, fruits, vegetables, etc.)', NULL, 75, NULL),
(101, 'Fungus &amp; Mold Control', NULL, 75, NULL),
(102, 'Residential Use', NULL, 77, NULL),
(103, 'Commercial / Industrial Use', NULL, 77, NULL),
(104, 'Garden &amp; Lawn Care', NULL, 77, NULL),
(105, 'Public Health / Sanitation', NULL, 77, NULL),
(106, 'Aerosols &amp; Sprays', NULL, 78, NULL),
(107, 'Powders &amp; Dusts', NULL, 78, NULL),
(108, 'Granules', NULL, 78, NULL),
(109, 'Baits', NULL, 78, NULL),
(110, 'Traps', NULL, 78, NULL),
(111, 'Foggers &amp; Misting Machines', NULL, 79, NULL),
(112, 'Protective Gear (gloves, masks, suits)', NULL, 79, NULL),
(113, 'Measuring &amp; Mixing Tools', NULL, 79, NULL),
(114, 'Biological Control (beneficial insects, bacteria, etc.)', NULL, 80, NULL),
(115, 'Eco-safe repellents', NULL, 80, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `low_stock_alerts`
--

CREATE TABLE `low_stock_alerts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `current_stock` int(11) DEFAULT NULL,
  `alert_threshold` int(11) DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_resolved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `payment_transaction_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `delivery_date` datetime DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `seller_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `shipped_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `refund_status` enum('pending','processing','completed','failed') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `payment_transaction_id`, `seller_id`, `total_amount`, `status`, `delivery_date`, `shipping_address`, `payment_method`, `payment_status`, `seller_notes`, `created_at`, `updated_at`, `shipped_at`, `cancelled_at`, `tracking_number`, `cancellation_reason`, `refund_status`) VALUES
(1, 3, NULL, NULL, 2466.00, 'delivered', '2025-09-03 22:43:24', '1231231231', 'paypal', 'pending', NULL, '2025-08-31 14:43:24', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(2, 3, NULL, NULL, 1233.00, 'delivered', '2025-09-03 22:47:40', 'asdasdasdasd', 'paypal', 'pending', NULL, '2025-08-31 14:47:40', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(3, 3, NULL, NULL, 6165.00, 'delivered', '2025-09-05 20:26:12', 'asdasdasdasd', 'debit_card', 'pending', NULL, '2025-09-02 12:26:12', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(4, 3, NULL, NULL, 9864.00, 'delivered', '2025-09-05 20:29:57', 'asdasdasdasd', 'paypal', 'pending', NULL, '2025-09-02 12:29:57', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(5, 3, NULL, NULL, 1233.00, 'delivered', '2025-09-05 20:54:20', 'asdasdasdasd', 'debit_card', 'pending', NULL, '2025-09-02 12:54:20', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(6, 3, NULL, NULL, 1233.00, 'delivered', '2025-09-05 20:56:00', 'asdasdasdasd', 'credit_card', 'pending', NULL, '2025-09-02 12:56:00', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(7, 3, NULL, NULL, 3699.00, 'processing', NULL, 'asdasdasdasd', 'debit_card', 'pending', NULL, '2025-09-03 13:07:33', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(8, 3, NULL, NULL, 555.00, 'delivered', '2025-09-06 21:11:16', 'asdasdasdasd', 'debit_card', 'pending', NULL, '2025-09-03 13:11:16', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(9, 3, NULL, NULL, 1233.00, 'pending', NULL, 'asdasdasdasd', 'credit_card', 'pending', NULL, '2025-09-03 13:55:51', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(10, 3, NULL, NULL, 3021.00, 'pending', NULL, 'asdasdasdasd', 'credit_card', 'pending', NULL, '2025-09-05 13:08:39', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(11, 3, NULL, NULL, 1566.00, 'pending', NULL, 'asdasdasdasd', 'credit_card', 'pending', NULL, '2025-09-05 13:08:59', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(12, 8, NULL, NULL, 20300.00, 'pending', NULL, 'asdfasdasd', 'paypal', 'pending', NULL, '2025-09-11 15:17:46', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(13, 8, NULL, NULL, 10000.00, 'pending', NULL, 'asdasdasd', 'credit_card', 'pending', NULL, '2025-09-11 16:00:43', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(14, 8, NULL, NULL, 10700.00, 'pending', NULL, 'asdasdasdasdasd', 'credit_card', 'pending', NULL, '2025-09-12 14:26:28', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(15, 7, NULL, NULL, 315.00, 'pending', NULL, '2323', 'credit_card', 'pending', NULL, '2025-09-13 07:25:19', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(16, 8, NULL, NULL, 167.00, 'pending', NULL, 'asdasdasdasdasd', 'debit_card', 'pending', NULL, '2025-09-13 11:40:31', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(17, 7, NULL, NULL, 11.00, 'processing', NULL, '123123', 'debit_card', 'pending', NULL, '2025-09-13 14:55:43', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(18, 7, NULL, NULL, 15.00, 'processing', NULL, '123123', 'debit_card', 'pending', NULL, '2025-09-13 15:24:36', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(19, 7, NULL, NULL, 2000.00, 'cancelled', NULL, '123123', 'credit_card', 'pending', NULL, '2025-09-13 15:27:12', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(20, 7, NULL, NULL, 11.00, 'pending', NULL, '123123', 'credit_card', 'pending', NULL, '2025-09-13 15:35:11', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(21, 7, NULL, NULL, 2163.00, 'delivered', '2025-09-16 23:35:33', '123123', 'credit_card', 'pending', NULL, '2025-09-13 15:35:33', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(22, 7, NULL, NULL, 11.00, 'cancelled', NULL, '123123', 'cash_on_delivery', 'pending', NULL, '2025-09-13 15:40:40', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(23, 7, NULL, NULL, 11.00, 'processing', NULL, '123123', 'debit_card', 'pending', NULL, '2025-09-13 15:48:53', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(24, 8, NULL, NULL, 2000.00, 'pending', NULL, 'asdasdasdasdasd', 'cash_on_delivery', 'pending', NULL, '2025-09-13 16:22:27', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(25, 8, NULL, NULL, 326.00, 'pending', NULL, 'asdasdasdasdasd', 'cash_on_delivery', 'pending', NULL, '2025-09-13 16:23:47', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(26, 7, NULL, NULL, 300.00, 'pending', NULL, '123123', 'paypal', 'pending', NULL, '2025-09-14 05:05:29', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(27, 9, NULL, NULL, 2000.00, 'processing', NULL, 'efef123', 'debit_card', 'pending', NULL, '2025-09-16 23:57:32', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(28, 9, NULL, NULL, 300.00, 'processing', NULL, 'sdasas', 'debit_card', 'pending', NULL, '2025-09-17 16:22:38', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(29, 9, NULL, NULL, 15.00, 'pending', NULL, 'dd', 'credit_card', 'pending', NULL, '2025-09-18 00:47:15', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(30, 7, NULL, NULL, 15.00, 'pending', NULL, 'sss', 'credit_card', 'pending', NULL, '2025-09-18 00:54:16', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(31, 9, NULL, NULL, 15.00, 'pending', NULL, 'ddd', 'credit_card', 'pending', NULL, '2025-09-18 00:54:59', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(32, 9, NULL, NULL, 11.00, 'processing', NULL, 'ss', 'debit_card', 'pending', NULL, '2025-09-18 01:00:37', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(33, 9, NULL, NULL, 100.00, 'processing', NULL, 'jjjj', 'credit_card', 'pending', NULL, '2025-09-18 11:15:03', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(34, 9, NULL, NULL, 100.00, 'delivered', '2025-09-21 19:19:46', 'ss', 'debit_card', 'pending', NULL, '2025-09-18 11:19:46', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(35, 9, NULL, NULL, 426.00, 'pending', NULL, 'sss', 'debit_card', 'pending', NULL, '2025-09-18 11:25:33', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(36, 9, NULL, NULL, 630.00, 'pending', NULL, 'lkj', 'debit_card', 'pending', NULL, '2025-09-18 11:55:56', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(37, 9, NULL, NULL, 15.00, 'pending', NULL, '121', 'debit_card', 'pending', NULL, '2025-09-18 14:48:56', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(38, 9, NULL, NULL, 11.00, 'pending', NULL, 'ss', 'paypal', 'pending', NULL, '2025-09-18 15:05:47', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(39, 9, NULL, NULL, 15.00, 'pending', NULL, 'sss', 'credit_card', 'pending', NULL, '2025-09-18 15:34:09', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(40, 9, NULL, NULL, 100.00, 'delivered', '2025-09-22 00:23:59', 'ss', 'credit_card', 'pending', NULL, '2025-09-18 16:23:59', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(41, 9, NULL, NULL, 15.00, 'shipped', NULL, 'ss', 'debit_card', 'pending', NULL, '2025-09-18 16:42:39', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(42, 9, NULL, NULL, 15.00, 'delivered', NULL, 'ss', 'credit_card', 'pending', NULL, '2025-09-18 16:43:57', '2025-09-25 10:19:10', NULL, NULL, NULL, NULL, NULL),
(43, 9, NULL, NULL, 15.00, 'delivered', NULL, 'ss', 'paypal', 'pending', NULL, '2025-09-18 17:00:05', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(44, 9, NULL, NULL, 15.00, 'delivered', '2025-09-22 09:09:33', 'fsdf', 'cash_on_delivery', 'pending', NULL, '2025-09-19 01:09:33', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(45, 9, NULL, NULL, 15.00, 'delivered', '2025-09-22 09:36:08', 'ss', 'credit_card', 'pending', NULL, '2025-09-19 01:36:08', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(46, 7, NULL, NULL, 15.00, 'delivered', '2025-09-22 09:45:00', 'hj', 'credit_card', 'pending', NULL, '2025-09-19 01:45:00', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(47, 9, NULL, NULL, 2000.00, 'delivered', '2025-09-22 16:42:10', 'ss', 'debit_card', 'pending', NULL, '2025-09-19 08:42:10', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(48, 9, NULL, NULL, 15.00, 'delivered', '2025-09-22 17:19:23', 'vvv', 'credit_card', 'pending', NULL, '2025-09-19 09:19:23', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(49, 9, NULL, NULL, 15.00, 'delivered', '2025-09-22 17:34:42', 'gg', 'paypal', 'pending', NULL, '2025-09-19 09:34:42', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(50, 9, NULL, NULL, 15.00, 'delivered', '2025-09-22 17:57:07', 'ss', 'paypal', 'pending', NULL, '2025-09-19 09:57:07', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(51, 8, NULL, NULL, 15.00, 'pending', NULL, 'asdasdasdasdasd', 'credit_card', 'pending', NULL, '2025-09-20 10:49:55', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(52, 8, NULL, NULL, 15.00, 'delivered', '2025-09-23 18:57:07', 'asdasdasdasdasd', 'credit_card', 'pending', NULL, '2025-09-20 10:57:07', '2025-09-23 15:38:28', NULL, NULL, NULL, NULL, NULL),
(53, 9, NULL, NULL, 10000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'debit_card', 'pending', NULL, '2025-09-24 12:31:50', '2025-09-25 00:29:50', NULL, NULL, NULL, NULL, NULL),
(54, 9, NULL, NULL, 500000.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'paypal', 'pending', NULL, '2025-09-24 12:37:00', '2025-09-25 00:14:11', NULL, NULL, NULL, NULL, NULL),
(55, 9, NULL, NULL, 10000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'debit_card', 'pending', NULL, '2025-09-24 12:49:28', '2025-09-25 00:29:33', NULL, NULL, NULL, NULL, NULL),
(56, 9, NULL, NULL, 10000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'paypal', 'pending', NULL, '2025-09-25 00:16:10', '2025-09-25 00:28:56', NULL, NULL, NULL, NULL, NULL),
(57, 9, NULL, NULL, 300.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-09-25 00:42:19', '2025-09-25 00:48:42', NULL, NULL, NULL, NULL, NULL),
(58, 9, NULL, NULL, 2000.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'debit_card', 'pending', NULL, '2025-09-25 10:19:49', '2025-09-25 10:28:21', NULL, NULL, NULL, NULL, NULL),
(59, 9, NULL, NULL, 30622.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-09-25 10:32:09', '2025-09-25 10:52:49', NULL, NULL, NULL, NULL, NULL),
(60, 9, NULL, NULL, 2000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'debit_card', 'pending', NULL, '2025-09-25 10:50:53', '2025-10-02 11:27:41', NULL, NULL, NULL, NULL, NULL),
(61, 9, NULL, NULL, 10000.00, 'processing', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-09-25 10:54:07', '2025-09-25 15:35:12', NULL, NULL, NULL, NULL, NULL),
(62, 9, NULL, NULL, 10000.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-09-25 11:15:16', '2025-09-25 15:34:38', NULL, NULL, NULL, NULL, NULL),
(63, 9, NULL, NULL, 30000.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-09-25 11:23:09', '2025-09-25 15:29:09', NULL, NULL, NULL, NULL, NULL),
(64, 9, NULL, NULL, 15.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-09-25 11:37:02', '2025-09-25 15:28:50', NULL, NULL, NULL, NULL, NULL),
(65, 9, NULL, NULL, 300.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'paypal', 'pending', NULL, '2025-09-25 15:30:11', '2025-10-02 11:27:37', NULL, NULL, NULL, NULL, NULL),
(66, 9, NULL, NULL, 300.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'debit_card', 'pending', NULL, '2025-09-25 15:30:32', '2025-10-02 11:27:33', NULL, NULL, NULL, NULL, NULL),
(67, 9, NULL, NULL, 300.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-09-25 15:32:37', '2025-10-02 11:27:30', NULL, NULL, NULL, NULL, NULL),
(68, 9, NULL, NULL, 10000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-09-25 15:33:03', '2025-10-02 11:27:23', NULL, NULL, NULL, NULL, NULL),
(69, 9, NULL, NULL, 15.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-09-25 15:33:28', '2025-10-02 11:27:26', NULL, NULL, NULL, NULL, NULL),
(70, 9, NULL, NULL, 10000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-09-26 14:27:08', '2025-10-02 11:27:20', NULL, NULL, NULL, NULL, NULL),
(71, 9, NULL, NULL, 10000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-09-26 14:28:09', '2025-10-02 11:27:18', NULL, NULL, NULL, NULL, NULL),
(72, 9, NULL, NULL, 10000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-09-26 14:39:00', '2025-10-02 11:27:14', NULL, NULL, NULL, NULL, NULL),
(73, 9, NULL, NULL, 10000.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-09-26 14:39:38', '2025-09-26 15:09:51', NULL, NULL, NULL, NULL, NULL),
(74, 9, NULL, NULL, 10000.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-09-26 14:47:46', '2025-09-26 15:08:04', NULL, NULL, NULL, NULL, NULL),
(75, 9, NULL, NULL, 10000.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'paypal', 'pending', NULL, '2025-09-26 14:51:24', '2025-09-26 15:07:33', NULL, NULL, NULL, NULL, NULL),
(76, 9, NULL, NULL, 10000.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-09-26 15:52:34', '2025-09-26 15:58:29', NULL, NULL, NULL, NULL, NULL),
(77, 9, NULL, NULL, 10000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-09-26 16:12:14', '2025-10-02 11:27:10', NULL, NULL, NULL, NULL, NULL),
(78, 7, NULL, NULL, 10000.00, 'cancelled', NULL, 'ss', 'cash_on_delivery', 'pending', NULL, '2025-09-26 16:26:03', '2025-10-02 11:27:07', NULL, NULL, NULL, NULL, NULL),
(79, 7, NULL, NULL, 10000.00, 'cancelled', NULL, 'dd', 'cash_on_delivery', 'pending', NULL, '2025-09-26 16:32:58', '2025-10-02 11:27:04', NULL, NULL, NULL, NULL, NULL),
(80, NULL, NULL, NULL, 2000.00, 'pending', NULL, 'AddressAddressAddressAddressAddress', 'debit_card', 'pending', NULL, '2025-09-28 05:03:27', '2025-09-28 05:03:27', NULL, NULL, NULL, NULL, NULL),
(81, 9, NULL, NULL, 2000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-09-28 06:10:56', '2025-10-02 11:27:00', NULL, NULL, NULL, NULL, NULL),
(82, 9, NULL, NULL, 2000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'debit_card', 'pending', NULL, '2025-09-28 06:11:17', '2025-09-28 07:54:09', NULL, NULL, NULL, NULL, NULL),
(83, 9, NULL, NULL, 2000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'paypal', 'pending', NULL, '2025-09-28 06:11:29', '2025-09-29 16:00:05', NULL, NULL, NULL, NULL, NULL),
(84, 9, NULL, NULL, 2000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-09-28 06:11:40', '2025-09-29 15:59:37', NULL, NULL, NULL, NULL, NULL),
(85, 9, NULL, NULL, 2000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'gcash', 'pending', NULL, '2025-09-28 06:20:35', '2025-09-29 15:57:40', NULL, NULL, NULL, NULL, NULL),
(86, 9, NULL, NULL, 500.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-09-28 07:42:33', '2025-10-02 11:26:57', NULL, NULL, NULL, NULL, NULL),
(87, 9, NULL, NULL, 500.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-09-28 07:45:39', '2025-10-02 11:26:54', NULL, NULL, NULL, NULL, NULL),
(88, 9, NULL, NULL, 2000.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-09-28 07:57:24', '2025-09-28 08:03:54', NULL, NULL, NULL, NULL, NULL),
(89, 9, NULL, NULL, 300.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-09-28 08:06:09', '2025-09-28 08:27:43', NULL, NULL, NULL, NULL, NULL),
(90, 9, NULL, NULL, 10000.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'gcash', 'pending', NULL, '2025-09-28 14:50:00', '2025-09-28 14:51:22', NULL, NULL, NULL, NULL, NULL),
(91, 9, NULL, NULL, 10000.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'gcash', 'pending', NULL, '2025-09-28 15:22:33', '2025-09-29 15:47:34', NULL, NULL, NULL, NULL, NULL),
(92, 9, NULL, NULL, 500.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'debit_card', 'pending', NULL, '2025-09-28 15:30:08', '2025-10-02 11:26:50', NULL, NULL, NULL, NULL, NULL),
(93, 9, NULL, NULL, 10000.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'paypal', 'pending', NULL, '2025-09-28 15:32:10', '2025-10-02 11:26:47', NULL, NULL, NULL, NULL, NULL),
(94, 9, NULL, NULL, 12611.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'debit_card', 'pending', NULL, '2025-09-28 15:34:34', '2025-10-02 11:26:42', NULL, NULL, NULL, NULL, NULL),
(95, 9, NULL, NULL, 360.00, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'gcash', 'pending', NULL, '2025-09-29 08:37:43', '2025-09-30 11:46:38', NULL, NULL, NULL, NULL, NULL),
(96, 9, NULL, NULL, 11.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'paypal', 'pending', NULL, '2025-10-01 12:51:56', '2025-10-01 14:48:29', NULL, NULL, NULL, NULL, NULL),
(97, 9, NULL, NULL, 2137.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'debit_card', 'pending', NULL, '2025-10-01 13:50:24', '2025-10-01 14:24:45', NULL, '2025-10-01 14:24:45', NULL, 'wla lang', NULL),
(98, 9, NULL, NULL, 11.00, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-10-01 13:54:56', '2025-10-01 14:20:09', NULL, '2025-10-01 14:20:09', NULL, 'trip lng nako', NULL),
(99, 9, NULL, NULL, 190.00, 'shipped', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-10-03 14:17:36', '2025-10-03 14:20:39', NULL, NULL, NULL, NULL, NULL),
(100, 9, NULL, NULL, 9046.79, 'cancelled', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-10-04 04:40:47', '2025-10-04 04:45:32', NULL, NULL, NULL, NULL, NULL),
(101, 9, NULL, NULL, 9336.79, 'delivered', NULL, 'AddressAddressAddressAddressAddress', 'credit_card', 'pending', NULL, '2025-10-04 04:42:37', '2025-10-04 04:44:44', NULL, NULL, NULL, NULL, NULL),
(102, 9, NULL, NULL, 760.80, 'pending', NULL, 'AddressAddressAddressAddressAddress', 'cash_on_delivery', 'pending', NULL, '2025-10-04 04:47:14', '2025-10-04 04:47:14', NULL, NULL, NULL, NULL, NULL),
(103, 9, NULL, NULL, 301007.60, 'pending', NULL, 'AddressAddressAddressAddressAddress', 'debit_card', 'pending', NULL, '2025-10-04 04:52:19', '2025-10-04 04:52:19', NULL, NULL, NULL, NULL, NULL),
(109, 12, 7, 7, 2220.00, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 14:57:31', '2025-10-05 14:57:31', NULL, NULL, NULL, NULL, NULL),
(110, 12, 7, 11, 52021.50, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 14:57:31', '2025-10-05 14:57:31', NULL, NULL, NULL, NULL, NULL),
(111, 12, 8, 7, 500.00, 'pending', NULL, '123 Whatever Dr', 'paymongo', 'pending', NULL, '2025-10-05 14:58:13', '2025-10-05 14:58:13', NULL, NULL, NULL, NULL, NULL),
(112, 12, 8, 11, 15027.80, 'pending', NULL, '123 Whatever Dr', 'paymongo', 'pending', NULL, '2025-10-05 14:58:13', '2025-10-05 14:58:13', NULL, NULL, NULL, NULL, NULL),
(113, 12, 9, 7, 100.00, 'pending', NULL, '123 Whatever Dr', 'paymongo', 'pending', NULL, '2025-10-05 15:01:37', '2025-10-05 15:01:37', NULL, NULL, NULL, NULL, NULL),
(114, 12, 9, 11, 16133.10, 'pending', NULL, '123 Whatever Dr', 'paymongo', 'pending', NULL, '2025-10-05 15:01:37', '2025-10-05 15:01:37', NULL, NULL, NULL, NULL, NULL),
(115, 12, 10, 7, 100.00, 'pending', NULL, '123 Whatever Dr', 'paymongo', 'pending', NULL, '2025-10-05 15:04:14', '2025-10-05 15:04:14', NULL, NULL, NULL, NULL, NULL),
(116, 12, 10, 11, 12830.52, 'pending', NULL, '123 Whatever Dr', 'paymongo', 'pending', NULL, '2025-10-05 15:04:14', '2025-10-05 15:04:14', NULL, NULL, NULL, NULL, NULL),
(117, 12, 11, 7, 100.00, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:38:16', '2025-10-05 15:38:16', NULL, NULL, NULL, NULL, NULL),
(118, 12, 11, 11, 2934.35, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:38:16', '2025-10-05 15:38:16', NULL, NULL, NULL, NULL, NULL),
(119, 12, 12, 7, 100.00, 'pending', NULL, '123 Whatever Dr', 'paymongo', 'pending', NULL, '2025-10-05 15:41:34', '2025-10-05 15:41:34', NULL, NULL, NULL, NULL, NULL),
(120, 12, 12, 11, 2934.35, 'pending', NULL, '123 Whatever Dr', 'paymongo', 'pending', NULL, '2025-10-05 15:41:34', '2025-10-05 15:41:34', NULL, NULL, NULL, NULL, NULL),
(121, 12, 13, 7, 100.00, 'pending', NULL, '123 Whatever Dr', 'paymongo', 'pending', NULL, '2025-10-05 15:41:42', '2025-10-05 15:41:42', NULL, NULL, NULL, NULL, NULL),
(122, 12, 13, 11, 2934.35, 'pending', NULL, '123 Whatever Dr', 'paymongo', 'pending', NULL, '2025-10-05 15:41:42', '2025-10-05 15:41:42', NULL, NULL, NULL, NULL, NULL),
(123, 12, 14, 7, 100.00, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:41:54', '2025-10-05 15:41:54', NULL, NULL, NULL, NULL, NULL),
(124, 12, 14, 11, 2934.35, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:41:54', '2025-10-05 15:41:54', NULL, NULL, NULL, NULL, NULL),
(125, 12, 15, 7, 100.00, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:42:03', '2025-10-05 15:42:03', NULL, NULL, NULL, NULL, NULL),
(126, 12, 15, 11, 2934.35, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:42:03', '2025-10-05 15:42:03', NULL, NULL, NULL, NULL, NULL),
(127, 12, 16, 7, 100.00, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:50:47', '2025-10-05 15:50:47', NULL, NULL, NULL, NULL, NULL),
(128, 12, 16, 11, 2934.35, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:50:47', '2025-10-05 15:50:47', NULL, NULL, NULL, NULL, NULL),
(129, 12, 17, 7, 100.00, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:50:50', '2025-10-05 15:50:50', NULL, NULL, NULL, NULL, NULL),
(130, 12, 17, 11, 2934.35, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:50:50', '2025-10-05 15:50:50', NULL, NULL, NULL, NULL, NULL),
(131, 12, 18, 7, 100.00, 'cancelled', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:50:56', '2025-10-07 08:20:45', NULL, '2025-10-07 08:20:45', NULL, 'brokie no money huhu', NULL),
(132, 12, 18, 11, 2934.35, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 15:50:56', '2025-10-05 15:50:56', NULL, NULL, NULL, NULL, NULL),
(133, 12, 19, 7, 100.00, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 17:13:39', '2025-10-05 17:13:39', NULL, NULL, NULL, NULL, NULL),
(134, 12, 19, 11, 2934.35, 'pending', NULL, '123 Whatever Dr', 'gcash', 'pending', NULL, '2025-10-05 17:13:39', '2025-10-05 17:13:39', NULL, NULL, NULL, NULL, NULL),
(135, 12, 20, 7, 100.00, 'pending', NULL, '123 Whatever Dr', 'paymaya', 'pending', NULL, '2025-10-05 17:14:27', '2025-10-05 17:14:27', NULL, NULL, NULL, NULL, NULL),
(136, 12, 20, 11, 2934.35, 'pending', NULL, '123 Whatever Dr', 'paymaya', 'pending', NULL, '2025-10-05 17:14:27', '2025-10-05 17:14:27', NULL, NULL, NULL, NULL, NULL);

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `order_status_change_log` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO order_status_history (order_id, status, notes, updated_by, created_at)
        VALUES (NEW.id, NEW.status, CONCAT('Status changed from ', OLD.status, ' to ', NEW.status), NEW.user_id, NOW());
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_groups`
--

CREATE TABLE `order_groups` (
  `id` int(11) NOT NULL,
  `payment_transaction_id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 2, 2, 1233.00),
(2, 2, 3, 1, 1233.00),
(3, 3, 3, 5, 1233.00),
(4, 4, 3, 8, 1233.00),
(5, 5, 3, 1, 1233.00),
(6, 6, 3, 1, 1233.00),
(7, 7, 3, 3, 1233.00),
(8, 8, 8, 5, 111.00),
(9, 9, 3, 1, 1233.00),
(10, 10, 9, 4, 111.00),
(11, 10, 3, 2, 1233.00),
(12, 10, 6, 1, 111.00),
(13, 11, 3, 1, 1233.00),
(14, 11, 6, 1, 111.00),
(15, 11, 8, 1, 111.00),
(16, 11, 9, 1, 111.00),
(17, 12, 13, 2, 100.00),
(18, 12, 11, 2, 10000.00),
(19, 12, 16, 1, 100.00),
(20, 13, 11, 1, 10000.00),
(21, 14, 16, 2, 100.00),
(22, 14, 13, 2, 100.00),
(23, 14, 11, 1, 10000.00),
(24, 14, 17, 1, 300.00),
(25, 15, 17, 1, 300.00),
(26, 15, 18, 1, 15.00),
(27, 16, 18, 3, 15.00),
(28, 16, 19, 2, 11.00),
(29, 16, 20, 1, 100.00),
(30, 17, 19, 1, 11.00),
(31, 18, 18, 1, 15.00),
(32, 19, 21, 1, 2000.00),
(33, 20, 19, 1, 11.00),
(34, 21, 21, 1, 2000.00),
(35, 21, 20, 1, 100.00),
(36, 21, 19, 3, 11.00),
(37, 21, 18, 2, 15.00),
(38, 22, 19, 1, 11.00),
(39, 23, 19, 1, 11.00),
(40, 24, 21, 1, 2000.00),
(41, 25, 17, 1, 300.00),
(42, 25, 18, 1, 15.00),
(43, 25, 19, 1, 11.00),
(44, 26, 17, 1, 300.00),
(45, 27, 21, 1, 2000.00),
(46, 28, 17, 1, 300.00),
(47, 29, 18, 1, 15.00),
(48, 30, 18, 1, 15.00),
(49, 31, 18, 1, 15.00),
(50, 32, 19, 1, 11.00),
(51, 33, 20, 1, 100.00),
(52, 34, 20, 1, 100.00),
(53, 35, 18, 1, 15.00),
(54, 35, 19, 1, 11.00),
(55, 35, 20, 1, 100.00),
(56, 35, 17, 1, 300.00),
(57, 36, 18, 2, 15.00),
(58, 36, 17, 2, 300.00),
(59, 37, 18, 1, 15.00),
(60, 38, 19, 1, 11.00),
(61, 39, 18, 1, 15.00),
(62, 40, 20, 1, 100.00),
(63, 41, 18, 1, 15.00),
(64, 42, 18, 1, 15.00),
(65, 43, 18, 1, 15.00),
(66, 44, 18, 1, 15.00),
(67, 45, 18, 1, 15.00),
(68, 46, 18, 1, 15.00),
(69, 47, 21, 1, 2000.00),
(70, 48, 18, 1, 15.00),
(71, 49, 18, 1, 15.00),
(72, 50, 18, 1, 15.00),
(73, 51, 18, 1, 15.00),
(74, 52, 18, 1, 15.00),
(75, 53, 22, 1, 10000.00),
(76, 54, 22, 50, 10000.00),
(77, 55, 22, 1, 10000.00),
(78, 56, 22, 1, 10000.00),
(79, 57, 17, 1, 300.00),
(80, 58, 21, 1, 2000.00),
(81, 59, 22, 3, 10000.00),
(82, 59, 17, 2, 300.00),
(83, 59, 19, 2, 11.00),
(84, 60, 21, 1, 2000.00),
(85, 61, 22, 1, 10000.00),
(86, 62, 22, 1, 10000.00),
(87, 63, 22, 3, 10000.00),
(88, 64, 18, 1, 15.00),
(89, 65, 17, 1, 300.00),
(90, 66, 17, 1, 300.00),
(91, 67, 17, 1, 300.00),
(92, 68, 22, 1, 10000.00),
(93, 69, 18, 1, 15.00),
(94, 70, 22, 1, 10000.00),
(95, 71, 22, 1, 10000.00),
(96, 72, 22, 1, 10000.00),
(97, 73, 22, 1, 10000.00),
(98, 74, 22, 1, 10000.00),
(99, 75, 22, 1, 10000.00),
(100, 76, 22, 1, 10000.00),
(101, 77, 22, 1, 10000.00),
(102, 78, 22, 1, 10000.00),
(103, 79, 22, 1, 10000.00),
(104, 80, 21, 1, 2000.00),
(105, 81, 21, 1, 2000.00),
(106, 82, 21, 1, 2000.00),
(107, 83, 21, 1, 2000.00),
(108, 84, 21, 1, 2000.00),
(109, 85, 21, 1, 2000.00),
(110, 86, 23, 1, 500.00),
(111, 87, 23, 1, 500.00),
(112, 88, 21, 1, 2000.00),
(113, 89, 17, 1, 300.00),
(114, 90, 22, 1, 10000.00),
(115, 91, 22, 1, 10000.00),
(116, 92, 23, 1, 500.00),
(117, 93, 22, 1, 10000.00),
(118, 94, 23, 1, 500.00),
(119, 94, 22, 1, 10000.00),
(120, 94, 21, 1, 2000.00),
(121, 94, 20, 1, 100.00),
(122, 94, 19, 1, 11.00),
(123, 95, 18, 4, 15.00),
(124, 95, 24, 3, 100.00),
(125, 96, 19, 1, 11.00),
(126, 97, 18, 1, 15.00),
(127, 97, 19, 2, 11.00),
(128, 97, 20, 1, 100.00),
(129, 97, 21, 1, 2000.00),
(130, 98, 19, 1, 11.00),
(131, 99, 26, 1, 190.00),
(132, 100, 35, 1, 7525.19),
(133, 100, 37, 1, 760.80),
(134, 100, 39, 1, 760.80),
(135, 101, 20, 1, 100.00),
(136, 101, 39, 1, 760.80),
(137, 101, 37, 1, 760.80),
(138, 101, 35, 1, 7525.19),
(139, 101, 26, 1, 190.00),
(140, 102, 37, 1, 760.80),
(141, 103, 35, 40, 7525.19),
(142, 109, 20, 7, 100.00),
(143, 109, 26, 8, 190.00),
(144, 110, 34, 1, 1562.13),
(145, 110, 35, 5, 7525.19),
(146, 110, 39, 3, 760.80),
(147, 110, 37, 4, 760.80),
(148, 110, 30, 1, 4674.80),
(149, 110, 32, 2, 611.42),
(150, 110, 33, 1, 915.39),
(151, 110, 31, 1, 694.79),
(152, 111, 20, 5, 100.00),
(153, 112, 35, 1, 7525.19),
(154, 112, 37, 1, 760.80),
(155, 112, 39, 1, 760.80),
(156, 112, 30, 1, 4674.80),
(157, 112, 32, 1, 611.42),
(158, 112, 31, 1, 694.79),
(159, 113, 20, 1, 100.00),
(160, 114, 34, 1, 1562.13),
(161, 114, 35, 1, 7525.19),
(162, 114, 39, 1, 760.80),
(163, 114, 30, 1, 4674.80),
(164, 114, 33, 1, 915.39),
(165, 114, 31, 1, 694.79),
(166, 115, 20, 1, 100.00),
(167, 116, 34, 1, 1562.13),
(168, 116, 35, 1, 7525.19),
(169, 116, 39, 1, 760.80),
(170, 116, 37, 1, 760.80),
(171, 116, 32, 1, 611.42),
(172, 116, 33, 1, 915.39),
(173, 116, 31, 1, 694.79),
(174, 117, 20, 1, 100.00),
(175, 118, 34, 1, 1562.13),
(176, 118, 37, 1, 760.80),
(177, 118, 32, 1, 611.42),
(178, 119, 20, 1, 100.00),
(179, 120, 34, 1, 1562.13),
(180, 120, 37, 1, 760.80),
(181, 120, 32, 1, 611.42),
(182, 121, 20, 1, 100.00),
(183, 122, 34, 1, 1562.13),
(184, 122, 37, 1, 760.80),
(185, 122, 32, 1, 611.42),
(186, 123, 20, 1, 100.00),
(187, 124, 34, 1, 1562.13),
(188, 124, 37, 1, 760.80),
(189, 124, 32, 1, 611.42),
(190, 125, 20, 1, 100.00),
(191, 126, 34, 1, 1562.13),
(192, 126, 37, 1, 760.80),
(193, 126, 32, 1, 611.42),
(194, 127, 20, 1, 100.00),
(195, 128, 34, 1, 1562.13),
(196, 128, 37, 1, 760.80),
(197, 128, 32, 1, 611.42),
(198, 129, 20, 1, 100.00),
(199, 130, 34, 1, 1562.13),
(200, 130, 37, 1, 760.80),
(201, 130, 32, 1, 611.42),
(202, 131, 20, 1, 100.00),
(203, 132, 34, 1, 1562.13),
(204, 132, 37, 1, 760.80),
(205, 132, 32, 1, 611.42),
(206, 133, 20, 1, 100.00),
(207, 134, 34, 1, 1562.13),
(208, 134, 37, 1, 760.80),
(209, 134, 32, 1, 611.42),
(210, 135, 20, 1, 100.00),
(211, 136, 34, 1, 1562.13),
(212, 136, 37, 1, 760.80),
(213, 136, 32, 1, 611.42);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `updated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `notes`, `updated_by`, `created_at`) VALUES
(1, 6, 'delivered', 'Status updated to delivered by seller.', 1, '2025-09-03 13:01:00'),
(2, 5, 'delivered', 'Status updated to delivered by seller.', 1, '2025-09-03 13:01:24'),
(3, 4, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:24'),
(4, 3, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:26'),
(5, 4, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:26'),
(6, 3, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:27'),
(7, 2, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:27'),
(8, 1, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:28'),
(9, 1, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:29'),
(10, 1, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:29'),
(11, 2, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:30'),
(12, 3, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:30'),
(13, 4, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:31'),
(14, 4, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:31'),
(15, 4, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:01:31'),
(16, 4, 'delivered', 'Status updated to delivered by seller.', 1, '2025-09-03 13:01:34'),
(17, 3, 'delivered', 'Status updated to delivered by seller.', 1, '2025-09-03 13:01:38'),
(18, 3, 'delivered', 'Status updated to delivered by seller.', 1, '2025-09-03 13:01:39'),
(19, 2, 'delivered', 'Status updated to delivered by seller.', 1, '2025-09-03 13:01:41'),
(20, 1, 'delivered', 'Status updated to delivered by seller.', 1, '2025-09-03 13:01:43'),
(21, 8, 'delivered', 'Status updated to delivered by seller.', 1, '2025-09-03 13:19:47'),
(22, 8, 'delivered', 'Status updated to delivered by seller.', 1, '2025-09-03 13:21:32'),
(23, 8, 'delivered', 'Status updated to delivered by seller.', 1, '2025-09-03 13:23:16'),
(24, 8, 'pending', 'Status updated to pending by seller.', 1, '2025-09-03 13:23:18'),
(25, 8, 'processing', 'Status updated to processing by seller.', 1, '2025-09-03 13:23:21'),
(26, 8, 'shipped', 'Status updated to shipped by seller.', 1, '2025-09-03 13:23:23'),
(27, 8, 'delivered', 'Status updated to delivered by seller.', 1, '2025-09-03 13:23:25'),
(28, 7, 'processing', 'Status updated to processing by seller.', 1, '2025-09-03 13:23:36'),
(29, 14, 'delivered', 'Status updated to delivered by seller.', 7, '2025-09-13 03:21:16'),
(30, 13, 'delivered', 'Status updated to delivered by seller.', 7, '2025-09-13 03:21:18'),
(31, 12, 'delivered', 'Status updated to delivered by seller.', 7, '2025-09-13 03:21:20'),
(32, 23, 'delivered', 'Status updated to delivered by seller.', 7, '2025-09-13 16:07:49'),
(33, 22, 'delivered', 'Status updated to delivered by seller.', 7, '2025-09-13 16:07:51'),
(34, 21, 'delivered', 'Status updated to delivered by seller.', 7, '2025-09-13 16:07:53'),
(35, 18, 'delivered', 'Status updated to delivered by seller.', 7, '2025-09-13 16:07:58'),
(36, 18, 'processing', 'Status updated to processing by seller.', 7, '2025-09-13 16:08:01'),
(37, 17, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-13 16:08:04'),
(38, 17, 'delivered', 'Status updated to delivered by seller.', 7, '2025-09-13 16:08:07'),
(39, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-13 16:33:20'),
(40, 12, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-13 16:33:45'),
(41, 13, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-13 16:33:48'),
(42, 14, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-13 16:33:51'),
(43, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-13 16:34:00'),
(44, 24, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-13 16:34:06'),
(45, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-13 16:34:21'),
(46, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-13 16:34:32'),
(47, 23, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-13 16:34:38'),
(48, 22, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-13 16:34:41'),
(49, 24, 'processing', 'Status updated to processing by seller.', 7, '2025-09-13 16:34:48'),
(50, 23, 'processing', 'Status updated to processing by seller.', 7, '2025-09-13 16:34:50'),
(51, 25, 'processing', 'Status updated to processing by seller.', 7, '2025-09-13 16:34:52'),
(52, 22, 'processing', 'Status updated to processing by seller.', 7, '2025-09-13 16:34:54'),
(53, 13, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:39:10'),
(54, 13, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:42:00'),
(55, 15, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:42:13'),
(56, 15, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:42:25'),
(57, 15, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:43:07'),
(58, 15, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:43:18'),
(59, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:43:33'),
(60, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:44:04'),
(61, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:44:20'),
(62, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:44:23'),
(63, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:44:50'),
(64, 24, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-14 03:45:32'),
(65, 24, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 03:45:34'),
(66, 24, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 03:45:37'),
(67, 24, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:45:42'),
(68, 24, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:47:14'),
(69, 24, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:47:40'),
(70, 24, 'processing', 'Status updated to processing by seller.', 7, '2025-09-14 03:47:50'),
(71, 25, 'cancelled', 'Status updated to cancelled by seller.', 7, '2025-09-14 03:47:57'),
(72, 25, 'delivered', 'Status updated to delivered by seller.', 7, '2025-09-14 03:48:00'),
(73, 25, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-14 03:48:04'),
(74, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:48:06'),
(75, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:50:32'),
(76, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:50:39'),
(77, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:58:42'),
(78, 25, 'processing', 'Status updated to processing by seller.', 7, '2025-09-14 03:58:52'),
(79, 25, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 03:58:53'),
(80, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 03:58:58'),
(81, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:00:57'),
(82, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:01:46'),
(83, 23, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:01:59'),
(84, 23, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:02:28'),
(85, 25, 'processing', 'Status updated to processing by seller.', 7, '2025-09-14 04:03:07'),
(86, 25, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:03:09'),
(87, 25, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:37:22'),
(88, 25, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:37:34'),
(89, 23, 'delivered', 'Status updated to delivered by seller.', 7, '2025-09-14 04:37:35'),
(90, 23, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:37:38'),
(91, 23, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:37:40'),
(92, 23, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:40:21'),
(93, 23, 'processing', 'Status updated to processing by seller.', 7, '2025-09-14 04:40:27'),
(94, 24, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:40:31'),
(95, 23, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:40:38'),
(96, 23, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:41:50'),
(97, 23, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:41:56'),
(98, 13, 'processing', 'Status updated to processing by seller.', 7, '2025-09-14 04:43:42'),
(99, 13, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:43:46'),
(100, 17, 'processing', 'Status updated to processing by seller.', 7, '2025-09-14 04:43:51'),
(101, 14, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:44:07'),
(102, 19, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:44:14'),
(103, 19, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:48:44'),
(104, 23, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-14 04:48:52'),
(105, 23, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:48:55'),
(106, 23, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:49:00'),
(107, 23, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:55:03'),
(108, 23, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-14 04:55:14'),
(109, 23, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:55:19'),
(110, 23, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:55:24'),
(111, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:57:45'),
(112, 24, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:57:48'),
(113, 24, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:59:27'),
(114, 25, 'delivered', 'Status updated to delivered by seller.', 7, '2025-09-14 04:59:35'),
(115, 25, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 04:59:36'),
(116, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 04:59:38'),
(117, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 05:01:28'),
(118, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 05:01:43'),
(119, 25, 'processing', 'Status updated to processing by seller.', 7, '2025-09-14 05:01:51'),
(120, 25, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 05:01:52'),
(121, 25, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 05:01:56'),
(122, 22, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 05:02:21'),
(123, 23, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 05:02:24'),
(124, 23, 'processing', 'Status updated to processing by seller.', 7, '2025-09-14 05:02:43'),
(125, 22, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 05:03:20'),
(126, 22, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 05:05:00'),
(127, 26, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 05:06:38'),
(128, 26, 'confirmed', 'Status updated to confirmed by seller.', 7, '2025-09-14 05:06:46'),
(129, 26, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 05:07:01'),
(130, 26, 'processing', 'Status updated to processing by seller.', 7, '2025-09-14 05:07:13'),
(131, 25, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 05:07:20'),
(132, 25, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 05:08:48'),
(133, 25, 'processing', 'Status updated to processing by seller.', 7, '2025-09-14 05:09:56'),
(134, 25, 'processing', 'Status updated to processing by seller.', 7, '2025-09-14 05:13:43'),
(135, 26, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 05:13:52'),
(136, 26, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-14 05:13:57'),
(137, 24, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 05:14:49'),
(138, 24, 'pending', 'Status updated to pending by seller.', 7, '2025-09-14 05:14:59'),
(139, 12, 'pending', 'Status updated to pending by seller.', 7, '2025-09-16 18:24:33'),
(140, 22, 'cancelled', 'Status updated to cancelled by seller.', 7, '2025-09-16 18:24:37'),
(141, 19, 'cancelled', 'Status updated to cancelled by seller.', 7, '2025-09-16 18:24:41'),
(142, 26, 'pending', 'Status updated to pending by seller.', 7, '2025-09-16 18:40:57'),
(143, 25, 'pending', 'Status updated to pending by seller.', 7, '2025-09-16 18:41:00'),
(144, 27, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-16 23:59:14'),
(145, 27, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 11:25:56'),
(146, 27, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-17 11:26:02'),
(147, 27, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 11:29:45'),
(148, 27, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 11:43:13'),
(149, 27, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 11:43:25'),
(150, 15, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 11:43:31'),
(151, 27, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-17 11:43:48'),
(152, 27, 'processing', 'Status updated to processing by seller.', 7, '2025-09-17 11:44:22'),
(153, 27, 'processing', 'Status updated to processing by seller.', 7, '2025-09-17 11:44:52'),
(154, 27, 'processing', 'Status updated to processing by seller.', 7, '2025-09-17 11:45:23'),
(155, 28, 'cancelled', 'Order cancelled by customer within grace period', 9, '2025-09-17 16:26:39'),
(156, 28, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 16:30:27'),
(157, 28, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 16:30:58'),
(158, 28, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 16:31:29'),
(159, 28, 'cancelled', 'Order cancelled by customer within grace period', 9, '2025-09-17 16:31:46'),
(160, 28, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 16:32:00'),
(161, 28, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 16:32:31'),
(162, 28, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 16:33:02'),
(163, 28, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 16:33:33'),
(164, 28, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 16:34:04'),
(165, 28, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 16:34:35'),
(166, 28, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 16:35:06'),
(167, 28, 'pending', 'Status updated to pending by seller.', 7, '2025-09-17 16:35:37'),
(168, 9, 'pending', 'Initial status: pending (migrated from existing data)', 3, '2025-09-03 13:55:51'),
(169, 10, 'pending', 'Initial status: pending (migrated from existing data)', 3, '2025-09-05 13:08:39'),
(170, 11, 'pending', 'Initial status: pending (migrated from existing data)', 3, '2025-09-05 13:08:59'),
(171, 16, 'pending', 'Initial status: pending (migrated from existing data)', 8, '2025-09-13 11:40:31'),
(172, 20, 'pending', 'Initial status: pending (migrated from existing data)', 7, '2025-09-13 15:35:11'),
(173, 28, 'processing', 'Status changed from pending to processing', 9, '2025-09-18 00:49:23'),
(174, 28, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-18 00:49:23'),
(175, 28, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 00:49:57'),
(176, 28, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 00:50:28'),
(177, 28, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 00:50:59'),
(178, 28, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 00:51:30'),
(179, 28, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 00:52:01'),
(180, 28, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 00:52:32'),
(181, 28, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 00:53:03'),
(182, 28, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 00:53:34'),
(183, 28, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 00:53:45'),
(184, 28, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 00:54:04'),
(185, 29, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 00:47:15'),
(186, 30, 'pending', 'Initial status: pending (migrated from existing data)', 7, '2025-09-18 00:54:16'),
(187, 31, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 00:54:59'),
(188, 32, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 01:00:37'),
(192, 32, 'processing', 'Status changed from pending to processing', 9, '2025-09-18 11:13:02'),
(193, 32, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-18 11:13:02'),
(194, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:13:36'),
(195, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:14:07'),
(196, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:14:38'),
(197, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:15:09'),
(198, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:15:40'),
(199, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:16:11'),
(200, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:16:42'),
(201, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:17:13'),
(202, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:17:44'),
(203, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:17:49'),
(204, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:18:19'),
(205, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:18:49'),
(206, 33, 'processing', 'Status changed from pending to processing', 9, '2025-09-18 11:19:17'),
(207, 33, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-18 11:19:17'),
(208, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:19:25'),
(209, 32, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:19:51'),
(210, 34, 'processing', 'Status changed from pending to processing', 9, '2025-09-18 11:19:53'),
(211, 34, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-18 11:19:53'),
(212, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:20:29'),
(213, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:21:00'),
(214, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:21:31'),
(215, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:22:02'),
(216, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:22:33'),
(217, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:23:04'),
(218, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:23:35'),
(219, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:24:06'),
(220, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:24:37'),
(221, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:25:08'),
(222, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:25:38'),
(223, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:25:40'),
(224, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:26:11'),
(225, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:26:42'),
(226, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:27:13'),
(227, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:27:44'),
(228, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:28:15'),
(229, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:28:46'),
(230, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:29:17'),
(231, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:29:48'),
(232, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:30:19'),
(233, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:30:50'),
(234, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:31:21'),
(235, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:31:52'),
(236, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:32:23'),
(237, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:32:54'),
(238, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:33:25'),
(239, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:33:56'),
(240, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:34:27'),
(241, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:34:58'),
(242, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:35:29'),
(243, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:36:00'),
(244, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:36:32'),
(245, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:37:03'),
(246, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:37:34'),
(247, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:38:05'),
(248, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:38:36'),
(249, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:39:07'),
(250, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:39:38'),
(251, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:40:09'),
(252, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:40:40'),
(253, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:41:11'),
(254, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:41:42'),
(255, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:42:13'),
(256, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:42:44'),
(257, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:43:15'),
(258, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:43:46'),
(259, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:44:17'),
(260, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:44:48'),
(261, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:45:19'),
(262, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:45:50'),
(263, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:46:21'),
(264, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:46:52'),
(265, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:54:55'),
(266, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:55:16'),
(267, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:55:46'),
(268, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:56:06'),
(269, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:56:36'),
(270, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:57:06'),
(271, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:57:36'),
(272, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:58:06'),
(273, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:58:36'),
(274, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:59:06'),
(275, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 11:59:36'),
(276, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:00:06'),
(277, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:00:36'),
(278, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:01:07'),
(279, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:01:37'),
(280, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:02:08'),
(281, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:02:39'),
(282, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:03:10'),
(283, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:03:41'),
(284, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:04:12'),
(285, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:04:43'),
(286, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:05:14'),
(287, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:05:45'),
(288, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:06:16'),
(289, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:06:47'),
(290, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:07:18'),
(291, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:07:49'),
(292, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:08:20'),
(293, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:08:51'),
(294, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:09:22'),
(295, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:09:53'),
(296, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:10:24'),
(297, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:10:55'),
(298, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:28:22'),
(299, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:28:52'),
(300, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:29:22'),
(301, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:29:53'),
(302, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:30:23'),
(303, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:30:53'),
(304, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:31:23'),
(305, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:31:53'),
(306, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:32:23'),
(307, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:32:53'),
(308, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:33:23'),
(309, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:33:54'),
(310, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:34:25'),
(311, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:34:56'),
(312, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:35:27'),
(313, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:35:58'),
(314, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:36:29'),
(315, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:37:00'),
(316, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:37:31'),
(317, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:38:02'),
(318, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:38:32'),
(319, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:39:02'),
(320, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:39:32'),
(321, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:40:02'),
(322, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:40:32'),
(323, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:41:02'),
(324, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:41:32'),
(325, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:42:03'),
(326, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:42:33'),
(327, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:43:03'),
(328, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:43:33'),
(329, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:44:04'),
(330, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:44:35'),
(331, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:45:06'),
(332, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:45:37'),
(333, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:46:08'),
(334, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:46:39'),
(335, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:47:10'),
(336, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:47:41'),
(337, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:48:12'),
(338, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:48:43'),
(339, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:49:14'),
(340, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:49:45'),
(341, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:50:16'),
(342, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:50:47'),
(343, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:51:18'),
(344, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:51:49'),
(345, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:52:20'),
(346, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 12:52:51'),
(347, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:11:48'),
(348, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:12:19'),
(349, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:12:50'),
(350, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:13:21'),
(351, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:13:52'),
(352, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:14:23'),
(353, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:14:32'),
(354, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:15:02'),
(355, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:15:32'),
(356, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:15:40'),
(357, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:16:10'),
(358, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:16:40'),
(359, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:17:10'),
(360, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:17:40'),
(361, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:18:10'),
(362, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:18:40'),
(363, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:19:10'),
(364, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:19:40'),
(365, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:20:10'),
(366, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:20:41'),
(367, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:21:11'),
(368, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:21:41'),
(369, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:22:11'),
(370, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:22:42'),
(371, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:23:13'),
(372, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:23:44'),
(373, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:24:15'),
(374, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:24:46'),
(375, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:25:17'),
(376, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:25:48'),
(377, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:26:19'),
(378, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:26:50'),
(379, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:27:21'),
(380, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:27:52'),
(381, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:28:23'),
(382, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:28:54'),
(383, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:29:25'),
(384, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:29:56'),
(385, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:30:27'),
(386, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:30:58'),
(387, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:31:29'),
(388, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 13:32:00'),
(389, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:46:30'),
(390, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:47:00'),
(391, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:47:30'),
(392, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:48:01'),
(393, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:48:32'),
(394, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:49:03'),
(395, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:49:34'),
(396, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:50:05'),
(397, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:50:36'),
(398, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:51:07'),
(399, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:51:38'),
(400, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:52:09'),
(401, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:52:40'),
(402, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:53:11'),
(403, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:53:42'),
(404, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:54:13'),
(405, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:54:44'),
(406, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:55:15'),
(407, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:55:46'),
(408, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:56:17'),
(409, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:56:48'),
(410, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:57:19'),
(411, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:57:50'),
(412, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:58:21'),
(413, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:58:52'),
(414, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:59:23'),
(415, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 14:59:54'),
(416, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:00:25'),
(417, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:00:56'),
(418, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:01:27'),
(419, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:01:58'),
(420, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:02:29'),
(421, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:03:00'),
(422, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:03:31'),
(423, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:04:03'),
(424, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:04:34'),
(425, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:05:05'),
(427, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:05:36'),
(428, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:06:07'),
(429, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:06:38'),
(430, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:07:09'),
(431, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:07:39'),
(432, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:08:10'),
(433, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:08:41'),
(434, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:09:12'),
(435, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:09:43'),
(436, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:10:14'),
(437, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:10:45'),
(438, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:11:16'),
(439, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:11:47'),
(440, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:12:18'),
(441, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:12:49'),
(442, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:13:20'),
(443, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:13:51'),
(444, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:14:22'),
(445, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:14:53'),
(446, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:15:24'),
(447, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:15:55'),
(448, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:16:26'),
(449, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:16:57'),
(450, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:17:27'),
(451, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:17:58'),
(452, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:18:29'),
(453, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:19:00'),
(454, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:19:31'),
(455, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:20:02'),
(456, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:20:33'),
(457, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:21:04'),
(458, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:21:35'),
(459, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:22:06'),
(460, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:22:37'),
(461, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:23:08'),
(462, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:23:39'),
(463, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:24:10'),
(464, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:24:41'),
(465, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:25:12'),
(466, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:25:43'),
(467, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:26:14'),
(468, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:26:45'),
(469, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:27:16'),
(470, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:27:47'),
(471, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:28:18'),
(472, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:28:49'),
(473, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:29:20'),
(474, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:29:51'),
(475, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:30:22'),
(476, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:30:53'),
(477, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:31:24'),
(478, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:31:55'),
(479, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:32:26'),
(480, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:32:57'),
(481, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:33:28'),
(482, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:33:59'),
(483, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:34:30'),
(484, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:35:01'),
(485, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:35:32'),
(486, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:36:03'),
(487, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:36:34'),
(488, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:37:05'),
(489, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:37:36'),
(490, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:38:07'),
(491, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:38:38'),
(492, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:39:03'),
(493, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:39:17'),
(494, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:39:47'),
(495, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:40:17'),
(496, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:40:40'),
(497, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:41:11'),
(498, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:41:42'),
(499, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:41:51'),
(500, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:42:22'),
(501, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:42:54'),
(502, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:43:17'),
(503, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:43:47'),
(504, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:44:18'),
(505, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:44:49'),
(506, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:45:20'),
(507, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:45:51'),
(508, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:46:22'),
(509, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:46:53'),
(510, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:47:24'),
(511, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:47:54'),
(512, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:48:25'),
(513, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:48:55'),
(514, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:49:26'),
(515, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:49:57'),
(516, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:50:28'),
(517, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:50:59'),
(518, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:51:30'),
(519, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:52:01'),
(520, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:52:32'),
(521, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:53:03'),
(522, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:53:34'),
(523, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:54:05'),
(524, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:54:36'),
(525, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:55:07'),
(526, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:55:38'),
(527, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:56:09'),
(528, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:56:40'),
(529, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:57:11'),
(530, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:57:42'),
(531, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:58:13'),
(532, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:58:44'),
(533, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:59:15'),
(534, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 15:59:46'),
(535, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:00:17'),
(536, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:00:48'),
(537, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:01:19'),
(538, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:01:50'),
(539, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:02:21'),
(540, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:02:52'),
(541, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:03:23'),
(542, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:03:54'),
(543, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:04:25'),
(544, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:04:56'),
(545, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:05:27'),
(546, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:05:58'),
(547, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:06:29'),
(548, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:07:05'),
(549, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:07:36');
INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `notes`, `updated_by`, `created_at`) VALUES
(550, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:08:07'),
(551, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:08:38'),
(552, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:09:09'),
(553, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:09:40'),
(554, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:10:11'),
(555, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:10:42'),
(556, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:11:13'),
(557, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:11:44'),
(558, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:12:15'),
(559, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:12:46'),
(560, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:22:52'),
(561, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:23:23'),
(562, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:23:30'),
(563, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:24:01'),
(564, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:24:05'),
(565, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:24:08'),
(566, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:24:38'),
(567, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:25:09'),
(568, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:25:39'),
(569, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:26:09'),
(570, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:26:39'),
(571, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:27:10'),
(572, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:27:40'),
(573, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:28:10'),
(574, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:28:40'),
(575, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:29:10'),
(576, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:29:41'),
(577, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:30:11'),
(578, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:30:41'),
(579, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:31:12'),
(580, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:31:43'),
(581, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:32:15'),
(582, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:32:26'),
(583, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:32:56'),
(584, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:33:27'),
(585, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:33:58'),
(586, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:34:29'),
(587, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:35:00'),
(588, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:35:31'),
(589, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:36:01'),
(590, 35, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 11:25:33'),
(591, 36, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 11:55:56'),
(592, 37, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 14:48:56'),
(593, 38, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 15:05:47'),
(594, 39, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 15:34:09'),
(595, 40, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 16:23:59'),
(597, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:36:32'),
(598, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:37:03'),
(599, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:37:35'),
(600, 34, 'processing', 'Status updated to processing by seller.', 7, '2025-09-18 16:37:58'),
(601, 40, 'shipped', 'Status changed from pending to shipped', 9, '2025-09-18 16:38:21'),
(602, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:38:21'),
(603, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:38:51'),
(604, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:39:22'),
(605, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:39:53'),
(606, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:40:24'),
(607, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:40:55'),
(608, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:41:12'),
(609, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:41:30'),
(610, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:41:53'),
(611, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:42:00'),
(612, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:42:31'),
(613, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:43:02'),
(614, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:43:33'),
(615, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:44:04'),
(616, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:44:12'),
(617, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:44:42'),
(618, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:45:12'),
(619, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:45:42'),
(620, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:46:13'),
(621, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:46:44'),
(622, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:47:15'),
(623, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:47:46'),
(624, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:48:17'),
(625, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:48:48'),
(626, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:49:19'),
(627, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:49:50'),
(628, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:50:21'),
(629, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:50:52'),
(630, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:51:23'),
(631, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:51:54'),
(632, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:52:25'),
(633, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:52:56'),
(634, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:53:27'),
(635, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:53:58'),
(636, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:54:29'),
(637, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:55:00'),
(638, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:55:31'),
(639, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:56:02'),
(640, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:56:33'),
(641, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:57:04'),
(642, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:57:35'),
(643, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:57:41'),
(644, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:58:39'),
(645, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:58:44'),
(646, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 16:59:39'),
(647, 40, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-18 17:00:39'),
(648, 41, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 16:42:39'),
(649, 42, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 16:43:57'),
(650, 43, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-18 17:00:05'),
(651, 44, 'pending', 'Initial status: pending (migrated from existing data)', 9, '2025-09-19 01:09:33'),
(655, 46, 'processing', 'Status changed from pending to processing', 7, '2025-09-19 08:46:23'),
(656, 46, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-19 08:46:23'),
(657, 45, 'processing', 'Status changed from pending to processing', 9, '2025-09-19 08:46:27'),
(658, 45, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-19 08:46:27'),
(659, 44, 'processing', 'Status changed from pending to processing', 9, '2025-09-19 08:46:30'),
(660, 44, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-19 08:46:30'),
(661, 43, 'shipped', 'Status changed from pending to shipped', 9, '2025-09-19 08:46:32'),
(662, 43, 'shipped', 'Status updated to shipped by seller.', 7, '2025-09-19 08:46:32'),
(663, 43, 'processing', 'Status changed from shipped to processing', 9, '2025-09-19 08:46:32'),
(664, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:46:32'),
(665, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:47:03'),
(666, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:47:12'),
(667, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:47:43'),
(668, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:48:14'),
(671, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:48:45'),
(672, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:49:16'),
(673, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:49:47'),
(674, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:50:18'),
(675, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:50:49'),
(676, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:51:20'),
(677, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:51:51'),
(678, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:52:22'),
(679, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:52:53'),
(680, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:53:24'),
(681, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:53:55'),
(682, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:54:26'),
(683, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:54:57'),
(684, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:55:28'),
(685, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:55:59'),
(686, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:56:30'),
(687, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:57:01'),
(688, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:57:32'),
(689, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:58:03'),
(690, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:58:34'),
(691, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:59:05'),
(692, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 08:59:36'),
(693, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:00:07'),
(694, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:00:38'),
(695, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:01:09'),
(696, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:01:40'),
(697, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:02:11'),
(698, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:02:42'),
(699, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:03:13'),
(700, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:03:44'),
(701, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:04:15'),
(702, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:04:46'),
(703, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:05:17'),
(704, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:05:48'),
(705, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:06:19'),
(706, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:06:50'),
(707, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:07:21'),
(708, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:08:32'),
(709, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:09:03'),
(710, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:09:34'),
(711, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:10:05'),
(712, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:10:36'),
(713, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:11:07'),
(714, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:11:38'),
(715, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:12:09'),
(716, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:12:40'),
(717, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:13:11'),
(718, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:13:42'),
(719, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:16:35'),
(720, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:17:06'),
(721, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:17:37'),
(722, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:18:08'),
(724, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:18:39'),
(725, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:19:10'),
(726, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:19:36'),
(727, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:20:06'),
(728, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:20:37'),
(729, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:21:08'),
(730, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:21:39'),
(731, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:22:10'),
(732, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:22:41'),
(733, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:23:12'),
(734, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:23:43'),
(735, 43, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:24:44'),
(736, 48, 'processing', 'Status changed from pending to processing', 9, '2025-09-19 09:24:59'),
(737, 48, 'processing', 'Status updated to processing by seller. Order automatically confirmed.', 7, '2025-09-19 09:24:59'),
(738, 48, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:25:33'),
(739, 48, 'processing', 'Status updated to processing by seller.', 7, '2025-09-19 09:26:04'),
(740, 48, 'processing', 'Status updated from processing to processing by seller.', 7, '2025-09-19 09:26:29'),
(741, 48, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-19 09:26:44'),
(742, 48, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-19 09:26:44'),
(743, 48, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-19 09:27:17'),
(744, 48, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-19 09:27:17'),
(745, 48, 'shipped', 'Status changed from delivered to shipped', 9, '2025-09-19 09:27:20'),
(746, 48, 'shipped', 'Status updated from delivered to shipped by seller.', 7, '2025-09-19 09:27:20'),
(747, 48, 'shipped', 'Status updated from shipped to shipped by seller.', 7, '2025-09-19 09:27:53'),
(748, 48, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-19 09:27:59'),
(749, 48, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-19 09:27:59'),
(750, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:28:32'),
(751, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:29:07'),
(752, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:29:41'),
(753, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:30:15'),
(754, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:30:50'),
(755, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:31:39'),
(756, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:32:13'),
(757, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:32:47'),
(758, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:33:21'),
(759, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:33:55'),
(760, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:34:45'),
(761, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:35:00'),
(762, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:35:21'),
(763, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:35:55'),
(764, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:36:28'),
(765, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:37:03'),
(766, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:37:37'),
(767, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:38:35'),
(768, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:39:09'),
(769, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:39:43'),
(770, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:40:17'),
(771, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:40:29'),
(772, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:41:02'),
(773, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:41:37'),
(774, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:42:14'),
(775, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:42:48'),
(776, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:43:22'),
(777, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:43:56'),
(778, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:44:30'),
(779, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:45:05'),
(780, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:45:39'),
(781, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:46:14'),
(782, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:46:49'),
(783, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:47:23'),
(784, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:48:12'),
(785, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:48:47'),
(786, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:49:12'),
(787, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:49:46'),
(788, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:50:21'),
(789, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:50:55'),
(790, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:51:29'),
(791, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:52:03'),
(792, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:52:37'),
(793, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:53:11'),
(794, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:53:45'),
(795, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:54:19'),
(796, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:54:53'),
(797, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:55:27'),
(798, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:56:00'),
(799, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:56:33'),
(800, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:56:46'),
(801, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:57:19'),
(802, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:57:53'),
(803, 48, 'delivered', 'Status updated from delivered to delivered by seller.', 7, '2025-09-19 09:58:03'),
(804, 48, 'pending', 'Status changed from delivered to pending', 9, '2025-09-19 09:58:15'),
(805, 48, 'pending', 'Status updated from delivered to pending by seller.', 7, '2025-09-19 09:58:15'),
(806, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 09:58:41'),
(807, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 09:59:15'),
(808, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 09:59:49'),
(809, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 10:00:23'),
(810, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 10:00:56'),
(811, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 10:01:30'),
(812, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 10:02:03'),
(813, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 10:02:08'),
(814, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 10:02:42'),
(815, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 10:03:15'),
(816, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 10:03:48'),
(817, 48, 'pending', 'Status updated from pending to pending by seller.', 7, '2025-09-19 10:04:21'),
(818, 48, 'shipped', 'Status changed from pending to shipped', 9, '2025-09-19 10:04:30'),
(819, 48, 'shipped', 'Status updated from pending to shipped by seller.', 7, '2025-09-19 10:04:30'),
(820, 48, 'shipped', 'Status updated from shipped to shipped by seller.', 7, '2025-09-19 10:05:04'),
(821, 48, 'shipped', 'Status updated from shipped to shipped by seller.', 7, '2025-09-19 10:05:37'),
(822, 48, 'shipped', 'Status updated from shipped to shipped by seller.', 7, '2025-09-19 10:06:10'),
(823, 48, 'shipped', 'Status updated from shipped to shipped by seller.', 7, '2025-09-19 10:06:43'),
(824, 48, 'shipped', 'Status updated from shipped to shipped by seller.', 7, '2025-09-19 10:07:17'),
(825, 48, 'shipped', 'Status updated from shipped to shipped by seller.', 7, '2025-09-19 10:07:51'),
(826, 48, 'shipped', 'Status updated from shipped to shipped by seller.', 7, '2025-09-19 10:08:25'),
(827, 50, 'processing', 'Status changed from pending to processing', 9, '2025-09-19 10:12:30'),
(828, 50, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-19 10:12:30'),
(829, 50, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-19 10:12:39'),
(830, 50, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-19 10:12:39'),
(831, 50, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-19 10:12:49'),
(832, 50, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-19 10:12:49'),
(833, 49, 'processing', 'Status changed from pending to processing', 9, '2025-09-19 11:14:49'),
(834, 49, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-19 11:14:49'),
(835, 52, 'processing', 'Status changed from pending to processing', 8, '2025-09-21 07:19:21'),
(836, 52, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-21 07:19:21'),
(837, 52, 'shipped', 'Status changed from processing to shipped', 8, '2025-09-21 07:19:45'),
(838, 52, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-21 07:19:45'),
(839, 52, 'delivered', 'Status changed from shipped to delivered', 8, '2025-09-21 07:20:03'),
(840, 52, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-21 07:20:04'),
(841, 49, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-21 07:20:25'),
(842, 49, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-21 07:20:25'),
(843, 49, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-21 07:20:35'),
(844, 49, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-21 07:20:35'),
(845, 48, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-21 07:40:05'),
(846, 48, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-21 07:40:05'),
(847, 47, 'processing', 'Status changed from pending to processing', 9, '2025-09-21 08:04:44'),
(848, 47, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-21 08:04:44'),
(849, 47, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-21 08:04:55'),
(850, 47, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-21 08:04:55'),
(851, 46, 'shipped', 'Status changed from processing to shipped', 7, '2025-09-21 08:05:03'),
(852, 46, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-21 08:05:03'),
(853, 47, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-21 08:05:16'),
(854, 47, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-21 08:05:16'),
(855, 45, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-21 08:06:06'),
(856, 45, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-21 08:06:06'),
(857, 34, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-21 08:06:15'),
(858, 34, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-21 08:06:15'),
(859, 40, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-21 08:06:25'),
(860, 40, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-21 08:06:25'),
(861, 34, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-21 08:06:33'),
(862, 34, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-21 08:06:33'),
(863, 41, 'processing', 'Status changed from pending to processing', 9, '2025-09-21 08:11:58'),
(864, 41, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-21 08:11:58'),
(865, 46, 'delivered', 'Status changed from shipped to delivered', 7, '2025-09-21 08:12:09'),
(866, 46, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-21 08:12:09'),
(867, 44, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-21 08:13:07'),
(868, 44, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-21 08:13:07'),
(869, 43, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-21 08:13:15'),
(870, 43, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-21 08:13:15'),
(871, 45, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-21 08:13:32'),
(872, 45, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-21 08:13:32'),
(873, 44, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-21 08:14:58'),
(874, 44, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-21 08:14:58'),
(875, 43, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-23 15:34:49'),
(876, 43, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-23 15:34:49'),
(877, 41, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-23 15:35:33'),
(878, 41, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-23 15:35:33'),
(885, 54, 'processing', 'Status changed from pending to processing', 9, '2025-09-25 00:13:25'),
(886, 54, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-25 00:13:25'),
(888, 54, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-25 00:13:45'),
(889, 54, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-25 00:13:45'),
(891, 54, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-25 00:14:11'),
(892, 54, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-25 00:14:11'),
(901, 56, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-09-25 00:28:56'),
(902, 56, 'cancelled', 'Order cancelled by customer', 9, '2025-09-25 00:28:56'),
(903, 55, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-09-25 00:29:33'),
(904, 55, 'cancelled', 'Order cancelled by customer', 9, '2025-09-25 00:29:33'),
(905, 53, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-09-25 00:29:50'),
(906, 53, 'cancelled', 'Order cancelled by customer', 9, '2025-09-25 00:29:50'),
(907, 57, 'processing', 'Status changed from pending to processing', 9, '2025-09-25 00:47:24'),
(908, 57, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-25 00:47:24'),
(909, 57, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-25 00:48:10'),
(910, 57, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-25 00:48:10'),
(911, 57, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-25 00:48:42'),
(912, 57, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-25 00:48:42'),
(913, 42, 'processing', 'Status changed from pending to processing', 9, '2025-09-25 10:17:54'),
(914, 42, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-25 10:17:54'),
(915, 42, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-25 10:18:56'),
(916, 42, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-25 10:18:56'),
(917, 42, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-25 10:19:10'),
(918, 42, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-25 10:19:10'),
(919, 58, 'processing', 'Status changed from pending to processing', 9, '2025-09-25 10:28:09'),
(920, 58, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-25 10:28:09'),
(921, 58, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-25 10:28:14'),
(922, 58, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-25 10:28:14'),
(923, 58, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-25 10:28:21'),
(924, 58, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-25 10:28:21'),
(925, 59, 'processing', 'Status changed from pending to processing', 9, '2025-09-25 10:52:38'),
(926, 59, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-25 10:52:38'),
(927, 59, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-25 10:52:44'),
(928, 59, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-25 10:52:44'),
(929, 59, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-25 10:52:49'),
(930, 59, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-25 10:52:49'),
(931, 64, 'processing', 'Status changed from pending to processing', 9, '2025-09-25 15:27:58'),
(932, 64, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-25 15:27:58'),
(933, 63, 'processing', 'Status changed from pending to processing', 9, '2025-09-25 15:28:09'),
(934, 63, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-25 15:28:09'),
(935, 63, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-25 15:28:19'),
(936, 63, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-25 15:28:19'),
(937, 64, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-25 15:28:30'),
(938, 64, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-25 15:28:30'),
(939, 64, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-25 15:28:50'),
(940, 64, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-25 15:28:50'),
(941, 63, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-25 15:29:09'),
(942, 63, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-25 15:29:09'),
(943, 62, 'processing', 'Status changed from pending to processing', 9, '2025-09-25 15:34:00'),
(944, 62, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-25 15:34:00'),
(945, 62, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-25 15:34:25'),
(946, 62, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-25 15:34:25'),
(947, 62, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-25 15:34:38'),
(948, 62, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-25 15:34:38'),
(949, 61, 'processing', 'Status changed from pending to processing', 9, '2025-09-25 15:35:12'),
(950, 61, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-25 15:35:12'),
(951, 71, 'processing', 'Status changed from pending to processing', 9, '2025-09-26 14:40:08'),
(952, 71, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-26 14:40:08'),
(953, 70, 'processing', 'Status changed from pending to processing', 9, '2025-09-26 14:40:14'),
(954, 70, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-26 14:40:14'),
(955, 69, 'processing', 'Status changed from pending to processing', 9, '2025-09-26 14:40:22'),
(956, 69, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-26 14:40:22'),
(957, 73, 'processing', 'Status changed from pending to processing', 9, '2025-09-26 14:50:27'),
(958, 73, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-26 14:50:27'),
(959, 73, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-26 14:50:36'),
(960, 73, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-26 14:50:36'),
(961, 75, 'processing', 'Status changed from pending to processing', 9, '2025-09-26 15:07:00'),
(962, 75, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-26 15:07:00'),
(963, 75, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-26 15:07:06'),
(964, 75, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-26 15:07:06'),
(965, 75, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-26 15:07:33'),
(966, 75, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-26 15:07:33'),
(967, 74, 'processing', 'Status changed from pending to processing', 9, '2025-09-26 15:07:52'),
(968, 74, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-26 15:07:52'),
(969, 74, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-26 15:07:58'),
(970, 74, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-26 15:07:58'),
(971, 74, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-26 15:08:04'),
(972, 74, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-26 15:08:04'),
(973, 73, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-26 15:09:51'),
(974, 73, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-26 15:09:51'),
(975, 76, 'processing', 'Status changed from pending to processing', 9, '2025-09-26 15:58:04'),
(976, 76, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-26 15:58:04'),
(977, 76, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-26 15:58:20'),
(978, 76, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-26 15:58:20'),
(979, 76, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-26 15:58:29'),
(980, 76, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-26 15:58:29'),
(981, 82, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-09-28 07:54:09'),
(982, 82, 'cancelled', 'Order cancelled by customer', 9, '2025-09-28 07:54:09'),
(983, 88, 'processing', 'Status changed from pending to processing', 9, '2025-09-28 08:02:59'),
(984, 88, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-28 08:02:59'),
(985, 87, 'processing', 'Status changed from pending to processing', 9, '2025-09-28 08:03:21'),
(986, 87, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-28 08:03:21'),
(987, 88, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-28 08:03:40'),
(988, 88, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-28 08:03:40'),
(989, 88, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-28 08:03:54'),
(990, 88, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-28 08:03:54'),
(991, 89, 'processing', 'Status changed from pending to processing', 9, '2025-09-28 08:07:12'),
(992, 89, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-28 08:07:12'),
(993, 89, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-28 08:27:37'),
(994, 89, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-28 08:27:37'),
(995, 89, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-28 08:27:43'),
(996, 89, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-28 08:27:43'),
(997, 90, 'processing', 'Status changed from pending to processing', 9, '2025-09-28 14:51:03'),
(998, 90, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-28 14:51:03'),
(999, 90, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-28 14:51:13'),
(1000, 90, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-28 14:51:13'),
(1001, 90, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-28 14:51:22'),
(1002, 90, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-28 14:51:22'),
(1003, 95, 'processing', 'Status changed from pending to processing', 9, '2025-09-29 08:46:21'),
(1004, 95, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-29 08:46:22'),
(1005, 95, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-29 08:48:30'),
(1006, 95, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-29 08:48:30'),
(1007, 94, 'processing', 'Status changed from pending to processing', 9, '2025-09-29 15:34:49'),
(1008, 94, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-29 15:34:49'),
(1009, 94, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-29 15:35:15'),
(1010, 94, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-29 15:35:15'),
(1011, 93, 'processing', 'Status changed from pending to processing', 9, '2025-09-29 15:35:31'),
(1012, 93, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-29 15:35:31'),
(1013, 93, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-29 15:36:31'),
(1014, 93, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-29 15:36:31'),
(1015, 92, 'processing', 'Status changed from pending to processing', 9, '2025-09-29 15:38:35'),
(1016, 92, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-29 15:38:35'),
(1017, 92, 'shipped', 'Status changed from processing to shipped', 9, '2025-09-29 15:38:43'),
(1018, 92, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-09-29 15:38:43'),
(1019, 91, 'processing', 'Status changed from pending to processing', 9, '2025-09-29 15:43:04'),
(1020, 91, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-29 15:43:04'),
(1021, 86, 'processing', 'Status changed from pending to processing', 9, '2025-09-29 15:43:15'),
(1022, 86, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-09-29 15:43:15'),
(1023, 91, 'cancelled', 'Status changed from processing to cancelled', 9, '2025-09-29 15:47:34'),
(1024, 91, 'cancelled', 'Status updated from processing to cancelled by seller.', 7, '2025-09-29 15:47:34'),
(1025, 85, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-09-29 15:57:40'),
(1026, 85, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-09-29 15:57:40'),
(1027, 84, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-09-29 15:59:37'),
(1028, 84, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-09-29 15:59:37'),
(1029, 83, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-09-29 16:00:05'),
(1030, 83, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-09-29 16:00:05'),
(1031, 95, 'delivered', 'Status changed from shipped to delivered', 9, '2025-09-30 11:46:38'),
(1032, 95, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-09-30 11:46:38'),
(1033, 98, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-10-01 14:20:09'),
(1034, 98, 'cancelled', 'Cancelled by customer. Reason: trip lng nako', 9, '2025-10-01 14:20:09'),
(1035, 97, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-10-01 14:24:45'),
(1036, 97, 'cancelled', 'Cancelled by customer. Reason: wla lang', 9, '2025-10-01 14:24:45'),
(1037, 96, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-10-01 14:48:29'),
(1038, 96, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-10-01 14:48:29'),
(1039, 94, 'delivered', 'Status changed from shipped to delivered', 9, '2025-10-02 11:26:42'),
(1040, 94, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-10-02 11:26:42'),
(1041, 93, 'delivered', 'Status changed from shipped to delivered', 9, '2025-10-02 11:26:47'),
(1042, 93, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-10-02 11:26:47'),
(1043, 92, 'delivered', 'Status changed from shipped to delivered', 9, '2025-10-02 11:26:50'),
(1044, 92, 'delivered', 'Status updated from shipped to delivered by seller.', 7, '2025-10-02 11:26:50'),
(1045, 87, 'cancelled', 'Status changed from processing to cancelled', 9, '2025-10-02 11:26:54'),
(1046, 87, 'cancelled', 'Status updated from processing to cancelled by seller.', 7, '2025-10-02 11:26:54'),
(1047, 86, 'cancelled', 'Status changed from processing to cancelled', 9, '2025-10-02 11:26:57'),
(1048, 86, 'cancelled', 'Status updated from processing to cancelled by seller.', 7, '2025-10-02 11:26:57'),
(1049, 81, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-10-02 11:27:00'),
(1050, 81, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-10-02 11:27:00'),
(1051, 79, 'cancelled', 'Status changed from pending to cancelled', 7, '2025-10-02 11:27:04'),
(1052, 79, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-10-02 11:27:04'),
(1053, 78, 'cancelled', 'Status changed from pending to cancelled', 7, '2025-10-02 11:27:07'),
(1054, 78, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-10-02 11:27:07'),
(1055, 77, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-10-02 11:27:10'),
(1056, 77, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-10-02 11:27:10'),
(1057, 72, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-10-02 11:27:14'),
(1058, 72, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-10-02 11:27:14'),
(1059, 71, 'cancelled', 'Status changed from processing to cancelled', 9, '2025-10-02 11:27:18'),
(1060, 71, 'cancelled', 'Status updated from processing to cancelled by seller.', 7, '2025-10-02 11:27:18'),
(1061, 70, 'cancelled', 'Status changed from processing to cancelled', 9, '2025-10-02 11:27:20'),
(1062, 70, 'cancelled', 'Status updated from processing to cancelled by seller.', 7, '2025-10-02 11:27:20'),
(1063, 68, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-10-02 11:27:23'),
(1064, 68, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-10-02 11:27:23'),
(1065, 69, 'cancelled', 'Status changed from processing to cancelled', 9, '2025-10-02 11:27:26'),
(1066, 69, 'cancelled', 'Status updated from processing to cancelled by seller.', 7, '2025-10-02 11:27:26'),
(1067, 67, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-10-02 11:27:30'),
(1068, 67, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-10-02 11:27:30'),
(1069, 66, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-10-02 11:27:33'),
(1070, 66, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-10-02 11:27:33'),
(1071, 65, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-10-02 11:27:37'),
(1072, 65, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-10-02 11:27:37'),
(1073, 60, 'cancelled', 'Status changed from pending to cancelled', 9, '2025-10-02 11:27:41'),
(1074, 60, 'cancelled', 'Status updated from pending to cancelled by seller.', 7, '2025-10-02 11:27:41'),
(1075, 99, 'processing', 'Status changed from pending to processing', 9, '2025-10-03 14:18:38'),
(1076, 99, 'processing', 'Status updated from pending to processing by seller.', 7, '2025-10-03 14:18:38'),
(1077, 99, 'shipped', 'Status changed from processing to shipped', 9, '2025-10-03 14:20:39'),
(1078, 99, 'shipped', 'Status updated from processing to shipped by seller.', 7, '2025-10-03 14:20:39'),
(1079, 100, 'processing', 'Status changed from pending to processing', 9, '2025-10-04 04:43:32'),
(1080, 100, 'processing', 'Status updated from pending to processing by seller.', 11, '2025-10-04 04:43:32'),
(1081, 101, 'processing', 'Status changed from pending to processing', 9, '2025-10-04 04:43:59'),
(1082, 101, 'processing', 'Status updated from pending to processing by seller.', 11, '2025-10-04 04:43:59'),
(1083, 101, 'shipped', 'Status changed from processing to shipped', 9, '2025-10-04 04:44:02'),
(1084, 101, 'shipped', 'Status updated from processing to shipped by seller.', 11, '2025-10-04 04:44:02'),
(1085, 101, 'delivered', 'Status changed from shipped to delivered', 9, '2025-10-04 04:44:44'),
(1086, 101, 'delivered', 'Status updated from shipped to delivered by seller.', 11, '2025-10-04 04:44:44'),
(1087, 100, 'cancelled', 'Status changed from processing to cancelled', 9, '2025-10-04 04:45:32'),
(1088, 100, 'cancelled', 'Status updated from processing to cancelled by seller.', 11, '2025-10-04 04:45:32'),
(1089, 131, 'cancelled', 'Status changed from pending to cancelled', 12, '2025-10-07 08:20:45');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_reference` varchar(255) DEFAULT NULL,
  `paymongo_session_id` varchar(255) DEFAULT NULL,
  `paymongo_payment_id` varchar(255) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_transactions`
--

INSERT INTO `payment_transactions` (`id`, `user_id`, `total_amount`, `payment_method`, `payment_status`, `payment_reference`, `paymongo_session_id`, `paymongo_payment_id`, `shipping_address`, `customer_name`, `customer_email`, `customer_phone`, `created_at`, `updated_at`, `completed_at`) VALUES
(7, 12, 54241.50, 'gcash', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 14:57:31', '2025-10-05 14:57:31', NULL),
(8, 12, 15527.80, 'paymongo', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 14:58:13', '2025-10-05 14:58:13', NULL),
(9, 12, 16233.10, 'paymongo', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 15:01:37', '2025-10-05 15:01:37', NULL),
(10, 12, 12930.52, 'paymongo', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 15:04:14', '2025-10-05 15:04:14', NULL),
(11, 12, 3034.35, 'gcash', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 15:38:16', '2025-10-05 15:38:16', NULL),
(12, 12, 3034.35, 'paymongo', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 15:41:34', '2025-10-05 15:41:34', NULL),
(13, 12, 3034.35, 'paymongo', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 15:41:42', '2025-10-05 15:41:42', NULL),
(14, 12, 3034.35, 'gcash', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 15:41:54', '2025-10-05 15:41:54', NULL),
(15, 12, 3034.35, 'gcash', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 15:42:03', '2025-10-05 15:42:03', NULL),
(16, 12, 3034.35, 'gcash', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 15:50:47', '2025-10-05 15:50:47', NULL),
(17, 12, 3034.35, 'gcash', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 15:50:50', '2025-10-05 15:50:50', NULL),
(18, 12, 3034.35, 'gcash', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 15:50:56', '2025-10-05 15:50:56', NULL),
(19, 12, 3034.35, 'gcash', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 17:13:39', '2025-10-05 17:13:39', NULL),
(20, 12, 3034.35, 'paymaya', 'pending', NULL, NULL, NULL, '123 Whatever Dr', 'Drea Mendez', 'heyllama11@gmail.com', '09770490613', '2025-10-05 17:14:27', '2025-10-05 17:14:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sku` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category_id`, `seller_id`, `stock_quantity`, `image_url`, `rating`, `review_count`, `status`, `admin_notes`, `created_at`, `sku`) VALUES
(1, 'piste', 'para sa UKUK', 1233.00, 1, 1, 4, 'assets/uploads/1756610072_133841685866127902.jpg', 0.00, 0, '', NULL, '2025-08-31 03:14:32', NULL),
(2, 'pistee', '123', 1233.00, 1, 1, 5, 'assets/uploads/1756633133_133841685866127902.jpg', 0.00, 0, 'inactive', NULL, '2025-08-31 09:38:53', NULL),
(3, 'pisteee', '123123', 1233.00, 1, 1, 976, '', 5.00, 1, '', NULL, '2025-08-31 14:44:36', NULL),
(5, 'pistee', '111', 500.00, 2, 1, 12, '', 0.00, 0, 'inactive', NULL, '2025-09-03 11:57:35', NULL),
(6, 'category', '121', 111.00, 2, 1, 1210, '', 0.00, 0, '', NULL, '2025-09-03 12:02:36', NULL),
(8, 'category', '121', 111.00, 2, 1, 1206, '', 0.00, 0, '', NULL, '2025-09-03 12:15:17', NULL),
(9, 'category', '121', 111.00, 2, 1, 1207, '', 0.00, 0, '', NULL, '2025-09-03 12:15:17', NULL),
(11, 'pistee', 'pistee pistee pistee pistee', 10000.00, 3, 7, 10, '', 0.00, 0, 'inactive', NULL, '2025-09-11 12:06:03', NULL),
(13, 'producttext1', 'producttext1producttext1', 100.00, 3, 7, 118, '', 0.00, 0, 'inactive', NULL, '2025-09-11 13:35:49', NULL),
(16, 'productTest', 'productTest productTest productTest', 100.00, 3, 7, 7, '', 0.00, 0, 'inactive', NULL, '2025-09-11 13:43:26', NULL),
(17, 'prodcWithPic', 'asdasd', 300.00, 111, 7, 100, 'assets/uploads/1757681691_RobloxScreenShot20250809_221658811.png', 2.00, 1, 'inactive', NULL, '2025-09-12 12:54:51', NULL),
(18, 'pictureProduct', 'pictureProductpictureProductpictureProductpictureProduct', 15.00, 3, 7, 78, 'assets/uploads/1757734061_RobloxScreenShot20250809_211348632.png', 5.00, 1, 'inactive', NULL, '2025-09-13 03:27:41', NULL),
(19, 'noImageTest', 'noImageTestnoImageTestnoImageTestnoImageTest', 11.00, 3, 7, 104, 'assets/uploads/tempo_image.jpg', 0.00, 0, 'inactive', NULL, '2025-09-13 03:41:47', NULL),
(20, 'Imidart', 'ImidartImidartImidartImidart', 100.00, 3, 7, 69, 'assets/uploads/1759552181_Imidart.jpg', 0.00, 0, 'active', NULL, '2025-09-13 03:42:51', NULL),
(21, 'withImageTEST', 'withImageTESTwithImageTESTwithImageTESTwithImageTESTwithImageTEST', 2000.00, 3, 7, 1196, 'assets/uploads/1757738766_RobloxScreenShot20250809_221658811.png', 0.00, 0, 'inactive', NULL, '2025-09-13 04:46:06', NULL),
(22, 'testRevenueprodct', 'testRevenueprodcttestRevenueprodcttestRevenueprodcttestRevenueprodct', 10000.00, 3, 7, 24, 'assets/uploads/1758717038_unnamed.png', 0.00, 0, 'inactive', NULL, '2025-09-24 12:30:38', NULL),
(23, 'SellerDashboard', 'SellerDashboardSellerDashboardSellerDashboard', 500.00, 3, 7, 1, 'assets/uploads/1759041125_logho (2).png', 0.00, 0, 'inactive', NULL, '2025-09-28 06:32:05', NULL),
(24, 'Decis_protech_', 'Excellent insecticide for control of aphids, caterpillars and many other pests. Decis Protech is a rapid and cost effective pyrethroid product with a low application rate to suit regular treatments on a range of biting, chewing and sucking insect pests in numerous agricultural and horticultural crops.\r\n\r\nKey Points:\r\nDecis Protech is rainfast 1 hour after application\r\nPests treated: Aphids, Pear Sucker, Scale insects, Caterpillar, Capsids, Whitefly, Mealy Bugs, Cutworm, Codling Moth, Tortrix Moth, Pollen Beetle, Pea &amp; Bean Weevil, Brassica Flea Beetle\r\nUse on: Amenity vegetation (outdoor), Ornamental plant production, Apples, Beans (Broad, Field, Tic), Broccoli, Brussels Sprouts, Cabbage, Cauliflower, Glasshouse crops (tomatoes, peppers, cucumbers, pot plants), Hops, Kale, Lettuce (outdoor), Mustard, Nursery Stock, Oats, Oilseed Rape (winter and spring), Pears, Peas, Plums, Raspberries, Sugar Beet, Swedes, Turnips, Winter &amp; spring wheat, Winter &amp; spring barley\r\nIt can be applied in frosty weather provided foliage is not covered with ice\r\nProduct Information:\r\nActive Ingredient: 15 g/L (1.50% w/w) deltamethrin\r\n\r\nDose Rate: (ornamentals) 120 ml in 100 L water or 12 ml per 10 l water\r\n\r\nMAPP:16160\r\n\r\nFor Amenity vegetation which includes outdoor ornamentals, trees &amp; shrubs - Decis Protech will control whitefly, scale insects, caterpillars, capsids, thrips, aphids and mealy bugs. Apply the product when the pest is first seen. For whitefly, thoroughly wet plants, especially the leaf under-surface and repeat as required.\r\n\r\nDO NOT EXCEED MAXIMUM CONCENTRATION of 120 ml per 100 L water\r\n\r\nStrains of some aphid species are resistant to many aphicides. Where aphids, glasshouse whitefly, tobacco whitefly, pear suckers resistant to products containing pyrethroid insecticides occur Decis Protech is unlikely to give satisfactory control. Repeat treatments are likely to result in lower levels of control. Where repeat treatments are necessary use different active ingredients.\r\n\r\nResistance Management Strategy:\r\n\r\nTotal reliance on a single pesticide will speed up the development of resistance; pesticides of different chemical types or alternative control measures should be included in a planned programme. It is good practice to alternate insecticides with different modes of action in a recognised anti-resistance strategy. Decis Protech should always be used in alternation with other insecticides of a different mode of action where available.\r\n\r\nApplication Information:\r\n\r\nDecis Protech should always be applied at the recommended rate of use and in sufficient water volume to achieve the required spray penetration into the crop and uniform coverage necessary for optimal pest control.\r\n\r\nShake well before use. Add the required quantity immediately at the beginning of filling the spray tank with water. Keep the spray agitation in action and add the required quantity of water. Continue agitation until spraying is completed. After spraying, thoroughly wash out the spray tank with a proprietary tank cleaner such as All Clear Extra or Tank &amp; Equipment Cleaner.\r\nSafety:\r\nSuggested safety clothing - coveralls, faceshield &amp; gloves when handling the concentrate (see Safety Clothing) Very high temperatures, &gt;35°C (&gt;95°F) may reduce efficacy or persistence.', 299.00, 81, 7, 30, 'assets/uploads/1759488574_Decis_protech_1l.jpg', 0.00, 0, 'inactive', NULL, '2025-09-29 08:31:10', NULL),
(26, 'INSECTICIDES-Mahishmati', 'INSECTICIDES-MahishmatiINSECTICIDES-Mahishmati', 190.00, 81, 7, 0, 'assets/uploads/1759501037_INSECTICIDES-Mahishmati.jpg', 0.00, 0, 'active', NULL, '2025-10-03 14:17:17', NULL),
(27, 'Indoor Ant Kit', 'The Indoor Ant Kit includes all the products you need for the professional treatment of most ants commonly found indoors, including little black ants, carpenter ants, odorous house ants, and more. This do-it-yourself ant treatment kit uses the convenience of bait plate stations and the attractiveness of two unique baits to fill the dietary needs of most household ants.', 3299.13, 81, 11, 21, 'assets/uploads/tempo_image.jpg', 0.00, 0, 'inactive', NULL, '2025-10-04 04:12:17', NULL),
(30, 'Mice Traps and Bait Combo Kits', 'Mice Combo Traps /Bait Kits combine four types of professional traps, mouse lure attractant, and one type of disposable bait station (bait included) in one kit for a greater selection and savings.', 4674.80, 3, 11, 97, 'assets/uploads/tempo_image.jpg', 0.00, 0, 'active', NULL, '2025-10-04 04:26:13', NULL),
(31, 'Victor Mouse Trap M040', 'The classic Victor mouse trap with the metal pedal. All Victor mouse traps and rat traps are made from Forest Stewardship Council (FSC) sourced wood from environmentally managed forests. Trap measures 4&quot;L x 1 3/4&quot;W.', 694.79, 3, 11, 196, 'assets/uploads/1759552123_mouse trap.jpg', 0.00, 0, 'active', NULL, '2025-10-04 04:28:43', NULL),
(32, 'Provoke Professional Gel for Mouse Traps', 'Provoke Professional Gel for Mouse Traps is the first and only attractant designed specifically for mice, according to their food preferences. Simply place a small amount of Provoke on the your snap trap or mouse multi-catch trap or glue board. Your trap will now become twice as enticing! In a recent field study where several cases of mouse traps were placed half with Provoke and half with peanut butter-- 29 mice were caught within the first hour by traps using Provoke, while only 3 mice were caught by the peanut butter traps.', 611.42, 3, 11, 186, 'assets/uploads/1759552185_provoke_2_oz-3561510086.png', 0.00, 0, 'active', NULL, '2025-10-04 04:29:45', NULL),
(33, 'Shake-Away Mouse Repellent', 'Shake Away Mouse Repellent is an effective organic repellent that helps get rid of mice. It is the first indoor mouse repellent created by Shake Away. It uses a non-toxic formula that will help eliminate those pests in a natural way, making this safe to use around family and pets. It features an aromatic mint scent made from natural ingredients which turns mice away from the treated area. This pack contains 4 easy and ready to use small packs that will last up to 60 days. The outside bag is resealable so you can preserve the unused packs.', 915.39, 3, 11, 147, 'assets/uploads/1759552240_9914.jpg.thumb_450x450.jpg', 0.00, 0, 'active', NULL, '2025-10-04 04:30:40', NULL),
(34, 'Advion Cockroach Gel Bait', 'Advion Cockroach Gel Bait is a professional-strength roach killer trusted by pest control experts and homeowners. Formulated with the powerful, non-repellent active ingredient Indoxacarb, Advion roach gel targets a wide range of cockroach species, including German, American, and Oriental roaches while delivering fast, comprehensive control through a proven domino effect.', 1562.13, 95, 11, 287, 'assets/uploads/1759552310_304-Advion-Roach-Bait-Gel-Syngenta.jpg.thumb_450x450.jpg', 0.00, 0, 'active', NULL, '2025-10-04 04:31:30', NULL),
(35, 'Contrac Rodent Pellet Place Pacs Rodenticide', 'Contrac Rodenticide Place Pacs are a single-feeding anticoagulant bait in pellet form that has superior acceptance by Mice and Rats. To use simply place Contrac Rodenticide Place Pacs down where rodents travel. Rodent will gnaw through the pack and ingest the poison.', 7525.19, 3, 11, 0, 'assets/uploads/1759552372_77-2.jpg.thumb_450x450.jpg', 0.00, 0, 'active', NULL, '2025-10-04 04:32:41', NULL),
(37, 'GreenWay Fruit Fly Trap', 'GreenWay Fruit Fly Trap will have your fruits and vegetables protected with this bottle fruit fly trap. It is ready to use, non-toxic, pet and child safe, and long-lasting. The design of this bottle trap is to capture and remove fruit flies. Place the GreenWay Fruit Fly Traps near fruit, food preparation areas, on countertops, and other areas where you have seen fruit flies. The GreenWay Fruit Fly Trap will last for months.', 760.80, 81, 11, 331, 'assets/uploads/1759552466_13134-Greenway-Fruit-Fly-Trap-v2.jpg.thumb_450x450.jpg', 0.00, 0, 'active', NULL, '2025-10-04 04:34:26', NULL),
(39, 'GreenWay Fruit Fly Trap', 'GreenWay Fruit Fly Trap will have your fruits and vegetables protected with this bottle fruit fly trap. It is ready to use, non-toxic, pet and child safe, and long-lasting. The design of this bottle trap is to capture and remove fruit flies. Place the GreenWay Fruit Fly Traps near fruit, food preparation areas, on countertops, and other areas where you have seen fruit flies. The GreenWay Fruit Fly Trap will last for months.', 760.80, 81, 11, 342, 'assets/uploads/1759552549_391-Tempo-1pc-Dust-envu.jpg.thumb_450x450.jpg', 0.00, 0, 'active', NULL, '2025-10-04 04:35:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_features`
--

CREATE TABLE `product_features` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `feature_name` varchar(255) DEFAULT NULL,
  `feature_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `rating`, `review_text`, `created_at`) VALUES
(1, 3, 3, 5, 'gioofd', '2025-09-03 12:45:54'),
(2, 17, 8, 2, 'ok', '2025-09-13 06:52:07'),
(3, 18, 9, 5, 'gooodss', '2025-09-21 07:29:33');

-- --------------------------------------------------------

--
-- Table structure for table `product_specifications`
--

CREATE TABLE `product_specifications` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `spec_name` varchar(255) NOT NULL,
  `spec_value` text NOT NULL,
  `spec_unit` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_specifications`
--

INSERT INTO `product_specifications` (`id`, `product_id`, `spec_name`, `spec_value`, `spec_unit`, `display_order`, `created_at`) VALUES
(1, 1, 'Weight', '2.5', 'kg', 1, '2025-09-12 14:00:15'),
(2, 1, 'Dimensions', '30 x 20 x 10', 'cm', 2, '2025-09-12 14:00:15'),
(3, 1, 'Color', 'Black', '', 3, '2025-09-12 14:00:15'),
(4, 1, 'Material', 'Plastic', '', 4, '2025-09-12 14:00:15'),
(5, 2, 'Weight', '1.2', 'kg', 1, '2025-09-12 14:00:15'),
(6, 2, 'Dimensions', '25 x 15 x 8', 'cm', 2, '2025-09-12 14:00:15'),
(7, 2, 'Color', 'White', '', 3, '2025-09-12 14:00:15'),
(8, 2, 'Material', 'Metal', '', 4, '2025-09-12 14:00:15'),
(9, 3, 'Weight', '0.8', 'kg', 1, '2025-09-12 14:00:15'),
(10, 3, 'Dimensions', '20 x 15 x 5', 'cm', 2, '2025-09-12 14:00:15'),
(11, 3, 'Color', 'Blue', '', 3, '2025-09-12 14:00:15'),
(12, 3, 'Battery Life', '10 hours', '', 4, '2025-09-12 14:00:15');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `comment`, `is_verified`, `is_approved`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 5, 'Excellent product!', 'Really happy with this purchase. Great quality and fast shipping.', 0, 1, '2025-09-12 14:00:15', '2025-09-12 14:00:15'),
(2, 1, 3, 4, 'Good value', 'Nice product for the price. Would recommend.', 0, 1, '2025-09-12 14:00:15', '2025-09-12 14:00:15'),
(3, 2, 4, 3, 'Average', 'It\'s okay, nothing special but does the job.', 0, 1, '2025-09-12 14:00:15', '2025-09-12 14:00:15'),
(4, 3, 4, 5, 'Perfect!', 'Exactly what I was looking for. Excellent quality.', 0, 1, '2025-09-12 14:00:15', '2025-09-12 14:00:15');

-- --------------------------------------------------------

--
-- Table structure for table `sales_analytics`
--

CREATE TABLE `sales_analytics` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `total_orders` int(11) DEFAULT 0,
  `total_products` int(11) DEFAULT 0,
  `monthly_revenue` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_trend`
--

CREATE TABLE `sales_trend` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `sale_date` date NOT NULL,
  `total_sales` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'order_grace_period', '1', 'Grace period in minutes for customer order cancellation priority', '2025-09-26 16:02:03', '2025-10-03 14:16:34'),
(2, 'site_maintenance_mode', '0', 'Enable/disable site maintenance mode (0 = disabled, 1 = enabled)', '2025-09-26 16:02:03', '2025-09-26 16:02:03'),
(3, 'max_products_per_seller', '100', 'Maximum number of products a seller can list', '2025-09-26 16:02:03', '2025-09-26 16:02:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `user_type` enum('customer','admin','seller') DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT 1,
  `seller_status` enum('pending','approved','rejected','suspended','banned') DEFAULT 'pending',
  `document_path` varchar(255) DEFAULT NULL,
  `document_verified` tinyint(1) DEFAULT 0,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `agreed_to_terms` tinyint(1) DEFAULT 0,
  `agreed_to_privacy` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `remember_token`, `user_type`, `is_active`, `seller_status`, `document_path`, `document_verified`, `first_name`, `last_name`, `address`, `phone`, `created_at`, `email_verified`, `last_login`, `agreed_to_terms`, `agreed_to_privacy`) VALUES
(1, 'step', 'jhon_gujol@sss.com', '$2y$10$xcogggs5Gu2GC4Zhrko1Iu11BltFKHyjH11o5jUkqM1gjPOW1u1tO', NULL, 'seller', 1, 'approved', NULL, 0, 'seller', 'sss', NULL, NULL, '2025-08-31 00:50:45', 0, NULL, 0, 0),
(2, 'seller1', 'seller1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'seller', 1, 'pending', NULL, 0, 'John', 'Seller', NULL, NULL, '2025-09-12 14:00:15', 0, NULL, 0, 0),
(3, 'step1', 'jhon_gujolcustomer@sss.com', '$2y$10$F8NNXEXToobkAkzq2ks.suH.3SPDI5xCJj6okvmsS2qosgNyEOc72', NULL, 'customer', 0, 'pending', NULL, 0, 'customer', 'customer', 'asdasdasdasd', '0911212121', '2025-08-31 01:30:07', 0, NULL, 0, 0),
(4, 'buyer1', 'buyer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, '', 1, 'pending', NULL, 0, 'Mike', 'Customer', NULL, NULL, '2025-09-12 14:00:15', 0, NULL, 0, 0),
(5, 'admin', 'admin@admin.com', '$2y$10$uoL9mzlJAwIGKJ44P6QrSOJh5ci569kuDu23C2TQkCuGr1tNMOZ4O', NULL, 'admin', 1, 'pending', NULL, 0, 'mendez', 'pangit', NULL, NULL, '2025-08-31 02:58:08', 0, NULL, 0, 0),
(6, 'admin2', 'adminStep@admin.com', '$2y$10$7AOyNuCFuGLqxy8DWEtbjusECwZJcxAlDqU4E909DNIWcqhlt0fOm', NULL, 'admin', 1, 'pending', NULL, 0, 'Step', 'Admin', NULL, NULL, '2025-09-05 14:24:42', 0, NULL, 0, 0),
(7, 'sellerStep123', 'jhongujol1299@gmail.com', '$2y$10$vFZPjnbercgy9x0wFo/LCOalIN2mqPJHC.ZIxuWvguZKYTcXWE2fW', NULL, 'seller', 1, 'approved', NULL, 0, 'step', 'step', 'asdasd', 'asdasd', '2025-09-10 13:33:46', 1, '2025-10-04 06:08:04', 0, 0),
(8, 'CustomerMendez123', 'andrealouise_mendez@sjp2cd.edu.ph', '$2y$10$Ho.8I402JOYBaTjPrZd/s.GP8f/t1ESZ7AZf1akza6DzRBiaebiAS', NULL, 'customer', 1, 'pending', NULL, 0, 'CustomerMendezz', 'anak Miropee', 'asdasdasdasdasd', '090909090', '2025-09-10 15:09:29', 1, '2025-09-28 10:32:17', 0, 0),
(9, 'customerStep123', 'jhon_gujol@sjp2cd.edu.ph', '$2y$10$XzoVPP7Y9dMnMYLkoRK19OPkvOeVb3MII3xowaBr.8Q/KhQIejiwW', NULL, 'customer', 1, 'pending', NULL, 0, 'Step', 'Stepin', 'AddressAddressAddressAddressAddress', '123123', '2025-09-13 17:23:27', 1, '2025-10-04 06:30:31', 0, 0),
(10, 'SellerIsagani123', 'isaganigujol@gmail.com', '$2y$10$VST0.nj97zy8lxnn0BlY..p9WqLrTASQbvDzbwB0mGGdZuVKvKFUa', NULL, 'seller', 1, 'approved', NULL, 0, 'SellerIsagani', 'SellerIsagani', NULL, NULL, '2025-09-16 18:28:34', 1, '2025-09-17 02:34:14', 0, 0),
(11, 'plantita', 'andreamndz101@gmail.com', '$2y$10$eybLK7Otyf.iHNdStaDDsOZ0RlVP7pjzp6.dTPM0Ur06Tq7czqX9u', NULL, 'seller', 1, 'approved', NULL, 0, 'Andrea', 'Mendez', NULL, NULL, '2025-10-04 04:08:38', 1, '2025-10-04 09:16:30', 1, 1),
(12, 'affogattoast', 'heyllama11@gmail.com', '$2y$10$7QQADb5nvTxMpDk9ew6aZOioLrt6Tff4uV9knk7cNcOHXpFf0nwMe', NULL, 'customer', 1, 'pending', NULL, 0, 'Drea', 'Mendez', 'Bacanaya Village, Philippines 8000', '09770490613', '2025-10-04 04:54:29', 1, '2025-10-07 09:09:37', 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `idx_categories_parent_id` (`parent_id`);

--
-- Indexes for table `low_stock_alerts`
--
ALTER TABLE `low_stock_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_orders_delivery_date` (`delivery_date`),
  ADD KEY `idx_orders_payment_transaction` (`payment_transaction_id`),
  ADD KEY `idx_orders_seller` (`seller_id`),
  ADD KEY `idx_orders_user_seller` (`user_id`,`seller_id`);

--
-- Indexes for table `order_groups`
--
ALTER TABLE `order_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_transaction` (`payment_transaction_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_order_status_history_order_id` (`order_id`),
  ADD KEY `idx_order_status_history_status` (`status`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_otp` (`otp`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `idx_products_rating` (`rating`),
  ADD KEY `idx_products_review_count` (`review_count`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_products_category_id` (`category_id`),
  ADD KEY `idx_products_seller_id` (`seller_id`),
  ADD KEY `idx_products_status` (`status`);

--
-- Indexes for table `product_features`
--
ALTER TABLE `product_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD KEY `idx_product_images_product_id` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD KEY `idx_product_reviews_product_id` (`product_id`),
  ADD KEY `idx_product_reviews_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD KEY `idx_users_user_type` (`user_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=223;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `order_groups`
--
ALTER TABLE `order_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=214;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1090;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_payment_transaction` FOREIGN KEY (`payment_transaction_id`) REFERENCES `payment_transactions` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
