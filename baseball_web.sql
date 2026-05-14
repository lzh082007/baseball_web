-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： localhost
-- 產生時間： 2026 年 05 月 14 日 11:50
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
INSERT INTO `member` (`mId`, `account`, `password`, `name`, `role`, `status`, `created_at`) VALUES(5, 'ting', 'j11995665', '黃郁婷', 'admin', 'active', '2026-05-14 05:39:35');

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

INSERT INTO `player` (`Player_id`, `Team_Id`, `mId`, `Player_Name`, `jersey_number`, `position`, `height`, `weight`, `pitching_speed`, `image_path`) VALUES(8, 1, 4, '劉詠傑', '21', '投手,內野手', 180, 79, 127, 'uploads/players/1778663574_IMG_3109.JPG');

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
  MODIFY `mId` int(11) NOT NULL AUTO_INCREMENT COMMENT '會員唯一識別碼', AUTO_INCREMENT=6;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `news`
--
ALTER TABLE `news`
  MODIFY `news_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '消息識別碼', AUTO_INCREMENT=8;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `ob`
--
ALTER TABLE `ob`
  MODIFY `Ob_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '校友 ID', AUTO_INCREMENT=8;

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
