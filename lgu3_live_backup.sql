-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: lgu3_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,'Public Health Advisory Dengue Right Now','Public Health Advisory Dengue Right Now','The Philippine Red Cross (PRC) warned the public of dengue fever risk and gave advice on how it can be prevented as the country observes Dengue Awareness Month in June.\r\n\r\nThe PRC Health Services explained that dengue fever is a mosquito-borne infectious disease that causes a severe flu-like illness. It can progress into two life-threatening conditions: dengue hemorrhagic fever and dengue shock syndrome. The virus that causes dengue is passed on to humans from animals through the bite of an infected mosquito.\r\n\r\nThe symptoms are sudden onset of high fever, which may last from 2 to 7 days; joint and muscle pain and pain behind the eyes; weakness; skin rashes; nose bleeding when fever starts to subside; abdominal pain; vomiting of coffee-colored matter; dark-colored stools; and difficulty of breathing.\r\n\r\nPRC reminds the public of the 4 Ss to prevent dengue:\r\n\r\nSearch and Destroy: Cover water drums and pails; Replace water in flower vases once a week; Clean gutters of leaves and debris; Collect and dispose of all unusable tin cans, jars, bottles, and other items that can collect and hold water.\r\n\r\nSelf-Protection Measures: Wear long pants and a long-sleeved shirt; Use mosquito repellent every day.\r\n\r\nSeek Early Consultation: Consult the doctors immediately if fever persists after 2 days and rashes appear.\r\n\r\nSay Yes to Fogging when there is an impending outbreak or a hotspot.\r\n\r\nThe PRC Health Services gives the following advice: If medicine for fever will be given, do not give aspirin. Ensure a person suspected of having dengue is well hydrated. If fever or symptoms persist for 2 or more days, bring the patient to the nearest hospital.\r\n\r\n','uploads/announcements/1769960406_lamok.jpg','2026-02-01 15:40:06'),(2,'DSWD Programs and Services','DSWD Programs and Services','DSWD Programs and Services\r\nThe DSWD offers a variety of programs and services to support Filipinos in need. Here are some of the key initiatives:\r\nPantawid Pamilyang Pilipino Program (4Ps): Provides cash grants to the poorest families to improve health, nutrition, and education for children aged 0-18. \r\n1\r\nKALAHI CIDSS-NCDDP: A comprehensive and integrated delivery of social services aimed at improving the lives of local communities. \r\n1\r\nSupplementary Feeding Program: Provides nutritious meals to malnourished children. \r\n1\r\nNational Feeding Program: Offers hot meals to hungry children in public schools. \r\n1\r\nSocial Pension for Indigent Senior Citizens: Provides monthly stipends to indigent senior citizens aged 60 and above. \r\n2\r\nAICS Program: Offers medical assistance, burial, transportation, education, food, or financial assistance for individuals in crisis situations. \r\n1\r\n\r\nThese programs are part of the DSWD\'s broader mission to empower the poor, build their capacities, and orchestrate efforts towards inclusive growth and development. For more information on these programs and how to apply, visit the DSWD\'s official website or contact your nearest DSWD field office','uploads/announcements/1769965975_4PS.webp','2026-02-01 17:12:55'),(3,'Employee training programs are essential for enhancing skills, boosting productivity, and fostering a culture of continuous improvement within organizations.','Employee training programs are essential for enhancing skills, boosting productivity, and fostering ','Employee training programs are essential for enhancing skills, boosting productivity, and fostering a culture of continuous improvement within organizations.\r\nImportance of Employee Training\r\nSkill Development: Training helps employees acquire new skills and knowledge, enabling them to perform their jobs more effectively and adapt to changing job requirements. \r\n2\r\nIncreased Productivity: Well-trained employees are more efficient and confident in their roles, leading to improved overall productivity for the organization. \r\n2\r\nEmployee Retention: Investing in training demonstrates that the organization values its employees, which can enhance job satisfaction and reduce turnover rates. \r\n2\r\nCompliance and Safety: Training programs ensure that employees are aware of safety protocols and compliance regulations, reducing the risk of accidents and legal issues. \r\n2\r\nOrganizational Growth: Continuous training aligns employee development with organizational goals, fostering innovation and competitiveness in the market. \r\n2\r\n\r\n\r\n5 Sources\r\nTypes of Employee Training Programs\r\nOrientation Training: Introduces new hires to the company culture, policies, and their specific roles, helping them integrate smoothly into the organization. \r\n2\r\nOn-the-Job Training: Provides hands-on experience under the guidance of experienced colleagues, allowing employees to learn in real work situations. \r\n2\r\nMentoring and Coaching: Involves pairing employees with mentors who can provide guidance, support, and feedback to enhance their professional development. \r\n2\r\nTechnical Skills Training: Focuses on developing specific technical skills required for particular roles, such as software training or machinery operation. \r\n2\r\nSoft Skills Training: Enhances interpersonal skills, communication, and leadership abilities, which are crucial for career advancement and team collaboration. \r\n2\r\nCompliance Training: Ensures that employees understand legal and regulatory requirements relevant to their roles, such as workplace safety or data protection. \r\n2\r\nLeadership Development: Prepares employees for leadership roles by developing their strategic thinking, decision-making, and management skills. \r\n2\r\n\r\n\r\n5 Sources\r\nConclusion\r\nImplementing effective employee training programs is a strategic investment that benefits both employees and the organization. By fostering a culture of learning and development, companies can enhance employee performance, satisfaction, and retention, ultimately driving long-term success. Organizations should assess their specific needs and tailor training programs accordingly to maximize their impact.','uploads/announcements/1769967383_Employee-Training.png','2026-02-01 17:36:23'),(4,'test','test','test','uploads/announcements/1769969366_Screenshot 2026-02-02 020202.png','2026-02-01 18:09:26');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_events`
--

DROP TABLE IF EXISTS `calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `creator_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT '09:00:00',
  `type` enum('task','training','work','meeting') DEFAULT 'task',
  `target_user_id` int(11) DEFAULT NULL,
  `status` enum('pending','joined','declined') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `creator_id` (`creator_id`),
  KEY `target_user_id` (`target_user_id`),
  CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`),
  CONSTRAINT `calendar_events_ibfk_2` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_events`
--

LOCK TABLES `calendar_events` WRITE;
/*!40000 ALTER TABLE `calendar_events` DISABLE KEYS */;
INSERT INTO `calendar_events` VALUES (1,1,'Training ','Come to join','2026-02-01','09:00:00','training',3,'joined','2026-01-29 23:07:09'),(8,1,'test','hi join','2026-02-03','09:00:00','training',NULL,'pending','2026-01-30 13:06:22'),(9,1,'For baby checkup','test 2','2026-03-20','09:00:00','training',NULL,'pending','2026-01-31 15:08:11');
/*!40000 ALTER TABLE `calendar_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_tags`
--

DROP TABLE IF EXISTS `event_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','joined','declined') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `event_tags_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_tags_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_tags`
--

