-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： localhost
-- 產生時間： 2026 年 06 月 04 日 03:38
-- 伺服器版本： 10.4.28-MariaDB
-- PHP 版本： 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `baseball_web`
--

-- --------------------------------------------------------

--
-- 資料表結構 `contact_us`
--

DROP TABLE IF EXISTS `contact_us`;
CREATE TABLE `contact_us` (
  `id` int(11) NOT NULL,
  `icon_class` varchar(50) NOT NULL,
  `content_text` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `contact_us`:
--

--
-- 傾印資料表的資料 `contact_us`
--

INSERT INTO `contact_us` (`id`, `icon_class`, `content_text`, `link`) VALUES(1, 'fas fa-map-marker-alt', '國立臺中科技大學 體育中心', NULL);
INSERT INTO `contact_us` (`id`, `icon_class`, `content_text`, `link`) VALUES(2, 'fas fa-envelope', 'nutc_baseball@edu.tw', NULL);
INSERT INTO `contact_us` (`id`, `icon_class`, `content_text`, `link`) VALUES(3, 'fas fa-phone', '04-2219-XXXX', NULL);
INSERT INTO `contact_us` (`id`, `icon_class`, `content_text`, `link`) VALUES(5, 'fab fa-instagram-square', 'instagram', 'https://www.instagram.com/nutc_baseball?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==');

-- --------------------------------------------------------

--
-- 資料表結構 `form`
--

DROP TABLE IF EXISTS `form`;
CREATE TABLE `form` (
  `form_id` int(11) NOT NULL COMMENT '申請單 ID',
  `team_id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `form_name` varchar(50) NOT NULL COMMENT '申請人姓名',
  `form_gender` varchar(10) DEFAULT NULL COMMENT '性別',
  `form_age` int(11) DEFAULT NULL COMMENT '年齡',
  `form_education` varchar(50) DEFAULT NULL COMMENT '學制 (五專/四技等)',
  `form_level` varchar(50) DEFAULT NULL COMMENT '棒球程度 (初學者/有基礎)',
  `form_position` varchar(255) DEFAULT NULL,
  `form_motive` text DEFAULT NULL COMMENT '加入動機',
  `form_contact` varchar(255) DEFAULT NULL COMMENT '聯絡資訊'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `form`:
--   `team_id`
--       `team` -> `team_Id`
--

-- --------------------------------------------------------

--
-- 資料表結構 `game`
--

DROP TABLE IF EXISTS `game`;
CREATE TABLE `game` (
  `Game_id` int(11) NOT NULL COMMENT '比賽識別碼',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `game_date` date DEFAULT NULL COMMENT '比賽日期',
  `game_time` time DEFAULT NULL COMMENT '比賽時間 (如: 12:30)',
  `location` varchar(100) DEFAULT NULL COMMENT '比賽地點',
  `opponent` varchar(1000) DEFAULT NULL,
  `result` varchar(10) DEFAULT NULL COMMENT '比賽結果 (如：14 vs 12 勝)',
  `batting_first` varchar(10) DEFAULT NULL COMMENT '先攻或後攻'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `game`:
--   `Team_Id`
--       `team` -> `team_Id`
--

--
-- 傾印資料表的資料 `game`
--

INSERT INTO `game` (`Game_id`, `Team_Id`, `game_date`, `game_time`, `location`, `opponent`, `result`, `batting_first`) VALUES(1, 1, '2026-05-13', '05:23:00', '地點', '測試對手', '8:4 勝', '先攻');
INSERT INTO `game` (`Game_id`, `Team_Id`, `game_date`, `game_time`, `location`, `opponent`, `result`, `batting_first`) VALUES(2, 1, '2026-05-12', '12:22:00', '地點', '對手', '5:3 勝', '後攻');
INSERT INTO `game` (`Game_id`, `Team_Id`, `game_date`, `game_time`, `location`, `opponent`, `result`, `batting_first`) VALUES(3, 1, '2026-05-31', '12:30:00', '地點', '對手', '', '後攻');

-- --------------------------------------------------------

--
-- 資料表結構 `gamerecord`
--

DROP TABLE IF EXISTS `gamerecord`;
CREATE TABLE `gamerecord` (
  `record_id` int(11) NOT NULL COMMENT '檔案紀錄 ID',
  `Game_Id` int(11) NOT NULL COMMENT '關聯 Game.game_id',
  `mId` int(11) NOT NULL COMMENT '關聯 Member.mId (上傳者)',
  `record_file` varchar(255) DEFAULT NULL COMMENT '紀錄表檔案路徑 (PDF/JPG)',
  `created_at` datetime DEFAULT NULL COMMENT '檔案上傳時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `gamerecord`:
--   `Game_Id`
--       `game` -> `Game_id`
--   `mId`
--       `member` -> `mId`
--

-- --------------------------------------------------------

--
-- 資料表結構 `game_lineups`
--

DROP TABLE IF EXISTS `game_lineups`;
CREATE TABLE `game_lineups` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `batting_order` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `position` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `sub_seq` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `game_lineups`:
--   `game_id`
--       `game` -> `Game_id`
--   `player_id`
--       `player` -> `Player_id`
--

--
-- 傾印資料表的資料 `game_lineups`
--

INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(64, 1, 1, 23, '1B', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(65, 1, 2, 24, '2B', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(66, 1, 3, 1, 'SS', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(67, 1, 4, 25, '3B', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(68, 1, 5, 26, 'C', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(69, 1, 6, 27, 'LF', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(70, 1, 7, 28, 'CF', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(71, 1, 8, 29, 'RF', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(72, 1, 9, 30, 'DH', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(73, 2, 1, 23, '1B', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(74, 2, 2, 24, '2B', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(75, 2, 3, 1, 'SS', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(76, 2, 4, 25, '3B', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(77, 2, 5, 26, 'C', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(78, 2, 6, 27, 'LF', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(79, 2, 7, 28, 'CF', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(80, 2, 8, 29, 'RF', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(81, 2, 9, 31, 'DH', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(82, 3, 1, 1, 'DH', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(83, 3, 2, 22, 'C', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(84, 3, 3, 23, '1B', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(85, 3, 4, 24, '2B', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(86, 3, 5, 25, '3B', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(87, 3, 6, 26, 'SS', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(88, 3, 7, 27, 'LF', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(89, 3, 8, 28, 'CF', 'active', 0);
INSERT INTO `game_lineups` (`id`, `game_id`, `batting_order`, `player_id`, `position`, `status`, `sub_seq`) VALUES(90, 3, 9, 29, 'RF', 'active', 0);

-- --------------------------------------------------------

--
-- 資料表結構 `game_live_logs`
--

DROP TABLE IF EXISTS `game_live_logs`;
CREATE TABLE `game_live_logs` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `inning` int(11) NOT NULL,
  `is_top` tinyint(4) NOT NULL,
  `outs` int(11) NOT NULL,
  `pa_result` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `game_live_logs`:
--   `game_id`
--       `game` -> `Game_id`
--

--
-- 傾印資料表的資料 `game_live_logs`
--

INSERT INTO `game_live_logs` (`id`, `game_id`, `inning`, `is_top`, `outs`, `pa_result`, `description`, `type`, `created_at`) VALUES(1, 3, 1, 1, 0, 'K', '', 'defense', '2026-05-29 12:15:34');
INSERT INTO `game_live_logs` (`id`, `game_id`, `inning`, `is_top`, `outs`, `pa_result`, `description`, `type`, `created_at`) VALUES(2, 3, 1, 1, 1, 'K', '', 'defense', '2026-05-29 12:15:44');
INSERT INTO `game_live_logs` (`id`, `game_id`, `inning`, `is_top`, `outs`, `pa_result`, `description`, `type`, `created_at`) VALUES(3, 3, 1, 1, 2, 'K', '', 'defense', '2026-05-29 12:15:49');
INSERT INTO `game_live_logs` (`id`, `game_id`, `inning`, `is_top`, `outs`, `pa_result`, `description`, `type`, `created_at`) VALUES(4, 3, 1, 0, 0, 'HR', '劉詠傑全壘打', 'offense', '2026-05-29 12:17:03');
INSERT INTO `game_live_logs` (`id`, `game_id`, `inning`, `is_top`, `outs`, `pa_result`, `description`, `type`, `created_at`) VALUES(5, 3, 1, 0, 0, '1B', '', 'offense', '2026-06-01 13:11:12');
INSERT INTO `game_live_logs` (`id`, `game_id`, `inning`, `is_top`, `outs`, `pa_result`, `description`, `type`, `created_at`) VALUES(6, 3, 1, 0, 0, 'K', '', 'offense', '2026-06-01 13:11:38');
INSERT INTO `game_live_logs` (`id`, `game_id`, `inning`, `is_top`, `outs`, `pa_result`, `description`, `type`, `created_at`) VALUES(7, 3, 1, 0, 1, 'K', '', 'offense', '2026-06-01 13:11:44');
INSERT INTO `game_live_logs` (`id`, `game_id`, `inning`, `is_top`, `outs`, `pa_result`, `description`, `type`, `created_at`) VALUES(8, 3, 1, 0, 2, 'K', '', 'offense', '2026-06-01 13:11:49');

-- --------------------------------------------------------

--
-- 資料表結構 `game_live_state`
--

DROP TABLE IF EXISTS `game_live_state`;
CREATE TABLE `game_live_state` (
  `game_id` int(11) NOT NULL,
  `current_batter_order` int(11) NOT NULL DEFAULT 1,
  `our_score` int(11) NOT NULL DEFAULT 0,
  `opponent_score` int(11) NOT NULL DEFAULT 0,
  `inning` int(11) NOT NULL DEFAULT 1,
  `is_top` tinyint(4) NOT NULL DEFAULT 0,
  `outs` int(11) NOT NULL DEFAULT 0,
  `balls` int(11) NOT NULL DEFAULT 0,
  `strikes` int(11) NOT NULL DEFAULT 0,
  `our_hits` int(11) NOT NULL DEFAULT 0,
  `opponent_hits` int(11) NOT NULL DEFAULT 0,
  `our_errors` int(11) NOT NULL DEFAULT 0,
  `opponent_errors` int(11) NOT NULL DEFAULT 0,
  `runner_first` tinyint(4) NOT NULL DEFAULT 0,
  `runner_second` tinyint(4) NOT NULL DEFAULT 0,
  `runner_third` tinyint(4) NOT NULL DEFAULT 0,
  `is_ended` tinyint(4) NOT NULL DEFAULT 0,
  `runner_first_id` int(11) DEFAULT NULL,
  `runner_second_id` int(11) DEFAULT NULL,
  `runner_third_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `game_live_state`:
--   `game_id`
--       `game` -> `Game_id`
--

--
-- 傾印資料表的資料 `game_live_state`
--

INSERT INTO `game_live_state` (`game_id`, `current_batter_order`, `our_score`, `opponent_score`, `inning`, `is_top`, `outs`, `balls`, `strikes`, `our_hits`, `opponent_hits`, `our_errors`, `opponent_errors`, `runner_first`, `runner_second`, `runner_third`, `is_ended`, `runner_first_id`, `runner_second_id`, `runner_third_id`) VALUES(1, 1, 8, 4, 9, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, NULL);
INSERT INTO `game_live_state` (`game_id`, `current_batter_order`, `our_score`, `opponent_score`, `inning`, `is_top`, `outs`, `balls`, `strikes`, `our_hits`, `opponent_hits`, `our_errors`, `opponent_errors`, `runner_first`, `runner_second`, `runner_third`, `is_ended`, `runner_first_id`, `runner_second_id`, `runner_third_id`) VALUES(2, 1, 5, 3, 9, 1, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, NULL);
INSERT INTO `game_live_state` (`game_id`, `current_batter_order`, `our_score`, `opponent_score`, `inning`, `is_top`, `outs`, `balls`, `strikes`, `our_hits`, `opponent_hits`, `our_errors`, `opponent_errors`, `runner_first`, `runner_second`, `runner_third`, `is_ended`, `runner_first_id`, `runner_second_id`, `runner_third_id`) VALUES(3, 6, 1, 0, 2, 1, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `game_pitchers`
--

DROP TABLE IF EXISTS `game_pitchers`;
CREATE TABLE `game_pitchers` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `pitcher_seq` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `game_pitchers`:
--   `game_id`
--       `game` -> `Game_id`
--   `player_id`
--       `player` -> `Player_id`
--

--
-- 傾印資料表的資料 `game_pitchers`
--

INSERT INTO `game_pitchers` (`id`, `game_id`, `player_id`, `status`, `pitcher_seq`) VALUES(8, 1, 1, 'substituted', 1);
INSERT INTO `game_pitchers` (`id`, `game_id`, `player_id`, `status`, `pitcher_seq`) VALUES(9, 1, 21, 'active', 2);
INSERT INTO `game_pitchers` (`id`, `game_id`, `player_id`, `status`, `pitcher_seq`) VALUES(10, 2, 21, 'substituted', 1);
INSERT INTO `game_pitchers` (`id`, `game_id`, `player_id`, `status`, `pitcher_seq`) VALUES(11, 2, 1, 'active', 2);
INSERT INTO `game_pitchers` (`id`, `game_id`, `player_id`, `status`, `pitcher_seq`) VALUES(12, 3, 21, 'active', 1);

-- --------------------------------------------------------

--
-- 資料表結構 `member`
--

DROP TABLE IF EXISTS `member`;
CREATE TABLE `member` (
  `mId` int(11) NOT NULL COMMENT '會員唯一識別碼',
  `account` varchar(50) NOT NULL COMMENT '登入帳號',
  `password` varchar(255) NOT NULL COMMENT '加密後的密碼',
  `name` varchar(50) NOT NULL COMMENT '使用者姓名',
  `role` enum('fan','player','admin','ob') NOT NULL COMMENT '權限等級',
  `status` enum('pending','active') NOT NULL COMMENT '審核狀態',
  `created_at` datetime NOT NULL COMMENT '帳號註冊時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `member`:
--

--
-- 傾印資料表的資料 `member`
--

INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(1, 'admin', 'admin', '管理者(Admin)', 'admin', 'active', '2026-05-13 11:00:08');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(2, 'user1', '123', '管理者(User1)', 'admin', 'active', '2026-05-13 11:00:08');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(4, 'Jeff', 'jeff', '劉詠傑', 'player', 'active', '2026-05-13 11:09:37');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(5, 'ting', 'j11995665', '黃郁婷', 'admin', 'active', '2026-05-14 05:39:35');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(6, 'admin2', 'admin123', 'Admin Two', 'player', 'pending', '2026-05-27 11:00:49');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(7, '球員1', '球員1', '球員1', 'player', 'active', '2026-05-28 00:55:40');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(8, '球員2', '球員2', '球員2', 'player', 'active', '2026-05-28 00:55:40');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(9, '球員3', '球員3', '球員3', 'player', 'active', '2026-05-28 00:55:40');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(10, '球員4', '球員4', '球員4', 'player', 'active', '2026-05-28 00:55:40');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(11, '球員5', '球員5', '球員5', 'player', 'active', '2026-05-28 00:55:40');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(12, '球員6', '球員6', '球員6', 'player', 'active', '2026-05-28 00:55:40');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(13, '球員7', '球員7', '球員7', 'player', 'active', '2026-05-28 00:55:40');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(14, '球員8', '球員8', '球員8', 'player', 'active', '2026-05-28 00:55:40');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(15, '球員9', '球員9', '球員9', 'player', 'active', '2026-05-28 00:55:40');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(16, '球員10', '球員10', '球員10', 'player', 'active', '2026-05-28 00:55:40');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(17, '球員11', '球員11', '球員11', 'player', 'active', '2026-05-28 00:55:40');
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(18, '球員12', '球員12', '球員12', 'player', 'active', '2026-05-28 00:55:40');

-- --------------------------------------------------------

--
-- 資料表結構 `news`
--

DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `news_id` int(11) NOT NULL COMMENT '消息識別碼',
  `title` varchar(200) NOT NULL COMMENT '消息標題',
  `content` text NOT NULL COMMENT '消息內容',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '發布時間',
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `news`:
--

--
-- 傾印資料表的資料 `news`
--

INSERT INTO `news` (`news_id`, `title`, `content`, `created_at`, `link`) VALUES(6, '測試', '5/12', '2026-05-13 23:15:09', NULL);
INSERT INTO `news` (`news_id`, `title`, `content`, `created_at`, `link`) VALUES(7, '一般組全國賽 SSU新聞文', '114學年度UBL大專棒球聯賽一般組全國賽進入小組預賽最後一日，國立高雄科技大學帶著一勝一敗戰績，在立德棒球場迎戰國立臺中科技大學。兩隊仍保有晉級機會，此役形同背水一戰。高科靠著許少瑄主投5局飆出9次三振穩住戰局，並在二局單局攻下5分完成逆轉，終場以9:2五局扣倒中科，續命保住晉級希望。\r\n中科開賽即先聲奪人。一局上林碁晟敲出內野安打展開攻勢，王駿丞補上二安攻佔得點圈，隨後透過野選與對手失誤跑回2分，取得2:0領先。\r\n二局下，高科黃柏叡、林毓珩、李毓恆接連敲安串聯攻勢，施宥廷把握得點圈機會，一棒掃出左外野方向三壘安打，帶有3分打點，單局灌進5分完成逆轉，將比分改寫為5:2。\r\n四局下高科再添保險分。施宥廷與林恩宇連續二壘安打擴大差距。五局下攻勢持續延燒，黃柏叡、林毓珩、李毓恆再度串聯安打送回2分，代打張鈞展擊出二壘方向滾地球，壘上跑者趁勢衝回本壘得分，比數拉開至9:2，提前結束比賽。\r\n高科施宥廷2支2，含一支3分打點三安與一支二安，成為高科逆轉關鍵火力。攝／許靜玟\r\n高科施宥廷2支2，含一支3分打點三安與一支二安，成為高科逆轉關鍵火力。攝／許靜玟\r\n此役高科先發投手許少瑄展現壓制力，主投5局僅用62球，被敲兩支安打，失1分自責分，送出9次三振，成功封鎖中科後段反攻氣勢，賽後防禦率1.80，收下本場勝投。\r\n許少瑄表示，此役是自己在本屆賽事中首度登板，「就是順順丟，把握機會。」球隊曾停止運作一年，他坦言球隊人數不多，「我們就是一場一場打，把每一場都當最後一場。」\r\n中科隊長劉詠傑則表示，抽籤出爐時便知道本組強度高，「但我們不希望因為對手是誰，就改變自己的打法，我們就是打自己的球。」本屆是劉詠傑第四次參加大專棒球聯賽，他指出球隊四年都晉級全國賽，曾闖進16強，今年卻止步小組賽，難免遺憾。\r\n「這三場真的很不簡單，辛苦大家了。」劉詠傑說道。身為隊長，他語氣堅定，「我相信我們不只是這樣，希望學弟們明年可以準備得更好，把成績再往上推。」\r\n隨著小組賽落幕，高科在關鍵戰拿下勝利，保住晉級希望，將於明(5)日迎戰東海大學，力拚16強。\r\n ', '2026-05-14 09:19:56', 'https://www.ssu.org.tw/News/Detail/3b9b26c7-1d85-4935-8a4a-c99fd658d5e8');
INSERT INTO `news` (`news_id`, `title`, `content`, `created_at`, `link`) VALUES(8, '測試', '邱偉宸是', '2026-05-15 09:34:17', NULL);
INSERT INTO `news` (`news_id`, `title`, `content`, `created_at`, `link`) VALUES(9, '測試', '亮澤紅柿', '2026-05-15 09:34:35', NULL);
INSERT INTO `news` (`news_id`, `title`, `content`, `created_at`, `link`) VALUES(10, '5/21', '測試', '2026-05-21 12:45:23', NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `ob`
--

DROP TABLE IF EXISTS `ob`;
CREATE TABLE `ob` (
  `Ob_id` int(11) NOT NULL COMMENT '校友 ID',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `OB_name` varchar(50) NOT NULL COMMENT '畢業學長姐姓名',
  `graduation_year` int(11) DEFAULT NULL COMMENT '畢業年度',
  `status` varchar(100) DEFAULT NULL COMMENT '畢業後現況或豐功偉業',
  `image_path` varchar(255) DEFAULT NULL COMMENT 'OB照片路徑'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `ob`:
--   `Team_Id`
--       `team` -> `team_Id`
--

--
-- 傾印資料表的資料 `ob`
--

INSERT INTO `ob` (`Ob_id`, `Team_Id`, `OB_name`, `graduation_year`, `status`, `image_path`) VALUES(4, 1, '蔡承庭', 113, '在中興大學當魔鷹', NULL);
INSERT INTO `ob` (`Ob_id`, `Team_Id`, `OB_name`, `graduation_year`, `status`, `image_path`) VALUES(5, 1, '徐崇舜', 114, '在馬祖報效國家', NULL);
INSERT INTO `ob` (`Ob_id`, `Team_Id`, `OB_name`, `graduation_year`, `status`, `image_path`) VALUES(6, 1, '范光磊', 114, NULL, NULL);
INSERT INTO `ob` (`Ob_id`, `Team_Id`, `OB_name`, `graduation_year`, `status`, `image_path`) VALUES(7, 1, '游安田', 114, '休學超廢，沒有啦哈哈哈', NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE `player` (
  `Player_id` int(11) NOT NULL COMMENT '球員識別碼',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `mId` int(11) DEFAULT NULL COMMENT '關聯 Member.mId (僅限本校球員，可為 NULL)',
  `Player_Name` varchar(50) NOT NULL COMMENT '球員姓名',
  `jersey_number` varchar(10) DEFAULT NULL COMMENT '背號 (如: 18, 93)',
  `position` varchar(255) DEFAULT NULL,
  `height` int(11) DEFAULT NULL COMMENT '身高 (cm)',
  `weight` int(11) DEFAULT NULL COMMENT '體重 (kg)',
  `image_path` varchar(255) DEFAULT NULL COMMENT '球員照片檔案路徑'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `player`:
--   `Team_Id`
--       `team` -> `team_Id`
--   `mId`
--       `member` -> `mId`
--

--
-- 傾印資料表的資料 `player`
--

INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(1, 1, 4, '劉詠傑', '21', '投手,內野手', 180, 79, 'uploads/players/1778663574_IMG_3109.JPG');
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(21, 1, 7, '球員1', '1', '投手', 175, 70, NULL);
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(22, 1, 8, '球員2', '2', '捕手', 175, 70, NULL);
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(23, 1, 9, '球員3', '3', '內野手', 175, 70, NULL);
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(24, 1, 10, '球員4', '4', '內野手', 175, 70, NULL);
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(25, 1, 11, '球員5', '5', '內野手', 175, 70, NULL);
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(26, 1, 12, '球員6', '6', '內野手', 175, 70, NULL);
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(27, 1, 13, '球員7', '7', '外野手', 175, 70, NULL);
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(28, 1, 14, '球員8', '8', '外野手', 175, 70, NULL);
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(29, 1, 15, '球員9', '9', '外野手', 175, 70, NULL);
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(30, 1, 16, '球員10', '10', '投手,捕手,內野手,外野手', 175, 70, NULL);
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(31, 1, 17, '球員11', '11', '投手,捕手,內野手,外野手', 175, 70, NULL);
INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `image_path`) VALUES(32, 1, 18, '球員12', '12', '投手,捕手,內野手,外野手', 175, 70, NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `playerrecord`
--

DROP TABLE IF EXISTS `playerrecord`;
CREATE TABLE `playerrecord` (
  `Player_record_Id` int(11) NOT NULL COMMENT '數據 ID',
  `Record_Id` int(11) NOT NULL COMMENT '關聯 GameRecord.record_id',
  `Player_Id` int(11) NOT NULL COMMENT '關聯 Player.player_id',
  `hit` int(11) DEFAULT NULL COMMENT '安打數',
  `rbi` int(11) DEFAULT NULL COMMENT '打點',
  `runs` int(11) DEFAULT NULL COMMENT '得分',
  `at_bats` int(11) DEFAULT NULL COMMENT '打席/打數',
  `avg` decimal(4,3) DEFAULT NULL COMMENT '打擊率'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `playerrecord`:
--   `Record_Id`
--       `gamerecord` -> `record_id`
--   `Player_Id`
--       `player` -> `Player_id`
--

-- --------------------------------------------------------

--
-- 資料表結構 `player_game_details`
--

DROP TABLE IF EXISTS `player_game_details`;
CREATE TABLE `player_game_details` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `pa_count` int(11) DEFAULT 0,
  `pa_results` text DEFAULT NULL,
  `pitches` int(11) DEFAULT 0,
  `innings` varchar(10) DEFAULT '0',
  `strikeouts` int(11) DEFAULT 0,
  `walks` int(11) DEFAULT 0,
  `earned_runs` int(11) DEFAULT 0,
  `rbi` int(11) DEFAULT 0,
  `runs` int(11) DEFAULT 0,
  `stolen_bases` int(11) DEFAULT 0,
  `sac_bunt` int(11) DEFAULT 0,
  `sac_fly` int(11) DEFAULT 0,
  `hit_by_pitch` int(11) DEFAULT 0,
  `go_outs` int(11) DEFAULT 0,
  `fo_outs` int(11) DEFAULT 0,
  `is_start` tinyint(4) DEFAULT 0,
  `is_relief` tinyint(4) DEFAULT 0,
  `is_cg` tinyint(4) DEFAULT 0,
  `is_sho` tinyint(4) DEFAULT 0,
  `win` tinyint(4) DEFAULT 0,
  `loss` tinyint(4) DEFAULT 0,
  `save` tinyint(4) DEFAULT 0,
  `blown_save` tinyint(4) DEFAULT 0,
  `hold` tinyint(4) DEFAULT 0,
  `batters_faced` int(11) DEFAULT 0,
  `hits_allowed` int(11) DEFAULT 0,
  `wild_pitches` int(11) DEFAULT 0,
  `balks` int(11) DEFAULT 0,
  `runs_allowed` int(11) DEFAULT 0,
  `p_go_outs` int(11) DEFAULT 0,
  `p_fo_outs` int(11) DEFAULT 0,
  `p_hit_by_pitch` int(11) DEFAULT 0,
  `p_hr_allowed` int(11) DEFAULT 0,
  `strikes` int(11) DEFAULT 0,
  `balls` int(11) DEFAULT 0,
  `swings` int(11) DEFAULT 0,
  `first_pitch_swings` int(11) DEFAULT 0,
  `whiffs` int(11) DEFAULT 0,
  `gb_count` int(11) DEFAULT 0,
  `ld_count` int(11) DEFAULT 0,
  `fb_count` int(11) DEFAULT 0,
  `hard_hit` int(11) DEFAULT 0,
  `soft_hit` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `player_game_details`:
--   `game_id`
--       `game` -> `Game_id`
--   `player_id`
--       `player` -> `Player_id`
--

--
-- 傾印資料表的資料 `player_game_details`
--

INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(10, 1, 23, 5, '1B, GO, 1B, FO, GO', 0, '0', 0, 0, 0, 1, 1, 0, 0, 0, 0, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(11, 1, 24, 5, 'BB, 1B, FO, K, GO', 0, '0', 1, 1, 0, 0, 1, 1, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(12, 1, 1, 5, '1B, 2B, HR, FO, GO', 95, '6', 7, 2, 3, 3, 2, 0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 1, 0, 0, 0, 0, 25, 5, 0, 0, 3, 0, 0, 0, 0, 60, 35, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(13, 1, 25, 5, 'HR, GO, FO, BB, K', 0, '0', 1, 1, 0, 2, 2, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(14, 1, 26, 4, 'FO, 1B, K, GO', 0, '0', 1, 0, 0, 1, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(15, 1, 27, 4, 'GO, K, FO, FO', 0, '0', 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(16, 1, 28, 4, 'BB, 1B, GO, K', 0, '0', 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(17, 1, 29, 4, 'GO, K, GO, FO', 0, '0', 1, 0, 0, 0, 0, 0, 0, 0, 0, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(18, 1, 30, 4, 'BB, GO, FO, GO', 0, '0', 0, 1, 0, 1, 1, 0, 0, 0, 0, 2, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(19, 1, 21, 0, NULL, 42, '3', 3, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 12, 2, 0, 0, 1, 0, 0, 0, 0, 28, 14, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(20, 2, 23, 4, '1B, GO, 1B, FO', 0, '0', 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(21, 2, 24, 4, 'BB, 1B, FO, GO', 0, '0', 0, 1, 0, 0, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(22, 2, 1, 4, '1B, 2B, BB, GO', 0, '0', 0, 1, 0, 1, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(23, 2, 25, 4, '2B, GO, K, FO', 0, '0', 1, 0, 0, 2, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(24, 2, 26, 4, '1B, K, GO, FO', 0, '0', 1, 0, 0, 1, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(25, 2, 27, 3, 'GO, K, FO', 0, '0', 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(26, 2, 28, 3, '1B, GO, K', 0, '0', 1, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(27, 2, 29, 3, 'GO, K, GO', 0, '0', 1, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(28, 2, 31, 3, 'BB, GO, FO', 0, '0', 0, 1, 0, 1, 1, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(29, 2, 1, 0, NULL, 25, '2', 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 7, 1, 0, 0, 0, 0, 0, 0, 0, 18, 7, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(30, 3, 21, 0, NULL, 10, '1', 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(31, 3, 1, 1, 'HR', 0, '0', 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(32, 3, 22, 1, '1B', 0, '0', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 0, 2, 0, 0, 0, 0, 0, 1, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(33, 3, 23, 1, 'K', 0, '0', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(34, 3, 24, 1, 'K', 0, '0', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
INSERT INTO `player_game_details` (`id`, `game_id`, `player_id`, `pa_count`, `pa_results`, `pitches`, `innings`, `strikeouts`, `walks`, `earned_runs`, `rbi`, `runs`, `stolen_bases`, `sac_bunt`, `sac_fly`, `hit_by_pitch`, `go_outs`, `fo_outs`, `is_start`, `is_relief`, `is_cg`, `is_sho`, `win`, `loss`, `save`, `blown_save`, `hold`, `batters_faced`, `hits_allowed`, `wild_pitches`, `balks`, `runs_allowed`, `p_go_outs`, `p_fo_outs`, `p_hit_by_pitch`, `p_hr_allowed`, `strikes`, `balls`, `swings`, `first_pitch_swings`, `whiffs`, `gb_count`, `ld_count`, `fb_count`, `hard_hit`, `soft_hit`) VALUES(35, 3, 25, 1, 'K', 0, '0', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- 資料表結構 `recruitmentinfo`
--

DROP TABLE IF EXISTS `recruitmentinfo`;
CREATE TABLE `recruitmentinfo` (
  `Recruit_Id` int(11) NOT NULL COMMENT '招募資訊 ID',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `intro` text DEFAULT NULL COMMENT '女棒或球隊招生簡介',
  `recruitment_info` text DEFAULT NULL COMMENT '招生對象、練習時間等細節',
  `contact_info` varchar(200) DEFAULT NULL COMMENT '球經聯繫方式或社群連結'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `recruitmentinfo`:
--   `Team_Id`
--       `team` -> `team_Id`
--

-- --------------------------------------------------------

--
-- 資料表結構 `team`
--

DROP TABLE IF EXISTS `team`;
CREATE TABLE `team` (
  `team_Id` int(11) NOT NULL COMMENT '球隊唯一識別碼',
  `team_name` varchar(50) NOT NULL COMMENT '球隊名稱',
  `team_type` varchar(20) NOT NULL COMMENT '隊伍類型：Men, Woman, OB'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `team`:
--

--
-- 傾印資料表的資料 `team`
--

INSERT INTO `team` (`team_Id`, `team_name`, `team_type`) VALUES(1, '中科大男棒', 'Men');
INSERT INTO `team` (`team_Id`, `team_name`, `team_type`) VALUES(2, '中科大女棒', 'Woman');
INSERT INTO `team` (`team_Id`, `team_name`, `team_type`) VALUES(3, '中科大OB', 'OB');

-- --------------------------------------------------------

--
-- 資料表結構 `teamhistory`
--

DROP TABLE IF EXISTS `teamhistory`;
CREATE TABLE `teamhistory` (
  `History_Id` int(11) NOT NULL COMMENT '紀錄識別碼',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `title` varchar(100) NOT NULL COMMENT '標題',
  `content` text DEFAULT NULL COMMENT '詳細簡介內容',
  `start_year` int(11) DEFAULT NULL COMMENT '起始年份',
  `month` int(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `teamhistory`:
--   `Team_Id`
--       `team` -> `team_Id`
--

--
-- 傾印資料表的資料 `teamhistory`
--

INSERT INTO `teamhistory` (`History_Id`, `Team_Id`, `title`, `content`, `start_year`, `month`) VALUES(1, 1, '2012 球隊創立', '中科大棒球隊於 2012 年正式成立，由一群熱愛棒球的同學共同創隊，從零開始打造屬於中科大的棒球文化。', 2012, NULL);
INSERT INTO `teamhistory` (`History_Id`, `Team_Id`, `title`, `content`, `start_year`, `month`) VALUES(5, 1, '劉詠傑加入球隊', '內容描述（讚）', 2022, 9);
INSERT INTO `teamhistory` (`History_Id`, `Team_Id`, `title`, `content`, `start_year`, `month`) VALUES(6, 1, '做了這個網頁', '內容描述', 2026, 2);

-- --------------------------------------------------------

--
-- 資料表結構 `video`
--

DROP TABLE IF EXISTS `video`;
CREATE TABLE `video` (
  `Video_id` int(11) NOT NULL COMMENT '影片唯一識別碼',
  `Team_Id` int(11) DEFAULT NULL COMMENT '關聯 Team.team_Id',
  `title` varchar(255) DEFAULT NULL COMMENT '影片標題',
  `description` text DEFAULT NULL COMMENT '描述',
  `url` varchar(255) DEFAULT NULL COMMENT 'YouTube 影片連結',
  `date` date DEFAULT NULL COMMENT '日期',
  `category` varchar(100) DEFAULT NULL COMMENT '分類'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `video`:
--   `Team_Id`
--       `team` -> `team_Id`
--

--
-- 傾印資料表的資料 `video`
--

INSERT INTO `video` (`Video_id`, `Team_Id`, `title`, `description`, `url`, `date`, `category`) VALUES(1, NULL, '114大專盃排名賽vs臺灣體大', '114大專盃排名賽vs臺灣體大', 'https://youtube.com/playlist?list=PL7QoN_5StDVPwuKoIFjHypu3IHfo4k4ie&si=CJm79C5FZGOyefZh', '2025-12-14', '比賽紀錄');
INSERT INTO `video` (`Video_id`, `Team_Id`, `title`, `description`, `url`, `date`, `category`) VALUES(2, NULL, '114大專盃預賽vs中興大學', '114大專盃預賽vs中興大學', 'https://youtube.com/playlist?list=PL7QoN_5StDVMiLCn692J_hSu8cPeBaqX4&si=Pnyd-AnEKYJTlT1R', '2025-12-02', '比賽紀錄');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `contact_us`
--
ALTER TABLE `contact_us`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `form`
--
ALTER TABLE `form`
  ADD PRIMARY KEY (`form_id`),
  ADD KEY `team_id` (`team_id`);

--
-- 資料表索引 `game`
--
ALTER TABLE `game`
  ADD PRIMARY KEY (`Game_id`),
  ADD KEY `Team_Id` (`Team_Id`);

--
-- 資料表索引 `gamerecord`
--
ALTER TABLE `gamerecord`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `Game_Id` (`Game_Id`),
  ADD KEY `mId` (`mId`);

--
-- 資料表索引 `game_lineups`
--
ALTER TABLE `game_lineups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `player_id` (`player_id`);

--
-- 資料表索引 `game_live_logs`
--
ALTER TABLE `game_live_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

--
-- 資料表索引 `game_live_state`
--
ALTER TABLE `game_live_state`
  ADD PRIMARY KEY (`game_id`);

--
-- 資料表索引 `game_pitchers`
--
ALTER TABLE `game_pitchers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `player_id` (`player_id`);

--
-- 資料表索引 `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`mId`);

--
-- 資料表索引 `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`news_id`);

--
-- 資料表索引 `ob`
--
ALTER TABLE `ob`
  ADD PRIMARY KEY (`Ob_id`),
  ADD KEY `Team_Id` (`Team_Id`);

--
-- 資料表索引 `player`
--
ALTER TABLE `player`
  ADD PRIMARY KEY (`Player_id`),
  ADD KEY `Team_Id` (`Team_Id`),
  ADD KEY `mId` (`mId`);

--
-- 資料表索引 `playerrecord`
--
ALTER TABLE `playerrecord`
  ADD PRIMARY KEY (`Player_record_Id`),
  ADD KEY `Record_Id` (`Record_Id`),
  ADD KEY `Player_Id` (`Player_Id`);

--
-- 資料表索引 `player_game_details`
--
ALTER TABLE `player_game_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `player_id` (`player_id`);

--
-- 資料表索引 `recruitmentinfo`
--
ALTER TABLE `recruitmentinfo`
  ADD PRIMARY KEY (`Recruit_Id`),
  ADD KEY `Team_Id` (`Team_Id`);

--
-- 資料表索引 `team`
--
ALTER TABLE `team`
  ADD PRIMARY KEY (`team_Id`);

--
-- 資料表索引 `teamhistory`
--
ALTER TABLE `teamhistory`
  ADD PRIMARY KEY (`History_Id`),
  ADD KEY `Team_Id` (`Team_Id`);

--
-- 資料表索引 `video`
--
ALTER TABLE `video`
  ADD PRIMARY KEY (`Video_id`),
  ADD KEY `fk_video_team` (`Team_Id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `contact_us`
--
ALTER TABLE `contact_us`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `form`
--
ALTER TABLE `form`
  MODIFY `form_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '申請單 ID', AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `game`
--
ALTER TABLE `game`
  MODIFY `Game_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '比賽識別碼', AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `gamerecord`
--
ALTER TABLE `gamerecord`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '檔案紀錄 ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `game_lineups`
--
ALTER TABLE `game_lineups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `game_live_logs`
--
ALTER TABLE `game_live_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `game_pitchers`
--
ALTER TABLE `game_pitchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `member`
--
ALTER TABLE `member`
  MODIFY `mId` int(11) NOT NULL AUTO_INCREMENT COMMENT '會員唯一識別碼', AUTO_INCREMENT=19;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `news`
--
ALTER TABLE `news`
  MODIFY `news_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '消息識別碼', AUTO_INCREMENT=11;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `ob`
--
ALTER TABLE `ob`
  MODIFY `Ob_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '校友 ID', AUTO_INCREMENT=8;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `player`
--
ALTER TABLE `player`
  MODIFY `Player_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '球員識別碼', AUTO_INCREMENT=33;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `playerrecord`
--
ALTER TABLE `playerrecord`
  MODIFY `Player_record_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '數據 ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `player_game_details`
--
ALTER TABLE `player_game_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `recruitmentinfo`
--
ALTER TABLE `recruitmentinfo`
  MODIFY `Recruit_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '招募資訊 ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `team`
--
ALTER TABLE `team`
  MODIFY `team_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '球隊唯一識別碼', AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `teamhistory`
--
ALTER TABLE `teamhistory`
  MODIFY `History_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '紀錄識別碼', AUTO_INCREMENT=7;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `video`
--
ALTER TABLE `video`
  MODIFY `Video_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '影片唯一識別碼', AUTO_INCREMENT=3;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `form`
--
ALTER TABLE `form`
  ADD CONSTRAINT `form_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_Id`);

--
-- 資料表的限制式 `game`
--
ALTER TABLE `game`
  ADD CONSTRAINT `game_ibfk_1` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`);

--
-- 資料表的限制式 `gamerecord`
--
ALTER TABLE `gamerecord`
  ADD CONSTRAINT `gamerecord_ibfk_1` FOREIGN KEY (`Game_Id`) REFERENCES `game` (`Game_id`),
  ADD CONSTRAINT `gamerecord_ibfk_2` FOREIGN KEY (`mId`) REFERENCES `member` (`mId`);

--
-- 資料表的限制式 `game_lineups`
--
ALTER TABLE `game_lineups`
  ADD CONSTRAINT `game_lineups_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`Game_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_lineups_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `player` (`Player_id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `game_live_logs`
--
ALTER TABLE `game_live_logs`
  ADD CONSTRAINT `game_live_logs_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`Game_id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `game_live_state`
--
ALTER TABLE `game_live_state`
  ADD CONSTRAINT `game_live_state_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`Game_id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `game_pitchers`
--
ALTER TABLE `game_pitchers`
  ADD CONSTRAINT `game_pitchers_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`Game_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_pitchers_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `player` (`Player_id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `ob`
--
ALTER TABLE `ob`
  ADD CONSTRAINT `ob_ibfk_1` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`);

--
-- 資料表的限制式 `player`
--
ALTER TABLE `player`
  ADD CONSTRAINT `player_ibfk_1` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`),
  ADD CONSTRAINT `player_ibfk_2` FOREIGN KEY (`mId`) REFERENCES `member` (`mId`);

--
-- 資料表的限制式 `playerrecord`
--
ALTER TABLE `playerrecord`
  ADD CONSTRAINT `playerrecord_ibfk_1` FOREIGN KEY (`Record_Id`) REFERENCES `gamerecord` (`record_id`),
  ADD CONSTRAINT `playerrecord_ibfk_2` FOREIGN KEY (`Player_Id`) REFERENCES `player` (`Player_id`);

--
-- 資料表的限制式 `player_game_details`
--
ALTER TABLE `player_game_details`
  ADD CONSTRAINT `player_game_details_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`Game_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_game_details_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `player` (`Player_id`) ON DELETE CASCADE;

--
-- 資料表的限制式 `recruitmentinfo`
--
ALTER TABLE `recruitmentinfo`
  ADD CONSTRAINT `recruitmentinfo_ibfk_1` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`);

--
-- 資料表的限制式 `teamhistory`
--
ALTER TABLE `teamhistory`
  ADD CONSTRAINT `teamhistory_ibfk_1` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`);

--
-- 資料表的限制式 `video`
--
ALTER TABLE `video`
  ADD CONSTRAINT `fk_video_team` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
