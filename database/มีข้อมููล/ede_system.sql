-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 06, 2026 at 09:58 AM
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
-- Database: `ede_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL,
  `document_code` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type_id` int(11) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `sender_name` varchar(150) NOT NULL,
  `receiver_name` varchar(150) NOT NULL,
  `current_status` varchar(50) DEFAULT 'เธฅเธเธ—เธฐเน€เธเธตเธขเธเนเธซเธกเน',
  `view_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `workflow_id` varchar(50) DEFAULT 'cat_default'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_status_log`
--

CREATE TABLE `document_status_log` (
  `log_id` bigint(20) NOT NULL,
  `document_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `action_by` int(11) DEFAULT NULL,
  `line_user_id_action` varchar(100) DEFAULT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `location_note` varchar(255) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `device_info` text DEFAULT NULL,
  `actor_name_snapshot` varchar(255) DEFAULT NULL,
  `actor_pic_snapshot` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_status_log`
--

INSERT INTO `document_status_log` (`log_id`, `document_id`, `status`, `action_by`, `line_user_id_action`, `action_time`, `location_note`, `ip_address`, `device_info`, `actor_name_snapshot`, `actor_pic_snapshot`) VALUES
(1, 1, 'ลงทะเบียนเอกสารใหม่', 1, NULL, '2025-12-26 06:56:37', NULL, NULL, NULL, NULL, NULL),
(2, 2, 'ลงทะเบียนเอกสารใหม่', 1, NULL, '2025-12-26 07:37:47', NULL, NULL, NULL, NULL, NULL),
(3, 3, 'ลงทะเบียนเอกสารใหม่', 1, NULL, '2025-12-29 07:28:57', NULL, NULL, NULL, NULL, NULL),
(4, 4, 'ลงทะเบียนเอกสารใหม่', 1, NULL, '2025-12-30 08:02:34', NULL, NULL, NULL, NULL, NULL),
(5, 4, 'เปิดอ่าน', NULL, '', '2026-01-05 06:28:36', 'สแกน QR Code', '::1', 'Scan (View)', 'Guest', ''),
(6, 4, 'รับเอกสาร', NULL, '', '2026-01-05 06:29:23', 'ส่งต่อให้: ส่งต่อ', '2001:44c8:6762:7671:ccf8:26e:61c6:c0ff', 'android', 'Guest', ''),
(7, 4, 'เปิดอ่าน', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-01-06 07:25:45', 'สแกน QR Code', '::1', 'Scan (View)', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(8, 4, 'ได้รับแล้ว', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-01-06 07:26:14', 'อัปเดตสถานะ', '2001:44c8:67a0:982:4125:f01d:f85:131a', 'android', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(9, 5, 'ลงทะเบียนเอกสารใหม่', 1, NULL, '2026-01-08 07:33:45', NULL, NULL, NULL, NULL, NULL),
(10, 5, 'เปิดอ่าน', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-01-08 08:12:49', 'สแกน QR Code', '::1', 'Scan (View)', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(11, 5, 'รับเอกสาร', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-01-08 08:13:04', 'อัปเดตสถานะ', '2001:44c8:4850:60bc:89a9:82fa:30c8:836d', 'android', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(12, 5, 'เปิดอ่าน', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-01-08 08:47:34', 'สแกน QR Code', '::1', 'Scan (View)', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(13, 6, 'ลงทะเบียนเอกสาร', 2, NULL, '2026-01-09 02:26:32', NULL, NULL, NULL, NULL, NULL),
(14, 6, 'เปิดอ่าน', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-01-12 08:11:33', 'สแกน QR Code', '::1', 'Scan (View)', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(15, 6, 'จบ', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-01-12 08:11:43', 'อัปเดตสถานะ', '2001:44c8:4231:219:bcc7:9fe6:c08f:135c', 'android', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(16, 7, 'ลงทะเบียนเอกสารใหม่', 1, NULL, '2026-01-27 04:47:44', NULL, NULL, NULL, NULL, NULL),
(17, 7, 'เปิดอ่าน', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-01-27 04:49:00', 'สแกน QR Code', '::1', 'Scan (View)', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(18, 7, 'รับเอกสาร', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-01-27 04:49:07', 'อัปเดตสถานะ', '2001:44c8:423b:c6f9:106:f831:e71d:3f9a', 'android', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(19, 8, 'ลงทะเบียนเอกสารใหม่', 1, NULL, '2026-01-28 05:58:32', NULL, NULL, NULL, NULL, NULL),
(20, 9, 'ลงทะเบียนเอกสารใหม่', 2, NULL, '2026-01-28 06:08:03', NULL, NULL, NULL, NULL, NULL),
(21, 9, 'เปิดอ่าน', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-01-28 06:10:35', 'สแกน QR Code', '::1', 'Scan (View)', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(22, 9, 'รับเอกสาร', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-01-28 06:10:56', 'อัปเดตสถานะ', '2001:44c8:423b:c6f9:106:f831:e71d:3f9a', 'android', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(23, 10, 'ลงทะเบียนเอกสารใหม่', 1, NULL, '2026-01-30 02:47:05', NULL, NULL, NULL, NULL, NULL),
(24, 10, 'เปิดอ่าน', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-02-24 13:06:19', 'สแกน QR Code', '::1', 'Scan (View)', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(25, 10, 'รับเอกสาร', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-02-24 13:06:24', 'อัปเดตสถานะ', '2001:44c8:4530:deb1:c586:f6d7:afb3:c8bb', 'android', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(26, 11, 'ลงทะเบียนเอกสารใหม่', 1, NULL, '2026-02-25 04:00:57', NULL, NULL, NULL, NULL, NULL),
(27, 11, 'เปิดอ่าน', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-02-25 04:02:52', 'สแกน QR Code', '::1', 'Scan (View)', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(28, 11, 'เปิดอ่าน', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-02-25 04:03:53', 'สแกน QR Code', '::1', 'Scan (View)', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA'),
(29, 11, 'รับเอกสาร', NULL, 'Uf3c4379f5b7fbd713116ddebf55010f2', '2026-02-25 04:03:57', 'อัปเดตสถานะ', '2001:44c8:6793:9e74:c8b:10f:4e7e:7978', 'android', 'BookPhanuwat', 'https://profile.line-scdn.net/0hSAcWKmkWDG5OLh-miYlyET5-DwRtX1V8MElFXHp7Ult3Tk1vZEFFW315UFtxSk5sZk8RWispVA5CPXsIUHjwWkkeUV9yF08wYkFCjA');

-- --------------------------------------------------------

--
-- Table structure for table `document_type`
--

CREATE TABLE `document_type` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_type`
--

INSERT INTO `document_type` (`type_id`, `type_name`) VALUES
(1, 'เอกสารภายใน'),
(2, 'เอกสารภายนอก');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'Administrator'),
(2, 'Staff (Saraban)'),
(3, 'User/Receiver');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `line_user_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `fullname`, `department`, `role_id`, `line_user_id`, `created_at`) VALUES
(1, 'admin', '$2y$10$1p2qN8fRo9qv.reOuSM3KuhE3XKlf/WXubHihWdFk41cch86Rq8Qy', 'System Admin', 'IT', 1, NULL, '2025-12-01 07:29:39'),
(2, 'bookphanuwat', '$2y$10$pGNdSz8wEcmWgAwC2ieYU.RkMKfhNIj4vzoJmd.hBlFo4DZ3T/YLq', 'Phanuwat Phaliphol', 'Test', 3, NULL, '2025-12-02 02:58:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`),
  ADD UNIQUE KEY `document_code` (`document_code`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `document_status_log`
--
ALTER TABLE `document_status_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `document_id` (`document_id`),
  ADD KEY `action_by` (`action_by`);

--
-- Indexes for table `document_type`
--
ALTER TABLE `document_type`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `document_status_log`
--
ALTER TABLE `document_status_log`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `document_type`
--
ALTER TABLE `document_type`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `document_type` (`type_id`),
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `document_status_log`
--
ALTER TABLE `document_status_log`
  ADD CONSTRAINT `document_status_log_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`),
  ADD CONSTRAINT `document_status_log_ibfk_2` FOREIGN KEY (`action_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