LOCK TABLES `event_tags` WRITE;
/*!40000 ALTER TABLE `event_tags` DISABLE KEYS */;
INSERT INTO `event_tags` VALUES (1,1,3,'joined','2026-01-29 23:28:08'),(10,8,3,'joined','2026-01-30 13:06:22'),(11,8,2,'joined','2026-01-30 13:06:22'),(12,9,3,'pending','2026-01-31 15:08:11'),(13,9,2,'joined','2026-01-31 15:08:11');
/*!40000 ALTER TABLE `event_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facilities`
--

DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facilities`
--

LOCK TABLES `facilities` WRITE;
/*!40000 ALTER TABLE `facilities` DISABLE KEYS */;
INSERT INTO `facilities` VALUES (1,'Sanville Covered Court w/ Multipurpose BLDG','Brgy. Culiat','2026-02-01 17:16:50'),(2,'Pael Multipurpose BLDG/ Burial Site','Brgy. Culiat','2026-02-01 17:16:50'),(3,'Culiat Highschool','Brgy. Culiat','2026-02-01 17:16:50'),(4,'Cassanova Multipurpose Building','Brgy. Culiat','2026-02-01 17:16:50'),(5,'Bernardo Court','Brgy. Culiat','2026-02-01 17:16:50');
/*!40000 ALTER TABLE `facilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facility_reservations`
--

DROP TABLE IF EXISTS `facility_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facility_reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `facility_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reservation_date` date NOT NULL,
  `status` enum('Approved','Pending','Denied','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `facility_id` (`facility_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `facility_reservations_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `facility_reservations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility_reservations`
--

LOCK TABLES `facility_reservations` WRITE;
/*!40000 ALTER TABLE `facility_reservations` DISABLE KEYS */;
INSERT INTO `facility_reservations` VALUES (1,1,1,'2025-12-09','Cancelled','2026-02-01 17:16:50'),(2,5,3,'2025-09-21','Approved','2026-02-01 17:16:50'),(3,1,2,'2025-11-29','Cancelled','2026-02-01 17:16:50'),(4,1,9,'2025-12-23','Pending','2026-02-01 17:16:50'),(5,2,14,'2025-11-21','Approved','2026-02-01 17:16:50'),(6,5,3,'2025-10-31','Approved','2026-02-01 17:16:50'),(7,3,9,'2025-12-17','Approved','2026-02-01 17:16:50'),(8,4,9,'2025-11-25','Pending','2026-02-01 17:16:50'),(9,3,2,'2025-10-01','Pending','2026-02-01 17:16:50'),(10,2,1,'2025-08-18','Denied','2026-02-01 17:16:50'),(11,5,2,'2025-11-02','Cancelled','2026-02-01 17:16:50'),(12,1,14,'2026-01-09','Pending','2026-02-01 17:16:50'),(13,1,5,'2025-10-10','Denied','2026-02-01 17:16:50'),(14,1,3,'2025-10-20','Approved','2026-02-01 17:16:50'),(15,1,14,'2025-09-24','Denied','2026-02-01 17:16:50'),(16,5,2,'2025-11-13','Cancelled','2026-02-01 17:16:50'),(17,4,1,'2025-08-23','Approved','2026-02-01 17:16:50'),(18,5,2,'2025-11-30','Cancelled','2026-02-01 17:16:50'),(19,3,3,'2026-01-06','Denied','2026-02-01 17:16:50'),(20,4,9,'2025-08-24','Denied','2026-02-01 17:16:50'),(21,4,9,'2025-10-19','Denied','2026-02-01 17:16:50'),(22,1,3,'2025-12-08','Approved','2026-02-01 17:16:50'),(23,2,5,'2025-12-19','Denied','2026-02-01 17:16:50'),(24,2,14,'2026-01-06','Approved','2026-02-01 17:16:50'),(25,1,3,'2026-01-22','Denied','2026-02-01 17:16:50'),(26,3,3,'2025-12-15','Pending','2026-02-01 17:16:50'),(27,2,1,'2025-11-05','Denied','2026-02-01 17:16:50'),(28,4,1,'2025-10-10','Approved','2026-02-01 17:16:50'),(29,1,14,'2025-10-08','Pending','2026-02-01 17:16:50'),(30,3,1,'2026-01-12','Approved','2026-02-01 17:16:50'),(31,2,9,'2026-01-21','Denied','2026-02-01 17:16:50'),(32,4,3,'2026-01-14','Denied','2026-02-01 17:16:50'),(33,3,5,'2025-09-30','Approved','2026-02-01 17:16:50'),(34,1,1,'2025-08-21','Approved','2026-02-01 17:16:50'),(35,2,3,'2025-12-05','Pending','2026-02-01 17:16:50'),(36,4,2,'2025-08-11','Approved','2026-02-01 17:16:50'),(37,3,1,'2025-08-16','Cancelled','2026-02-01 17:16:50'),(38,1,5,'2025-09-12','Denied','2026-02-01 17:16:50'),(39,2,1,'2025-10-01','Cancelled','2026-02-01 17:16:50'),(40,2,9,'2025-11-26','Approved','2026-02-01 17:16:50'),(41,2,14,'2025-08-06','Denied','2026-02-01 17:16:50'),(42,1,5,'2025-10-20','Cancelled','2026-02-01 17:16:50'),(43,4,14,'2025-09-18','Approved','2026-02-01 17:16:50'),(44,4,3,'2025-12-22','Pending','2026-02-01 17:16:50'),(45,5,9,'2025-12-12','Cancelled','2026-02-01 17:16:50'),(46,3,9,'2025-08-25','Cancelled','2026-02-01 17:16:50'),(47,2,9,'2025-12-07','Cancelled','2026-02-01 17:16:50'),(48,1,1,'2025-12-06','Pending','2026-02-01 17:16:50'),(49,4,5,'2025-08-20','Cancelled','2026-02-01 17:16:50'),(50,3,5,'2026-01-11','Pending','2026-02-01 17:16:50');
/*!40000 ALTER TABLE `facility_reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `learning_docs`
--

DROP TABLE IF EXISTS `learning_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learning_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `learning_docs`
--

LOCK TABLES `learning_docs` WRITE;
/*!40000 ALTER TABLE `learning_docs` DISABLE KEYS */;
INSERT INTO `learning_docs` VALUES (1,'Free Learning stage 1','Agriculture','uploads/docs/1769719010_Resource 1 - Agriculture.docx','2026-01-29 20:36:50'),(2,'Learning stage 2','Health','uploads/docs/1769724305_Resource 1 - Agriculture.docx','2026-01-29 22:05:05'),(3,'mew date','Skills','uploads/docs/1769783990_Resource 1 - Agriculture.docx','2026-01-30 14:39:50'),(4,'test2','Business','uploads/docs/1769872027_Resource 1 - Agriculture.docx','2026-01-31 15:07:07');
/*!40000 ALTER TABLE `learning_docs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_schedules`
--

DROP TABLE IF EXISTS `maintenance_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `maint_id` varchar(50) NOT NULL,
  `facility` varchar(255) NOT NULL,
  `maint_type` varchar(100) NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `priority` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `status` enum('Scheduled','In Progress','Completed','Delayed') DEFAULT 'Scheduled',
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `maint_id` (`maint_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `maintenance_schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_schedules`
--

LOCK TABLES `maintenance_schedules` WRITE;
/*!40000 ALTER TABLE `maintenance_schedules` DISABLE KEYS */;
INSERT INTO `maintenance_schedules` VALUES (1,'CIMM-14','City Hall - 2nd Floor','Aircon Filter Cleaning','2026-02-19 08:00:00','2 hours','Medium','Scheduled',9,'2026-02-01 16:58:42'),(2,'CIMM-15','City Hall - Electrical Room','Electrical Panel Inspection','2026-02-19 09:00:00','3 hours','High','In Progress',9,'2026-02-01 16:58:42'),(3,'CIMM-3','Building C','Fire Alarm Inspection','2026-02-20 08:30:00','4 hours','Medium','Scheduled',9,'2026-02-01 16:58:42');
/*!40000 ALTER TABLE `maintenance_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `type` varchar(50) DEFAULT 'info',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,2,'Module Sent: Digital Marketing for SMEs','Admin has sent you the learning materials for Digital Marketing for SMEs. Check your dashboard!',1,'success','2026-01-29 20:18:38'),(11,2,'Edit Approved','Administrator has approved your name change. You have 25 minutes to update your profile.',1,'success','2026-01-29 21:25:05'),(12,3,'Report Update: Eror','Your report \'Eror\' has been marked as APPROVED by the administration.',1,'success','2026-01-29 22:04:51'),(13,3,'Module Sent: Hairdressing NCII','Admin has sent you the learning materials for Hairdressing NCII. Check your dashboard!',1,'success','2026-01-29 22:05:15'),(14,3,'New Calendar Event: Training ','You have been tagged in a new calendar event: Training  scheduled for 2026-02-01 at 09:00.',1,'info','2026-01-29 23:07:09'),(15,1,'Event Response: Joined','Ralp Renz Cruzado  has joined the event: Training ',1,'info','2026-01-29 23:14:26'),(16,3,'New Calendar Event: test','You have been tagged in a new calendar event: test scheduled for 2026-02-03 at 09:00.',1,'info','2026-01-30 13:06:22'),(17,2,'New Calendar Event: test','You have been tagged in a new calendar event: test scheduled for 2026-02-03 at 09:00.',1,'info','2026-01-30 13:06:22'),(18,1,'Event Response: Joined','Ralp Renz Cruzado  has joined the event: test',1,'info','2026-01-30 13:07:08'),(19,1,'Event Response: Joined','Sophia Celine Dion has joined the event: test',1,'info','2026-01-30 14:35:27'),(20,1,'Direct Name Edit Request','Sophia Celine Dion has requested permission to edit their profile name.',1,'warning','2026-01-30 14:36:35'),(21,2,'Edit Approved','Administrator has approved your name change. You have 25 minutes to update your profile.',1,'success','2026-01-30 14:37:15'),(22,2,'Report Update: Hi','Your report \'Hi\' has been marked as APPROVED by the administration.',1,'success','2026-01-30 14:39:08'),(23,3,'Report Received','Your report \'eror\' has been received. Our team will review it shortly.',1,'info','2026-01-30 14:40:52'),(24,1,'New Report Submitted','Ralp Renz Cruzado  has submitted a new report: \'eror\'.',1,'warning','2026-01-30 14:40:52'),(25,3,'Report Update: eror','Your report \'eror\' has been marked as APPROVED by the administration.',1,'success','2026-01-30 14:41:22'),(26,3,'Report Update: eror','Your report \'eror\' has been marked as APPROVED by the administration.',0,'success','2026-01-30 15:46:06'),(27,2,'Report Received','Your report \'Broken Street Light\' has been received. Our team will review it shortly.',1,'info','2026-01-30 16:54:37'),(28,1,'New Report Submitted','Sophia Celine Dion 2 has submitted a new report: \'Broken Street Light\'.',1,'warning','2026-01-30 16:54:37'),(29,2,'Report Update: Broken Street Light','Your report \'Broken Street Light\' has been marked as APPROVED by the administration.',1,'success','2026-01-30 16:57:46'),(30,2,'Report Update: Broken Street Light','Your report \'Broken Street Light\' has been marked as APPROVED by the administration.',1,'success','2026-01-30 16:58:23'),(31,2,'Report Update: Broken Street Light','Your report \'Broken Street Light\' has been marked as APPROVED by the administration.',1,'success','2026-01-30 17:06:44'),(32,2,'Report Received','Your report \'Goods\' has been received. Our team will review it shortly.',1,'info','2026-01-30 17:08:06'),(33,1,'New Report Submitted','Sophia Celine Dion 2 has submitted a new report: \'Goods\'.',1,'warning','2026-01-30 17:08:06'),(34,2,'Report Update: Goods','Your report \'Goods\' has been marked as APPROVED by the administration.',1,'success','2026-01-30 17:08:35'),(35,2,'Report Update: Goods','Your report \'Goods\' has been marked as APPROVED by the administration.',1,'success','2026-01-30 17:08:42'),(36,2,'Report Received','Your report \'eror\' has been received. Our team will review it shortly.',1,'info','2026-01-30 20:39:03'),(37,1,'New Report Submitted','Sophia Celine Dion 2 has submitted a new report: \'eror\'.',1,'warning','2026-01-30 20:39:03'),(38,2,'Report Update: eror','Your report \'eror\' has been marked as APPROVED by the administration.',1,'success','2026-01-30 20:41:06'),(39,2,'Report Update: eror','Your report \'eror\' has been marked as APPROVED by the administration.',1,'success','2026-01-31 15:06:09'),(40,3,'New Calendar Event: For baby checkup','You have been tagged in a new calendar event: For baby checkup scheduled for 2026-03-20 at 09:00.',0,'info','2026-01-31 15:08:11'),(41,2,'New Calendar Event: For baby checkup','You have been tagged in a new calendar event: For baby checkup scheduled for 2026-03-20 at 09:00.',1,'info','2026-01-31 15:08:11'),(42,1,'Direct Name Edit Request','Sophia Celine Dion 2 has requested permission to edit their profile name.',1,'warning','2026-01-31 15:10:23'),(43,2,'Report Received','Your report \'Good work\' has been received. Our team will review it shortly.',1,'info','2026-01-31 15:10:38'),(44,1,'New Report Submitted','Sophia Celine Dion 2 has submitted a new report: \'Good work\'.',1,'warning','2026-01-31 15:10:38'),(45,1,'Event Response: Joined','Sophia Celine Dion 2 has joined the event: For baby checkup',1,'info','2026-01-31 15:10:46'),(46,2,'Report Update: Good work','Your report \'Good work\' has been marked as APPROVED by the administration.',1,'success','2026-01-31 15:13:05'),(47,2,'Edit Approved','Administrator has approved your name change. You have 25 minutes to update your profile.',1,'success','2026-01-31 15:13:17'),(48,9,'Application Sent','You have successfully applied for \'Computer Systems Servicing\'. Please wait for admin approval and materials.',1,'info','2026-01-31 15:16:59'),(49,1,'New Program Application','wilfre test has applied for \'Computer Systems Servicing\'. Review available in Applications panel.',1,'info','2026-01-31 15:16:59'),(50,9,'Application Approved: Computer Systems Servicing','Your application for Computer Systems Servicing has been approved and the material link is now available on your dashboard.',0,'success','2026-01-31 18:24:35'),(51,2,'Account Update','Your account has been Deactivated by an Administrator.',1,'info','2026-01-31 18:24:57'),(52,2,'Account Update','Your account has been Activated by an Administrator.',1,'info','2026-01-31 18:34:19'),(53,2,'Account Update','Your account has been Deactivated by an Administrator.',1,'info','2026-01-31 18:42:18'),(54,2,'Account Reactivated','Your account has been reactivated via Reference ID check. Welcome back!',1,'success','2026-01-31 18:42:47'),(55,2,'Report Received','Your report \'good work\' has been received. Our team will review it shortly.',1,'info','2026-01-31 19:30:20'),(56,1,'New Report Submitted','Sophia Celine Dion has submitted a new report: \'good work\'.',1,'warning','2026-01-31 19:30:20'),(57,2,'Report Update: good work','Your report \'good work\' has been marked as APPROVED by the administration.',1,'success','2026-01-31 19:30:48'),(58,2,'Report Received','Your report \'eror\' has been received. Our team will review it shortly.',1,'info','2026-01-31 19:32:13'),(59,1,'New Report Submitted','Sophia Celine Dion has submitted a new report: \'eror\'.',1,'warning','2026-01-31 19:32:13'),(60,2,'Report Update: eror','Your report \'eror\' has been marked as APPROVED by the administration.',1,'success','2026-01-31 19:32:40'),(61,3,'Report Received','Your report \'the system is ugly - eror not working function\' has been received. Our team will review it shortly.',0,'info','2026-01-31 20:45:49'),(62,1,'New Report Submitted','Ralp Renz Cruzado  has submitted a new report: \'the system is ugly - eror not working function\'.',1,'warning','2026-01-31 20:45:49'),(63,2,'Report Received','Your report \'Urgent complaints, broken\' has been received. Our team will review it shortly.',1,'info','2026-01-31 20:47:35'),(64,1,'New Report Submitted','Sophia Celine Dion has submitted a new report: \'Urgent complaints, broken\'.',1,'warning','2026-01-31 20:47:35'),(65,2,'Report Update: Urgent complaints, broken','Your report \'Urgent complaints, broken\' has been marked as APPROVED by the administration.',1,'success','2026-02-01 15:18:55'),(66,2,'Report Update: Urgent complaints, broken','Your report \'Urgent complaints, broken\' has been marked as APPROVED by the administration.',1,'success','2026-02-01 15:18:57'),(67,3,'Report Update: the system is ugly - eror not working function','Your report \'the system is ugly - eror not working function\' has been marked as APPROVED by the administration.',0,'success','2026-02-01 15:18:59'),(68,2,'Report Received','Your report \'eror\' has been received. Our team will review it shortly.',1,'info','2026-02-01 17:27:10'),(69,1,'New Report Submitted','Sophia Celine Dion has submitted a new report: \'eror\'.',1,'warning','2026-02-01 17:27:10');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `program_applications`
--

DROP TABLE IF EXISTS `program_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `program_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `material_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `program_applications`
--

LOCK TABLES `program_applications` WRITE;
/*!40000 ALTER TABLE `program_applications` DISABLE KEYS */;
INSERT INTO `program_applications` VALUES (1,2,5,'approved','view_material.php?app_id=1','2026-01-29 19:39:15'),(3,2,7,'approved','view_material.php?app_id=3','2026-01-29 20:18:38'),(4,3,1,'approved','view_material.php?app_id=4','2026-01-29 21:57:45'),(5,3,3,'approved','view_material.php?app_id=5','2026-01-29 21:57:51'),(6,3,6,'approved','view_material.php?app_id=6','2026-01-29 22:05:15'),(7,9,5,'approved','view_material.php?app_id=7','2026-01-31 15:16:59');
/*!40000 ALTER TABLE `program_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (1,'Basic Welding Training','Technical','Learn shield metal arc welding basics.','2026-01-29 19:07:06'),(2,'Baking & Pastry Arts','Livelihood','Start your own bakery business.','2026-01-29 19:07:06'),(3,'Call Center Agent Prep','BPO','English proficiency and customer service skills.','2026-01-29 19:07:06'),(4,'Urban Gardening 101','Agriculture','Sustainable food production at home.','2026-01-29 19:07:06'),(5,'Computer Systems Servicing','IT','Hardware repair and networking basics.','2026-01-29 19:07:06'),(6,'Hairdressing NCII','Service','Professional hair cutting and styling.','2026-01-29 19:07:06'),(7,'Digital Marketing for SMEs','Business','Promote products using social media.','2026-01-29 19:07:06');
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sentiment` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
INSERT INTO `reports` VALUES (1,2,'Hi','hi','approved','2026-01-29 18:42:59','neutral'),(2,2,'hi','please fix acces authorization is not working\n','approved','2026-01-29 21:01:20','neutral'),(3,3,'Eror','i need access to ai assistant','approved','2026-01-29 21:58:16','neutral'),(4,3,'eror','d ma open','approved','2026-01-30 14:40:52','neutral'),(5,2,'Broken Street Light','Testing','approved','2026-01-30 16:54:37','neutral'),(6,2,'Goods','Good System','approved','2026-01-30 17:08:06','positive'),(7,2,'eror','ang panget\n','approved','2026-01-30 20:39:03','neutral'),(8,2,'Good work','Excelent','approved','2026-01-31 15:10:38','neutral'),(9,2,'good work','excellent','approved','2026-01-31 19:30:20','positive'),(10,2,'eror','eror','approved','2026-01-31 19:32:13','neutral'),(11,3,'the system is ugly - eror not working function','then issue the ui is not working and the smoothness is negative ','approved','2026-01-31 20:45:49','neutral'),(12,2,'Urgent complaints, broken','system Urgent complaints','approved','2026-01-31 20:47:35','negative'),(13,2,'eror','hi','pending','2026-02-01 17:27:10','neutral');
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skill_test_stages`
--

DROP TABLE IF EXISTS `skill_test_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skill_test_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_id` int(11) DEFAULT NULL,
  `stage_number` int(11) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`),
  CONSTRAINT `skill_test_stages_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `skill_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skill_test_stages`
--

LOCK TABLES `skill_test_stages` WRITE;
/*!40000 ALTER TABLE `skill_test_stages` DISABLE KEYS */;
INSERT INTO `skill_test_stages` VALUES (1,1,1,'Introduction to Computers','Learn the basic parts of a computer and how to turn it on/off.','https://www.youtube.com/embed/dQw4w9WgXcQ'),(2,1,2,'Using the Mouse & Keyboard','Interactive guide to typing and navigating with a mouse.','https://www.youtube.com/embed/dQw4w9WgXcQ'),(3,1,3,'Browsing the Internet','How to use a web browser, search engines, and safety tips.','https://www.youtube.com/embed/dQw4w9WgXcQ'),(4,1,4,'Email Basics','Creating an email account, sending, and receiving emails.','https://www.youtube.com/embed/dQw4w9WgXcQ'),(5,1,5,'Final Assessment','Complete a quiz to verify your skills and get your certificate.','https://www.youtube.com/embed/dQw4w9WgXcQ'),(6,2,1,'Stage 1','Stage 1 Literacy','https://youtu.be/7z9poBHSPW8?list=RD7z9poBHSPW8'),(7,2,2,'Music','Musical Video','https://youtu.be/7z9poBHSPW8?list=RD7z9poBHSPW8'),(10,4,1,'stage 1','https://workspace.google.com/products/docs/','https://workspace.google.com/products/docs/'),(11,4,2,'stage 2','https://workspace.google.com/products/docs/','https://workspace.google.com/products/docs/'),(12,4,3,'stage 3','https://workspace.google.com/products/docs/','https://workspace.google.com/products/docs/');
/*!40000 ALTER TABLE `skill_test_stages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skill_tests`
--

DROP TABLE IF EXISTS `skill_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skill_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skill_tests`
--

LOCK TABLES `skill_tests` WRITE;
/*!40000 ALTER TABLE `skill_tests` DISABLE KEYS */;
INSERT INTO `skill_tests` VALUES (1,'Digital Literacy Skill Test','Master the basics of digital literacy in 5 easy stages. Free enrollment.','https://images.unsplash.com/photo-1572044162444-ad6021194360?auto=format&fit=crop&w=600&q=80','2026-01-30 15:32:36'),(2,'Literacy','Literacy Course','https://youtu.be/7z9poBHSPW8?list=RD7z9poBHSPW8','2026-01-30 17:36:34'),(4,'test 2 ','test 2','https://workspace.google.com/products/docs/','2026-01-31 15:09:05');
/*!40000 ALTER TABLE `skill_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_skill_progress`
--

DROP TABLE IF EXISTS `user_skill_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_skill_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `test_id` int(11) DEFAULT NULL,
  `current_stage` int(11) DEFAULT 1,
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `score` int(11) DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_id` (`test_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_skill_progress_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `skill_tests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_skill_progress_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_skill_progress`
--

LOCK TABLES `user_skill_progress` WRITE;
/*!40000 ALTER TABLE `user_skill_progress` DISABLE KEYS */;
INSERT INTO `user_skill_progress` VALUES (1,3,1,1,'in_progress',0,'2026-01-30 15:42:31',NULL),(2,2,1,5,'completed',100,'2026-01-30 17:19:05','2026-01-30 17:19:38'),(3,2,2,2,'completed',100,'2026-01-30 17:38:10','2026-01-30 17:38:19'),(4,5,1,5,'completed',100,'2026-01-30 18:24:52','2026-01-30 18:25:05'),(6,2,4,3,'completed',100,'2026-01-31 15:11:09','2026-01-31 15:11:24'),(7,14,1,1,'in_progress',0,'2026-01-31 21:45:31',NULL);
/*!40000 ALTER TABLE `user_skill_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_id` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `house_number` varchar(50) DEFAULT NULL,
  `valid_id_path` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `skills` text DEFAULT NULL,
  `interests` text DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `employment_status` varchar(50) DEFAULT NULL,
  `ai_analysis_result` text DEFAULT NULL,
  `edit_authorized_until` datetime DEFAULT NULL,
  `requesting_edit` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `age` int(11) DEFAULT NULL,
  `barangay` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `brgy_id_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `reference_id` (`reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'REF-99482798',NULL,NULL,NULL,NULL,'System Admin','admin@lgu3.gov',NULL,NULL,NULL,NULL,NULL,'$2y$10$bfX90/9oM36dc3XCJwuIkuddDHdEmeRqv1hiBXX7NyhcwuZ3m..1S','admin','2026-01-29 18:19:57',NULL,NULL,NULL,NULL,NULL,NULL,0,1,NULL,NULL,NULL,NULL,NULL),(2,'REF-30378547',NULL,NULL,NULL,NULL,'Sophia Celine Dion','test5@gmail.com',NULL,NULL,NULL,NULL,'uploads/1769899876_picken.jpg','$2y$10$aFHB8dUIEsgPAwQj.qN5heGCKK8N15e/S5xlkS6Awfo2iCPmAtEfi','user','2026-01-29 18:24:09','repair','computer components',NULL,'Unemployed','[{\"id\":\"5\",\"title\":\"Computer Systems Servicing\",\"category\":\"IT\",\"score\":2}]',NULL,0,1,NULL,NULL,NULL,NULL,NULL),(3,'REF-85590627',NULL,NULL,NULL,NULL,'Ralp Renz Cruzado ','ralp@gmail.com',NULL,NULL,NULL,NULL,NULL,'$2y$10$Fui4nCMGTDLdxsrhglcvhOrF./L.dTo8UO.RaFP8DXJugrQNVSoze','user','2026-01-29 21:56:02','Teacher and graduate license ','i need more idea from teacher ',NULL,'Looking for Upskilling','[]',NULL,0,1,NULL,NULL,NULL,NULL,NULL),(5,'REF-39327898',NULL,NULL,NULL,NULL,'khurt agustin','agustin01262005@gmail.com',NULL,NULL,NULL,NULL,NULL,'$2y$10$5gOmLU54BwYVsuYbJPvTkefDP74BZq7/kRhw.vteLw0GAuqbgwgkO','user','2026-01-30 18:23:57','Cooking','Cooking',NULL,'Unemployed','[]',NULL,0,1,NULL,NULL,NULL,NULL,NULL),(9,'REF-06247095',NULL,NULL,NULL,NULL,'wilfre test','sophiacelineee@gmail.com',NULL,NULL,NULL,NULL,NULL,'$2y$10$/Ueeki5bbKLF524l/9ZYVudX5OLUfMw17Aa3RFaeqn/ipVt5KMV82','user','2026-01-31 15:16:10','repair','anything',NULL,'Student','[{\"id\":\"5\",\"title\":\"Computer Systems Servicing\",\"category\":\"IT\",\"score\":1}]',NULL,0,1,NULL,NULL,NULL,NULL,NULL),(14,'REF-27052746',NULL,NULL,NULL,NULL,'mama','khentagustinc@gmail.com',NULL,NULL,NULL,NULL,NULL,'$2y$10$U0mfS50jZsWs6M1Xjlx7BeLk0WB4jZjqyulryVDhknI.HxOCdMphK','user','2026-01-31 21:31:46','I\'m not good speak English','learn speak english','i want to learn how to speak english','Student','[]',NULL,0,1,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-02  2:20:26
