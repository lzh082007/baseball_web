-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for osx10.10 (x86_64)
--
-- Host: localhost    Database: baseball_web
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `contact_us`
--

DROP TABLE IF EXISTS `contact_us`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_us` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `icon_class` varchar(50) NOT NULL,
  `content_text` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_us`
--

LOCK TABLES `contact_us` WRITE;
/*!40000 ALTER TABLE `contact_us` DISABLE KEYS */;
INSERT INTO `contact_us` VALUES (1,'fas fa-map-marker-alt','國立臺中科技大學 體育中心',NULL),(2,'fas fa-envelope','nutc_baseball@edu.tw',NULL),(3,'fas fa-phone','04-2219-XXXX',NULL),(5,'fab fa-instagram-square','instagram','https://www.instagram.com/nutc_baseball?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==');
/*!40000 ALTER TABLE `contact_us` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form`
--

DROP TABLE IF EXISTS `form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form` (
  `form_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '申請單 ID',
  `team_id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `form_name` varchar(50) NOT NULL COMMENT '申請人姓名',
  `form_gender` varchar(10) DEFAULT NULL COMMENT '性別',
  `form_age` int(11) DEFAULT NULL COMMENT '年齡',
  `form_education` varchar(50) DEFAULT NULL COMMENT '學制 (五專/四技等)',
  `form_level` varchar(50) DEFAULT NULL COMMENT '棒球程度 (初學者/有基礎)',
  `form_position` varchar(255) DEFAULT NULL,
  `form_motive` text DEFAULT NULL COMMENT '加入動機',
  `form_contact` varchar(255) DEFAULT NULL COMMENT '聯絡資訊',
  PRIMARY KEY (`form_id`),
  KEY `team_id` (`team_id`),
  CONSTRAINT `form_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `team` (`team_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form`
--

LOCK TABLES `form` WRITE;
/*!40000 ALTER TABLE `form` DISABLE KEYS */;
/*!40000 ALTER TABLE `form` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `game`
--

DROP TABLE IF EXISTS `game`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game` (
  `Game_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '比賽識別碼',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `game_date` date DEFAULT NULL COMMENT '比賽日期',
  `game_time` time DEFAULT NULL COMMENT '比賽時間 (如: 12:30)',
  `location` varchar(100) DEFAULT NULL COMMENT '比賽地點',
  `opponent` varchar(100) DEFAULT NULL COMMENT '對手學校名稱',
  `result` varchar(10) DEFAULT NULL COMMENT '比賽結果 (如：14 vs 12 勝)',
  PRIMARY KEY (`Game_id`),
  KEY `Team_Id` (`Team_Id`),
  CONSTRAINT `game_ibfk_1` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game`
--

LOCK TABLES `game` WRITE;
/*!40000 ALTER TABLE `game` DISABLE KEYS */;
INSERT INTO `game` VALUES (1,1,'2026-05-13','05:23:00','地點','對手','2:0 勝'),(2,1,'2026-05-12','12:22:00','地點','對手','比分 勝'),(3,2,'2026-05-21','02:30:00','洲際','台大','10;2 勝');
/*!40000 ALTER TABLE `game` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gamerecord`
--

DROP TABLE IF EXISTS `gamerecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gamerecord` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '檔案紀錄 ID',
  `Game_Id` int(11) NOT NULL COMMENT '關聯 Game.game_id',
  `mId` int(11) NOT NULL COMMENT '關聯 Member.mId (上傳者)',
  `record_file` varchar(255) DEFAULT NULL COMMENT '紀錄表檔案路徑 (PDF/JPG)',
  `created_at` datetime DEFAULT NULL COMMENT '檔案上傳時間',
  PRIMARY KEY (`record_id`),
  KEY `Game_Id` (`Game_Id`),
  KEY `mId` (`mId`),
  CONSTRAINT `gamerecord_ibfk_1` FOREIGN KEY (`Game_Id`) REFERENCES `game` (`Game_id`),
  CONSTRAINT `gamerecord_ibfk_2` FOREIGN KEY (`mId`) REFERENCES `member` (`mId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gamerecord`
--

LOCK TABLES `gamerecord` WRITE;
/*!40000 ALTER TABLE `gamerecord` DISABLE KEYS */;
/*!40000 ALTER TABLE `gamerecord` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member`
--

DROP TABLE IF EXISTS `member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member` (
  `mId` int(11) NOT NULL AUTO_INCREMENT COMMENT '會員唯一識別碼',
  `account` varchar(50) NOT NULL COMMENT '登入帳號',
  `password` varchar(255) NOT NULL COMMENT '加密後的密碼',
  `name` varchar(50) NOT NULL COMMENT '使用者姓名',
  `role` enum('fan','player','admin') NOT NULL COMMENT '權限等級',
  `status` enum('pending','active') NOT NULL COMMENT '審核狀態',
  `created_at` datetime NOT NULL COMMENT '帳號註冊時間',
  PRIMARY KEY (`mId`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member`
--

LOCK TABLES `member` WRITE;
/*!40000 ALTER TABLE `member` DISABLE KEYS */;
INSERT INTO `member` VALUES (1,'admin','admin','管理者(Admin)','admin','active','2026-05-13 11:00:08'),(2,'user1','123','管理者(User1)','admin','active','2026-05-13 11:00:08'),(4,'Jeff','jeff','劉詠傑','player','active','2026-05-13 11:09:37'),(5,'ting','j11995665','黃郁婷','admin','active','2026-05-14 05:39:35');
/*!40000 ALTER TABLE `member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `news_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '消息識別碼',
  `title` varchar(200) NOT NULL COMMENT '消息標題',
  `content` text NOT NULL COMMENT '消息內容',
  `created_at` datetime DEFAULT current_timestamp() COMMENT '發布時間',
  `link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`news_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (6,'測試','5/12','2026-05-13 23:15:09',NULL),(7,'一般組全國賽 SSU新聞文','114學年度UBL大專棒球聯賽一般組全國賽進入小組預賽最後一日，國立高雄科技大學帶著一勝一敗戰績，在立德棒球場迎戰國立臺中科技大學。兩隊仍保有晉級機會，此役形同背水一戰。高科靠著許少瑄主投5局飆出9次三振穩住戰局，並在二局單局攻下5分完成逆轉，終場以9:2五局扣倒中科，續命保住晉級希望。\r\n中科開賽即先聲奪人。一局上林碁晟敲出內野安打展開攻勢，王駿丞補上二安攻佔得點圈，隨後透過野選與對手失誤跑回2分，取得2:0領先。\r\n二局下，高科黃柏叡、林毓珩、李毓恆接連敲安串聯攻勢，施宥廷把握得點圈機會，一棒掃出左外野方向三壘安打，帶有3分打點，單局灌進5分完成逆轉，將比分改寫為5:2。\r\n四局下高科再添保險分。施宥廷與林恩宇連續二壘安打擴大差距。五局下攻勢持續延燒，黃柏叡、林毓珩、李毓恆再度串聯安打送回2分，代打張鈞展擊出二壘方向滾地球，壘上跑者趁勢衝回本壘得分，比數拉開至9:2，提前結束比賽。\r\n高科施宥廷2支2，含一支3分打點三安與一支二安，成為高科逆轉關鍵火力。攝／許靜玟\r\n高科施宥廷2支2，含一支3分打點三安與一支二安，成為高科逆轉關鍵火力。攝／許靜玟\r\n此役高科先發投手許少瑄展現壓制力，主投5局僅用62球，被敲兩支安打，失1分自責分，送出9次三振，成功封鎖中科後段反攻氣勢，賽後防禦率1.80，收下本場勝投。\r\n許少瑄表示，此役是自己在本屆賽事中首度登板，「就是順順丟，把握機會。」球隊曾停止運作一年，他坦言球隊人數不多，「我們就是一場一場打，把每一場都當最後一場。」\r\n中科隊長劉詠傑則表示，抽籤出爐時便知道本組強度高，「但我們不希望因為對手是誰，就改變自己的打法，我們就是打自己的球。」本屆是劉詠傑第四次參加大專棒球聯賽，他指出球隊四年都晉級全國賽，曾闖進16強，今年卻止步小組賽，難免遺憾。\r\n「這三場真的很不簡單，辛苦大家了。」劉詠傑說道。身為隊長，他語氣堅定，「我相信我們不只是這樣，希望學弟們明年可以準備得更好，把成績再往上推。」\r\n隨著小組賽落幕，高科在關鍵戰拿下勝利，保住晉級希望，將於明(5)日迎戰東海大學，力拚16強。\r\n ','2026-05-14 09:19:56','https://www.ssu.org.tw/News/Detail/3b9b26c7-1d85-4935-8a4a-c99fd658d5e8'),(8,'測試','邱偉宸是','2026-05-15 09:34:17',NULL),(9,'測試','亮澤紅柿','2026-05-15 09:34:35',NULL);
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ob`
--

DROP TABLE IF EXISTS `ob`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ob` (
  `Ob_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '校友 ID',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `OB_name` varchar(50) NOT NULL COMMENT '畢業學長姐姓名',
  `graduation_year` int(11) DEFAULT NULL COMMENT '畢業年度',
  `status` varchar(100) DEFAULT NULL COMMENT '畢業後現況或豐功偉業',
  `image_path` varchar(255) DEFAULT NULL COMMENT 'OB照片路徑',
  PRIMARY KEY (`Ob_id`),
  KEY `Team_Id` (`Team_Id`),
  CONSTRAINT `ob_ibfk_1` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ob`
--

LOCK TABLES `ob` WRITE;
/*!40000 ALTER TABLE `ob` DISABLE KEYS */;
INSERT INTO `ob` VALUES (4,1,'蔡承庭',113,'在中興大學當魔鷹',NULL),(5,1,'徐崇舜',114,'在馬祖報效國家',NULL),(6,1,'范光磊',114,NULL,NULL),(7,1,'游安田',114,'休學超廢，沒有啦哈哈哈',NULL);
/*!40000 ALTER TABLE `ob` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `player`
--

DROP TABLE IF EXISTS `player`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player` (
  `Player_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '球員識別碼',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `mId` int(11) DEFAULT NULL COMMENT '關聯 Member.mId (僅限本校球員，可為 NULL)',
  `Player_Name` varchar(50) NOT NULL COMMENT '球員姓名',
  `jersey_number` varchar(10) DEFAULT NULL COMMENT '背號 (如: 18, 93)',
  `position` varchar(255) DEFAULT NULL,
  `height` int(11) DEFAULT NULL COMMENT '身高 (cm)',
  `weight` int(11) DEFAULT NULL COMMENT '體重 (kg)',
  `pitching_speed` int(11) DEFAULT NULL COMMENT '球速 (km/h)',
  `image_path` varchar(255) DEFAULT NULL COMMENT '球員照片檔案路徑',
  PRIMARY KEY (`Player_id`),
  KEY `Team_Id` (`Team_Id`),
  KEY `mId` (`mId`),
  CONSTRAINT `player_ibfk_1` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`),
  CONSTRAINT `player_ibfk_2` FOREIGN KEY (`mId`) REFERENCES `member` (`mId`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player`
--

LOCK TABLES `player` WRITE;
/*!40000 ALTER TABLE `player` DISABLE KEYS */;
INSERT INTO `player` VALUES (1,1,4,'劉詠傑','21','投手,內野手',180,79,127,'uploads/players/1778663574_IMG_3109.JPG'),(9,1,5,'黃郁婷','18','投手,內野手',160,44,160,NULL);
/*!40000 ALTER TABLE `player` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `player_game_details`
--

DROP TABLE IF EXISTS `player_game_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_game_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `pa_count` int(11) DEFAULT 0,
  `pa_results` text DEFAULT NULL,
  `pitches` int(11) DEFAULT 0,
  `innings` varchar(10) DEFAULT '0',
  `strikeouts` int(11) DEFAULT 0,
  `walks` int(11) DEFAULT 0,
  `earned_runs` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  KEY `player_id` (`player_id`),
  CONSTRAINT `player_game_details_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`Game_id`) ON DELETE CASCADE,
  CONSTRAINT `player_game_details_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `player` (`Player_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_game_details`
--

LOCK TABLES `player_game_details` WRITE;
/*!40000 ALTER TABLE `player_game_details` DISABLE KEYS */;
INSERT INTO `player_game_details` VALUES (1,1,1,1,'HR',4,'5',15,0,0),(2,1,9,4,'2B',60,'5',5,1,2);
/*!40000 ALTER TABLE `player_game_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `playerrecord`
--

DROP TABLE IF EXISTS `playerrecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `playerrecord` (
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
  KEY `Player_Id` (`Player_Id`),
  CONSTRAINT `playerrecord_ibfk_1` FOREIGN KEY (`Record_Id`) REFERENCES `gamerecord` (`record_id`),
  CONSTRAINT `playerrecord_ibfk_2` FOREIGN KEY (`Player_Id`) REFERENCES `player` (`Player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `playerrecord`
--

LOCK TABLES `playerrecord` WRITE;
/*!40000 ALTER TABLE `playerrecord` DISABLE KEYS */;
/*!40000 ALTER TABLE `playerrecord` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recruitmentinfo`
--

DROP TABLE IF EXISTS `recruitmentinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recruitmentinfo` (
  `Recruit_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '招募資訊 ID',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `intro` text DEFAULT NULL COMMENT '女棒或球隊招生簡介',
  `recruitment_info` text DEFAULT NULL COMMENT '招生對象、練習時間等細節',
  `contact_info` varchar(200) DEFAULT NULL COMMENT '球經聯繫方式或社群連結',
  PRIMARY KEY (`Recruit_Id`),
  KEY `Team_Id` (`Team_Id`),
  CONSTRAINT `recruitmentinfo_ibfk_1` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recruitmentinfo`
--

LOCK TABLES `recruitmentinfo` WRITE;
/*!40000 ALTER TABLE `recruitmentinfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `recruitmentinfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team`
--

DROP TABLE IF EXISTS `team`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `team` (
  `team_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '球隊唯一識別碼',
  `team_name` varchar(50) NOT NULL COMMENT '球隊名稱',
  `team_type` varchar(20) NOT NULL COMMENT '隊伍類型：Men, Woman, OB',
  PRIMARY KEY (`team_Id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team`
--

LOCK TABLES `team` WRITE;
/*!40000 ALTER TABLE `team` DISABLE KEYS */;
INSERT INTO `team` VALUES (1,'中科大男棒','Men'),(2,'中科大女棒','Woman'),(3,'中科大OB','OB');
/*!40000 ALTER TABLE `team` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teamhistory`
--

DROP TABLE IF EXISTS `teamhistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teamhistory` (
  `History_Id` int(11) NOT NULL AUTO_INCREMENT COMMENT '紀錄識別碼',
  `Team_Id` int(11) NOT NULL COMMENT '關聯 Team.team_Id',
  `title` varchar(100) NOT NULL COMMENT '標題',
  `content` text DEFAULT NULL COMMENT '詳細簡介內容',
  `start_year` int(11) DEFAULT NULL COMMENT '起始年份',
  `month` int(2) DEFAULT NULL,
  PRIMARY KEY (`History_Id`),
  KEY `Team_Id` (`Team_Id`),
  CONSTRAINT `teamhistory_ibfk_1` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teamhistory`
--

LOCK TABLES `teamhistory` WRITE;
/*!40000 ALTER TABLE `teamhistory` DISABLE KEYS */;
INSERT INTO `teamhistory` VALUES (1,1,'2012 球隊創立','中科大棒球隊於 2012 年正式成立，由一群熱愛棒球的同學共同創隊，從零開始打造屬於中科大的棒球文化。',2012,NULL),(5,1,'劉詠傑加入球隊','內容描述（讚）',2022,9),(6,1,'做了這個網頁','內容描述',2026,2);
/*!40000 ALTER TABLE `teamhistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video`
--

DROP TABLE IF EXISTS `video`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video` (
  `Video_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '影片唯一識別碼',
  `Team_Id` int(11) DEFAULT NULL COMMENT '關聯 Team.team_Id',
  `title` varchar(255) DEFAULT NULL COMMENT '影片標題',
  `description` text DEFAULT NULL COMMENT '描述',
  `url` varchar(255) DEFAULT NULL COMMENT 'YouTube 影片連結',
  `date` date DEFAULT NULL COMMENT '日期',
  `category` varchar(100) DEFAULT NULL COMMENT '分類',
  PRIMARY KEY (`Video_id`),
  KEY `fk_video_team` (`Team_Id`),
  CONSTRAINT `fk_video_team` FOREIGN KEY (`Team_Id`) REFERENCES `team` (`team_Id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video`
--

LOCK TABLES `video` WRITE;
/*!40000 ALTER TABLE `video` DISABLE KEYS */;
INSERT INTO `video` VALUES (1,NULL,'114大專盃排名賽vs臺灣體大','114大專盃排名賽vs臺灣體大','https://youtube.com/playlist?list=PL7QoN_5StDVPwuKoIFjHypu3IHfo4k4ie&si=CJm79C5FZGOyefZh','2025-12-14','比賽紀錄'),(2,NULL,'114大專盃預賽vs中興大學','114大專盃預賽vs中興大學','https://youtube.com/playlist?list=PL7QoN_5StDVMiLCn692J_hSu8cPeBaqX4&si=Pnyd-AnEKYJTlT1R','2025-12-02','比賽紀錄');
/*!40000 ALTER TABLE `video` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-21 11:08:33
