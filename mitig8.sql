-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql211.infinityfree.com
-- Generation Time: Jul 22, 2026 at 11:10 AM
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
-- Database: `if0_40209545_mitig8`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_insight_cache`
--

CREATE TABLE `ai_insight_cache` (
  `id` int(11) NOT NULL,
  `data_hash` varchar(64) NOT NULL,
  `narrative` text NOT NULL,
  `generated_at` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `ai_insight_cache`
--

INSERT INTO `ai_insight_cache` (`id`, `data_hash`, `narrative`, `generated_at`) VALUES
(1, 'c3162810750be34eebffa4190522bb97', 'We have received 9 flood reports, with 4 currently identified as high risk and needing immediate attention. Two reports are medium risk, and two are low risk. All 9 reports are awaiting dispatch, and none have been resolved yet. The high-risk reports indicate widespread flooding, requiring urgent assessment and response to prevent further damage and ensure safety.', '2026-07-17 18:20:52'),
(2, '68973b3086b8d838a55912dea74d118f', 'We have received 9 flood reports, with 4 currently posing a high risk. Two reports indicate medium risk, and two are low risk. All reports are awaiting review and dispatch, and none have been resolved yet. Immediate attention is needed to the high-risk locations, particularly reports #46, #43, #40, and #34, as they describe widespread flooding.', '2026-07-17 18:23:34'),
(3, '9dd55ec848b39f388552402a05e2a962', 'We have received 11 flood reports, with a significant number still requiring attention. Currently, 5 reports are considered high risk, and all 11 reports are awaiting dispatch.  Several high-risk reports indicate widespread flooding, requiring immediate assessment and resource allocation to ensure the safety and well-being of affected communities.  All reports need to be reviewed and dispatched promptly.', '2026-07-18 03:45:51'),
(4, 'ac95e4ca6ac4f7d29de2982c705451c0', 'We have received 12 flood reports, with 5 currently posing a high risk.  Four reports are considered medium risk, and two are low risk. All reports are awaiting review and dispatch, indicating an urgent need to prioritize assessment and response to the high-risk areas to prevent further damage and ensure safety.', '2026-07-19 07:33:19'),
(5, 'd46f496c773428b900a7761df2d23156', 'We have received a total of 8 flood reports, with 3 currently identified as high risk and requiring immediate attention. Two reports are medium risk, and two are low risk. All reports are awaiting dispatch, and one report remains unreviewed. The high-risk reports indicate significant flooding impacting multiple locations, demanding swift action to assess and provide assistance.', '2026-07-19 09:12:06');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `description` text DEFAULT NULL,
  `risk_level` enum('High','Medium','Low','Unreviewed') NOT NULL DEFAULT 'Unreviewed',
  `status` enum('Pending','Assigned','Resolved') DEFAULT 'Pending',
  `ai_reasoning` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `user_id`, `image`, `latitude`, `longitude`, `description`, `risk_level`, `status`, `ai_reasoning`, `created_at`) VALUES
(34, 1, '1784267722_n2.png', '5.55990000', '-0.26000000', 'It full everywhere', 'High', 'Pending', 'Widespread deep flooding is evident, with water submerging residential areas and surrounding buildings, indicating a severe drainage failure and immediate risk to property and safety.', '2026-07-17 05:55:23'),
(35, 1, '1784268093_gut.png', '5.54000000', '-0.68000000', 'here oo', 'Medium', 'Pending', 'The drain contains significant accumulated sediment and debris, reducing its capacity. While there is no current standing water, the blockage increases the risk of overflow during heavy rainfall.', '2026-07-17 06:01:34'),
(36, 1, '1784268871_image.jpg', '5.69883228', '-0.06840040', 'This ', 'Low', 'Pending', 'The image provides no view of a drainage system, standing water, or debris, showing only a textured wall and a checkered floor.', '2026-07-17 06:14:33'),
(37, 1, '1784275630_image.jpg', '5.72855020', '-0.06680202', 'The place', 'Medium', 'Pending', 'Standing water is visible on the ground, indicating poor drainage and a risk of localized flooding during rain.', '2026-07-17 08:07:11'),
(39, 1, '1784289628_image.jpg', '5.69444230', '-0.03987022', 'Here', 'Low', 'Pending', 'The image shows a dry dirt area with no visible drainage system, standing water, or blockages.', '2026-07-17 12:00:33'),
(42, 1, '1784334192_gut4.png', '5.55450000', '-0.19020000', 'the place ', 'Unreviewed', 'Pending', 'AI analysis unavailable at time of submission — needs manual review.', '2026-07-18 00:23:17'),
(46, 1, '1784336853_gut.png', '5.55450000', '-0.19020000', 'this is what i see', 'High', 'Pending', 'The drainage system is significantly blocked with debris and sediment, causing substantial standing water and indicating a high risk of flooding during rainfall. The blockage restricts water flow, leading to potential overflow onto surrounding areas and roads.', '2026-07-18 01:07:37'),
(51, 1, '1784477586_IMG_0638.jpeg', '5.55749430', '-0.20809989', 'Look at this', 'High', 'Pending', 'The drainage system is severely blocked with debris and appears to have standing water, indicating a high risk of localized flooding during rainfall. The blockage prevents proper water flow and suggests a potential for significant water accumulation.', '2026-07-19 16:13:10'),
(52, 1, '1784479259_se.png', '5.55756423', '-0.20803971', 'watch', 'High', 'Pending', 'Significant standing water indicates a severe blockage in the drainage system, leading to widespread flooding in the community. People are wading through the water, suggesting a serious disruption to normal life and potential danger.', '2026-07-19 16:41:07'),
(54, 1, '1784480125_IMG_0637.jpeg', '5.55763701', '-0.20795546', 'See', 'Medium', 'Pending', 'The drain contains a significant amount of sediment, dirt, and some scattered debris which could lead to a partial blockage if water levels rise.', '2026-07-19 16:55:28'),
(55, 1, '1784480148_IMG_0636.jpeg', '5.55760854', '-0.20792532', 'See', 'Medium', 'Pending', 'The drain contains some scattered plastic waste and sediment buildup, which constitutes a partial blockage that could impede water flow during heavy rain.', '2026-07-19 16:55:51'),
(56, 1, '1784480171_IMG_0638.jpeg', '5.55768095', '-0.20797617', 'See', 'Low', 'Pending', 'The drain appears mostly clear with water flowing through it, and while there is some minor floating debris, it does not pose an immediate blockage or flood risk.', '2026-07-19 16:56:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_insight_cache`
--
ALTER TABLE `ai_insight_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_hash` (`data_hash`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_insight_cache`
--
ALTER TABLE `ai_insight_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
