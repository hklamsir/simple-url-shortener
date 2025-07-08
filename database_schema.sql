--
-- 複製並在 phpMyAdmin 中執行此單一查詢即可建立所有資料表。
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

--
-- 資料庫： `short_url_db`
--

-- --------------------------------------------------------

--
-- 資料表結構 `access_logs`
--

CREATE TABLE `access_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `url_id` int(11) NOT NULL,
  `access_time` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) NOT NULL,
  `referrer` text DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_url_id` (`url_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `request_time` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `url_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `report_time` datetime NOT NULL DEFAULT current_timestamp(),
  `reporter_ip` varchar(45) NOT NULL,
  PRIMARY KEY (`report_id`),
  KEY `idx_url_id` (`url_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 插入初始資料 `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('admin_password', '$2y$10$V14.w21.pB17/q4r.oA0o.w2y.eI9.g1.h3.j4.k5.l6.m7.n8.o9');

-- --------------------------------------------------------

--
-- 資料表結構 `short_urls`
--

CREATE TABLE `short_urls` (
  `url_id` int(11) NOT NULL AUTO_INCREMENT,
  `original_url` text NOT NULL,
  `short_code` varchar(10) NOT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `click_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`url_id`),
  UNIQUE KEY `idx_short_code` (`short_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 已傾印資料表的限制(constraint)
--

--
-- 資料表的限制(constraint) `access_logs`
--
ALTER TABLE `access_logs`
  ADD CONSTRAINT `fk_access_url_id` FOREIGN KEY (`url_id`) REFERENCES `short_urls` (`url_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制(constraint) `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_report_url_id` FOREIGN KEY (`url_id`) REFERENCES `short_urls` (`url_id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;
