-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2026-03-26 03:42:21
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.0.30

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
CREATE DATABASE IF NOT EXISTS `baseball_web` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `baseball_web`;

-- --------------------------------------------------------

--
-- 資料表結構 `ai_analysis`
--

DROP TABLE IF EXISTS `ai_analysis`;
CREATE TABLE IF NOT EXISTS `ai_analysis` (
  `Analysis_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分析 ID',
  `Game_Id` int(11) NOT NULL COMMENT '關聯 Game.game_id',
  `summary` text DEFAULT NULL COMMENT 'AI 生成的比賽摘要文字',
  `created_at` datetime DEFAULT NULL COMMENT '分析生成時間',
  PRIMARY KEY (`Analysis_Id`),
  KEY `Game_Id` (`Game_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `contact_us`
--

DROP TABLE IF EXISTS `contact_us`;
CREATE TABLE IF NOT EXISTS `contact_us` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `icon_class` varchar(50) NOT NULL,
  `content_text` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `form`
--

DROP TABLE IF EXISTS `form`;
CREATE TABLE IF NOT EXISTS `form` (
  `form_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '申請單 ID',
  `team_id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `form_name` varchar(50) NOT NULL COMMENT '申請人姓名',
  `form_gender` varchar(10) DEFAULT NULL COMMENT '性別',
  `form_age` int(11) DEFAULT NULL COMMENT '年齡',
  `form_education` varchar(50) DEFAULT NULL COMMENT '學制 (五專/四技等)',
  `form_level` varchar(50) DEFAULT NULL COMMENT '棒球程度 (初學者/有基礎)',
  `form_position` varchar(50) DEFAULT NULL COMMENT '守備位置 (可複選，如: 投手,外野手)',
  `form_motive` text DEFAULT NULL COMMENT '加入動機',
  `form_contact` varchar(255) DEFAULT NULL COMMENT '聯絡資訊',
  PRIMARY KEY (`form_id`),
  KEY `team_id` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `game`
--

DROP TABLE IF EXISTS `game`;
CREATE TABLE IF NOT EXISTS `game` (
  `Game_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '比賽識別碼',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `game_date` date DEFAULT NULL COMMENT '比賽日期',
  `game_time` time DEFAULT NULL COMMENT '比賽時間 (如: 12:30)',
  `location` varchar(100) DEFAULT NULL COMMENT '比賽地點',
  `opponent` varchar(100) DEFAULT NULL COMMENT '對手學校名稱',
  `result` varchar(10) DEFAULT NULL COMMENT '比賽結果 (如：14 vs 12 勝)',
  PRIMARY KEY (`Game_id`),
  KEY `Team_Id` (`Team_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `gamerecord`
--

DROP TABLE IF EXISTS `gamerecord`;
CREATE TABLE IF NOT EXISTS `gamerecord` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '檔案紀錄 ID',
  `Game_Id` int(11) NOT NULL COMMENT '關聯 Game.game_id',
  `mId` int(11) NOT NULL COMMENT '關聯 Member.mId (上傳者)',
  `record_file` varchar(255) DEFAULT NULL COMMENT '紀錄表檔案路徑 (PDF/JPG)',
  `created_at` datetime DEFAULT NULL COMMENT '檔案上傳時間',
  PRIMARY KEY (`record_id`),
  KEY `Game_Id` (`Game_Id`),
  KEY `mId` (`mId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `member`
--

DROP TABLE IF EXISTS `member`;
CREATE TABLE IF NOT EXISTS `member` (
  `mId` int(11) NOT NULL AUTO_INCREMENT COMMENT '會員唯一識別碼',
  `account` varchar(50) NOT NULL COMMENT '登入帳號',
  `password` varchar(255) NOT NULL COMMENT '加密後的密碼',
  `name` varchar(50) NOT NULL COMMENT '使用者姓名',
  `role` enum('fan','player','admin') NOT NULL COMMENT '權限等級',
  `status` enum('pending','active') NOT NULL COMMENT '審核狀態',
  `created_at` datetime NOT NULL COMMENT '帳號註冊時間',
  PRIMARY KEY (`mId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `news`
--

DROP TABLE IF EXISTS `news`;
CREATE TABLE IF NOT EXISTS `news` (
  `news_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '消息識別碼',
  `title` varchar(200) NOT NULL COMMENT '消息標題',
  `content` text NOT NULL COMMENT '消息內容',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '發布時間',
  PRIMARY KEY (`news_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `ob`
--

DROP TABLE IF EXISTS `ob`;
CREATE TABLE IF NOT EXISTS `ob` (
  `Ob_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '校友 ID',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `OB_name` varchar(50) NOT NULL COMMENT '畢業學長姐姓名',
  `graduation_year` int(11) DEFAULT NULL COMMENT '畢業年度',
  `status` varchar(100) DEFAULT NULL COMMENT '畢業後現況或豐功偉業',
  `image_path` varchar(255) DEFAULT NULL COMMENT 'OB照片路徑',
  PRIMARY KEY (`Ob_id`),
  KEY `Team_Id` (`Team_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE IF NOT EXISTS `player` (
  `Player_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '球員識別碼',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `mId` int(11) DEFAULT NULL COMMENT '關聯 Member.mId (僅限本校球員，可為 NULL)',
  `Player_Name` varchar(50) NOT NULL COMMENT '球員姓名',
  `jersey_number` varchar(10) DEFAULT NULL COMMENT '背號 (如: 18, 93)',
  `position` varchar(20) DEFAULT NULL COMMENT '守位',
  `height` int(11) DEFAULT NULL COMMENT '身高 (cm)',
  `weight` int(11) DEFAULT NULL COMMENT '體重 (kg)',
  `pitching_speed` int(11) DEFAULT NULL COMMENT '球速 (km/h)',
  `image_path` varchar(255) DEFAULT NULL COMMENT '球員照片檔案路徑',
  PRIMARY KEY (`Player_id`),
  KEY `Team_Id` (`Team_Id`),
  KEY `mId` (`mId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `playerrecord`
--

DROP TABLE IF EXISTS `playerrecord`;
CREATE TABLE IF NOT EXISTS `playerrecord` (
  `Player_record_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '數據 ID',
  `Record_Id` int(11) NOT NULL COMMENT '關聯 GameRecord.record_id',
  `Player_Id` int(11) NOT NULL COMMENT '關聯 Player.player_id',
  `hit` int(11) DEFAULT NULL COMMENT '安打數',
  `rbi` int(11) DEFAULT NULL COMMENT '打點',
  `runs` int(11) DEFAULT NULL COMMENT '得分',
  `at_bats` int(11) DEFAULT NULL COMMENT '打席/打數',
  `avg` decimal(4,3) DEFAULT NULL COMMENT '打擊率',
  PRIMARY KEY (`Player_record_Id`),
  KEY `Record_Id` (`Record_Id`),
  KEY `Player_Id` (`Player_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `recruitmentinfo`
--

DROP TABLE IF EXISTS `recruitmentinfo`;
CREATE TABLE IF NOT EXISTS `recruitmentinfo` (
  `Recruit_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '招募資訊 ID',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `intro` text DEFAULT NULL COMMENT '女棒或球隊招生簡介',
  `recruitment_info` text DEFAULT NULL COMMENT '招生對象、練習時間等細節',
  `contact_info` varchar(200) DEFAULT NULL COMMENT '球經聯繫方式或社群連結',
  PRIMARY KEY (`Recruit_Id`),
  KEY `Team_Id` (`Team_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `team`
--

DROP TABLE IF EXISTS `team`;
CREATE TABLE IF NOT EXISTS `team` (
  `team_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '球隊唯一識別碼',
  `team_name` varchar(50) NOT NULL COMMENT '球隊名稱',
  `team_type` varchar(20) NOT NULL COMMENT '隊伍類型：Men, Woman, OB',
  PRIMARY KEY (`team_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `teamhistory`
--

DROP TABLE IF EXISTS `teamhistory`;
CREATE TABLE IF NOT EXISTS `teamhistory` (
  `History_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '紀錄識別碼',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `title` varchar(100) NOT NULL COMMENT '標題',
  `content` text DEFAULT NULL COMMENT '詳細簡介內容',
  `start_year` int(11) DEFAULT NULL COMMENT '起始年份',
  PRIMARY KEY (`History_Id`),
  KEY `Team_Id` (`Team_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `video`
--

DROP TABLE IF EXISTS `video`;
CREATE TABLE IF NOT EXISTS `video` (
  `Video_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '影片唯一識別碼',
  `Team_Id` int(11) DEFAULT NULL COMMENT '關聯 Team.team_Id',
  `title` varchar(255) DEFAULT NULL COMMENT '影片標題',
  `description` text DEFAULT NULL COMMENT '描述',
  `url` varchar(255) DEFAULT NULL COMMENT 'YouTube 影片連結',
  `date` date DEFAULT NULL COMMENT '日期',
  `category` varchar(100) DEFAULT NULL COMMENT '分類',
  PRIMARY KEY (`Video_id`),
  KEY `fk_video_team` (`Team_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `ai_analysis`
--
ALTER TABLE `ai_analysis`
  ADD CONSTRAINT `ai_analysis_ibfk_1` FOREIGN KEY (`Game_Id`) REFERENCES `game` (`Game_id`);

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
