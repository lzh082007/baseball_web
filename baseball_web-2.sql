-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： localhost
-- 產生時間： 2026 年 05 月 13 日 11:42
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
  `content_text` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `contact_us`:
--

--
-- 傾印資料表的資料 `contact_us`
--

INSERT INTO `contact_us` (`id`, `icon_class`, `content_text`) VALUES(1, 'fas fa-map-marker-alt', '國立臺中科技大學 體育中心');
INSERT INTO `contact_us` (`id`, `icon_class`, `content_text`) VALUES(2, 'fas fa-envelope', 'nutc_baseball@edu.tw');
INSERT INTO `contact_us` (`id`, `icon_class`, `content_text`) VALUES(3, 'fas fa-phone', '04-2219-XXXX');
INSERT INTO `contact_us` (`id`, `icon_class`, `content_text`) VALUES(5, 'fab fa-instagram-square', 'instagram');

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
  `form_position` varchar(50) DEFAULT NULL COMMENT '守備位置 (可複選，如: 投手,外野手)',
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
  `opponent` varchar(100) DEFAULT NULL COMMENT '對手學校名稱',
  `result` varchar(10) DEFAULT NULL COMMENT '比賽結果 (如：14 vs 12 勝)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `game`:
--   `Team_Id`
--       `team` -> `team_Id`
--

--
-- 傾印資料表的資料 `game`
--

INSERT INTO `game` (`Game_id`, `Team_Id`, `game_date`, `game_time`, `location`, `opponent`, `result`) VALUES(1, 1, '2026-05-13', '05:23:00', '地點', '對手', '比分 勝');
INSERT INTO `game` (`Game_id`, `Team_Id`, `game_date`, `game_time`, `location`, `opponent`, `result`) VALUES(2, 1, '2026-05-12', '12:22:00', '地點', '對手', '比分 勝');

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
-- 資料表結構 `member`
--

DROP TABLE IF EXISTS `member`;
CREATE TABLE `member` (
  `mId` int(11) NOT NULL COMMENT '會員唯一識別碼',
  `account` varchar(50) NOT NULL COMMENT '登入帳號',
  `password` varchar(255) NOT NULL COMMENT '加密後的密碼',
  `name` varchar(50) NOT NULL COMMENT '使用者姓名',
  `role` enum('fan','player','admin') NOT NULL COMMENT '權限等級',
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

-- --------------------------------------------------------

--
-- 資料表結構 `news`
--

DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `news_id` int(11) NOT NULL COMMENT '消息識別碼',
  `title` varchar(200) NOT NULL COMMENT '消息標題',
  `content` text NOT NULL COMMENT '消息內容',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '發布時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `news`:
--

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
  `position` varchar(20) DEFAULT NULL COMMENT '守位',
  `height` int(11) DEFAULT NULL COMMENT '身高 (cm)',
  `weight` int(11) DEFAULT NULL COMMENT '體重 (kg)',
  `pitching_speed` int(11) DEFAULT NULL COMMENT '球速 (km/h)',
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

INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `pitching_speed`, `image_path`) VALUES(8, 1, 4, '劉詠傑', '21', '投手', 180, 80, 127, 'uploads/players/1778663574_IMG_3109.JPG');

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
  `start_year` int(11) DEFAULT NULL COMMENT '起始年份'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `teamhistory`:
--   `Team_Id`
--       `team` -> `team_Id`
--

--
-- 傾印資料表的資料 `teamhistory`
--

INSERT INTO `teamhistory` (`History_Id`, `Team_Id`, `title`, `content`, `start_year`) VALUES(1, 1, '2012 球隊創立', '中科大棒球隊於 2012 年正式成立，由一群熱愛棒球的同學共同創隊，從零開始打造屬於中科大的棒球文化。', 2012);

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
  MODIFY `form_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '申請單 ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `game`
--
ALTER TABLE `game`
  MODIFY `Game_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '比賽識別碼', AUTO_INCREMENT=3;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `gamerecord`
--
ALTER TABLE `gamerecord`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '檔案紀錄 ID';

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `member`
--
ALTER TABLE `member`
  MODIFY `mId` int(11) NOT NULL AUTO_INCREMENT COMMENT '會員唯一識別碼', AUTO_INCREMENT=5;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `news`
--
ALTER TABLE `news`
  MODIFY `news_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '消息識別碼', AUTO_INCREMENT=6;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `ob`
--
ALTER TABLE `ob`
  MODIFY `Ob_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '校友 ID', AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `player`
--
ALTER TABLE `player`
  MODIFY `Player_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '球員識別碼', AUTO_INCREMENT=9;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `playerrecord`
--
ALTER TABLE `playerrecord`
  MODIFY `Player_record_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '數據 ID';

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
  MODIFY `History_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '紀錄識別碼', AUTO_INCREMENT=5;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `video`
--
ALTER TABLE `video`
  MODIFY `Video_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '影片唯一識別碼';

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
