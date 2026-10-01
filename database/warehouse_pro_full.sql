/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.23-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: WarehouseProDB
-- ------------------------------------------------------
-- Server version	10.6.23-MariaDB-0ubuntu0.22.04.1

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
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,6,'login','Admin logged in','2026-09-25 12:02:29'),(2,6,'create_product','Added 100 products to catalog','2026-09-25 12:02:29'),(3,6,'create_supplier','Added 8 suppliers','2026-09-25 12:02:29'),(4,6,'create_location','Created 12 warehouse zones','2026-09-25 12:02:29'),(5,7,'login','Manager logged in','2026-09-25 12:02:29'),(6,7,'receive_stock','Received PO #1 from Nordic Electronics','2026-09-25 12:02:29'),(7,7,'receive_stock','Received PO #2 from Baltic Furniture','2026-09-25 12:02:29'),(8,9,'login','Staff logged in','2026-09-25 12:02:29'),(9,9,'create_shipment','Shipped order #1 to Helsinki Logistics','2026-09-25 12:02:29'),(10,9,'create_shipment','Shipped order #2 to Espoo Retail','2026-09-25 12:02:29'),(11,10,'login','Staff logged in','2026-09-25 12:02:29'),(12,10,'stock_movement','Moved 30 units from Zone B to Zone C','2026-09-25 12:02:29'),(13,6,'update_product','Updated price for ELEC-0005','2026-09-25 12:02:29'),(14,7,'create_purchase_order','Created PO #9 for Nordic Electronics','2026-09-25 12:02:29'),(15,9,'return_stock','Processed return of 5 units ELEC-0005','2026-09-25 12:02:29'),(16,7,'pick_shipment','Picked and shipped SH-15: 3 line(s)','2026-09-27 12:22:40'),(17,7,'receive_po','Received PO-12: 4 line(s)','2026-09-28 10:05:31'),(18,7,'pick_shipment','Shipped SH-11: 2 line(s) · tracking WH-20260928-SH11-69F4','2026-09-28 10:20:20'),(19,7,'transfer_stock','Transferred 10 units (product #1) from JKL-A1-B01 to JKL-A1-B02','2026-09-28 10:39:22'),(20,7,'adjust_stock','Up adjustment: 5 units (product #1, loc #111) — reason: damage','2026-09-28 11:08:13'),(21,7,'adjust_stock','Down adjustment: 5 units (product #1, loc #111) — reason: damage','2026-09-28 11:16:29'),(22,7,'adjust_stock','Down adjustment: 5 units (product #1, loc #111) — reason: damage','2026-09-28 11:21:15'),(23,7,'adjust_stock','Down adjustment: 5 units (product #1, loc #111) — reason: damage','2026-09-28 11:22:01'),(24,7,'adjust_stock','Down adjustment: 5 units (product #1, loc #111) — reason: damage','2026-09-28 11:28:14'),(25,7,'adjust_stock','Down adjustment: 5 units (product #1, loc #111) — reason: damage','2026-09-28 11:32:12'),(26,7,'adjust_stock','Up adjustment: 2 units (product #72, loc #60) — reason: found','2026-09-28 11:58:38'),(27,7,'cycle_count','Cycle count at loc #109: 0 correction(s), net variance 0','2026-09-28 12:47:07'),(28,7,'cycle_count','Cycle count at loc #109: 3 correction(s), net variance 220','2026-09-28 12:49:14'),(29,7,'cycle_count','Cycle count at loc #109: 1 correction(s), net variance -50','2026-09-28 12:54:03'),(30,7,'quarantine_release','Batch B-CLEA0003-Q1 released back to available stock.','2026-09-28 12:59:00'),(31,7,'approval_approve','Movement #278 (transfer, qty 25) approved','2026-09-28 13:08:25'),(32,6,'category_created','Created category #13 (Test Cat)','2026-09-29 12:34:11'),(33,6,'shipment_created','Created SH-19 for customer \'Test Customer Oy\', warehouse #1, 2 line(s)','2026-09-30 09:38:00'),(34,6,'pick_shipment','Shipped SH-18: 1 line(s) · tracking WH-20260930-SH18-721A','2026-09-30 10:10:54'),(35,6,'shipment_delivered','Marked SH-18 as delivered (tracking WH-20260930-SH18-721A) — customer: Rovaniemi Logistics','2026-09-30 10:28:50'),(36,6,'warehouse_created','Created warehouse #13 (TST — Test Warehouse)','2026-09-30 12:31:32'),(37,7,'shift_assigned','Assigned morning shift #1 to user #9 on 2026-10-01','2026-10-01 08:58:33'),(38,9,'clock_in','Clocked in — entry #1','2026-10-01 10:12:33'),(39,9,'clock_out','Clocked out — entry #1','2026-10-01 10:12:38');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Electronics','Consumer and industrial electronics','active'),(2,'Furniture','Office and warehouse furniture','active'),(3,'Apparel','Clothing, uniforms, wearable items','active'),(4,'Tools & Equipment','Hand tools, power tools, equipment','active'),(5,'Office Supplies','Stationery, paper, desk supplies','active'),(6,'Cleaning Supplies','Cleaners, mops, sanitizers','active'),(7,'Batteries & Power','Batteries, chargers, power accessories','active'),(8,'Hardware','Nails, screws, bolts, fasteners','active'),(9,'Food & Beverage','Packaged food and drinks','active'),(10,'Medical Supplies','First aid, PPE, medical consumables','active'),(11,'Packaging','Boxes, tape, bubble wrap','active'),(12,'Safety Equipment','Helmets, gloves, vests, goggles','active'),(13,'Test Cat','Testing','active');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_logins`
--

DROP TABLE IF EXISTS `failed_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username_attempted` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `reason` enum('unknown_user','bad_password','inactive_account') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_username` (`username_attempted`),
  KEY `idx_ip` (`ip_address`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_logins`
--

LOCK TABLES `failed_logins` WRITE;
/*!40000 ALTER TABLE `failed_logins` DISABLE KEYS */;
INSERT INTO `failed_logins` VALUES (1,'matias','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','bad_password','2026-10-01 09:34:58');
/*!40000 ALTER TABLE `failed_logins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_counted_at` datetime DEFAULT NULL,
  `last_movement_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`,`location_id`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
INSERT INTO `inventory` VALUES (1,1,111,215,'2026-09-28 11:49:33',NULL,'2026-09-28 14:32:12'),(2,2,111,500,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(3,3,111,300,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(4,4,111,180,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(5,5,111,45,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(6,6,111,110,'2026-09-27 12:22:40',NULL,'2026-09-27 15:22:40'),(7,7,111,90,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(8,8,111,180,'2026-09-27 12:22:40',NULL,'2026-09-27 15:22:40'),(9,9,113,35,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(10,10,113,20,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(11,11,113,25,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(12,12,113,40,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(13,13,113,8,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(14,14,113,200,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(15,15,109,300,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(16,16,109,450,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(17,17,109,600,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(18,18,109,150,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(19,19,109,100,'2026-09-28 12:49:14','2026-09-28 15:49:14','2026-09-28 15:49:14'),(20,20,120,320,'2026-09-28 10:20:20',NULL,'2026-09-28 13:20:20'),(21,21,120,240,'2026-09-28 10:20:20',NULL,'2026-09-28 13:20:20'),(22,22,120,150,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(23,23,120,220,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(24,24,120,180,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(25,25,120,65,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(26,26,120,90,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(27,27,109,850,'2026-09-28 12:54:03','2026-09-28 15:54:03','2026-09-28 15:54:03'),(28,28,109,500,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(29,29,109,400,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(30,30,109,150,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(31,31,109,350,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(32,32,119,200,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(33,33,119,250,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(34,34,119,400,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(35,35,119,120,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(36,36,119,300,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(37,37,111,600,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(38,38,111,550,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(39,39,111,200,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(40,40,111,150,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(41,41,111,300,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(42,42,120,500,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(43,43,120,410,'2026-09-30 10:10:54',NULL,'2026-09-30 13:10:54'),(44,44,120,350,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(45,45,120,400,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(46,46,120,300,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(47,47,118,150,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(48,48,118,200,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(49,49,113,400,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(50,50,118,300,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(51,51,118,250,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(52,52,109,100,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(53,53,109,700,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(54,54,109,400,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(55,55,109,500,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(56,56,109,300,'2026-09-28 12:49:14','2026-09-28 15:49:14','2026-09-28 15:49:14'),(57,57,113,500,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(58,58,113,600,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(59,59,109,800,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(60,60,112,200,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(61,61,109,400,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(62,62,119,250,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(63,63,109,300,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(64,64,109,200,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(65,65,115,80,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(66,66,119,150,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(67,67,109,180,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(68,68,120,70,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(69,69,120,110,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(70,70,120,250,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(71,71,111,130,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(72,72,111,400,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(73,73,111,80,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(74,74,111,140,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(75,75,111,350,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(76,76,111,60,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(77,77,111,200,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(78,78,111,300,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(79,79,109,150,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(80,80,109,180,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(81,81,112,40,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(82,82,109,300,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(83,83,109,500,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(84,84,109,400,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(85,85,109,120,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(86,86,109,60,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(87,87,113,100,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(88,88,113,30,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(89,89,113,50,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(90,90,113,120,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(91,91,112,45,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(92,92,115,200,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(93,93,115,40,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(94,94,119,250,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(95,95,119,180,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(96,96,109,600,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(97,97,119,25,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(98,98,109,200,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(99,99,119,30,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(100,100,119,250,'2026-09-26 12:37:34',NULL,'2026-09-25 14:58:55'),(101,20,36,300,'2026-09-28 10:05:31',NULL,'2026-09-28 13:05:31'),(102,24,285,150,'2026-09-28 10:05:31',NULL,'2026-09-28 13:05:31'),(103,25,261,100,'2026-09-28 10:05:31',NULL,'2026-09-28 13:05:31'),(104,30,237,150,'2026-09-28 10:05:31',NULL,'2026-09-28 13:05:31'),(105,1,123,20,'2026-09-28 10:39:22',NULL,'2026-09-28 13:39:22'),(106,72,60,2,'2026-09-28 11:58:38',NULL,'2026-09-28 14:58:38');
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_history`
--

DROP TABLE IF EXISTS `location_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `changes` text DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `location_history_ibfk_1` (`location_id`),
  KEY `location_history_ibfk_2` (`user_id`),
  CONSTRAINT `location_history_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `location_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_history`
--

LOCK TABLES `location_history` WRITE;
/*!40000 ALTER TABLE `location_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` enum('receiving','storage','dispatch','returns','quarantine') DEFAULT 'storage',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `warehouse_id` (`warehouse_id`),
  CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=349 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
INSERT INTO `locations` VALUES (1,1,'Zone A - Receiving','HEL-Z-OLD','Dock 1, Entry Gate','Inbound goods receiving area','storage','2026-09-25 11:54:27'),(2,2,'Zone B - Aisle 1','ESP-Z-OLD','Rack B1, Shelves 1-20','Small parts storage','storage','2026-09-25 11:54:27'),(3,3,'Zone C - Aisle 2','VAN-Z-OLD','Rack C1, Shelves 1-20','Medium parts storage','storage','2026-09-25 11:54:27'),(4,4,'Zone D - Aisle 3','TRE-Z-OLD','Rack D1, Shelves 1-20','Bulk items storage','storage','2026-09-25 11:54:27'),(5,5,'Zone E - Cold Storage','TKU-Z-OLD','Refrigerated Room 1','Temperature-controlled items','storage','2026-09-25 11:54:27'),(6,6,'Zone F - Hazardous','OUL-Z-OLD','Isolated Room 2','Flammable and hazardous materials','storage','2026-09-25 11:54:27'),(7,7,'Zone G - Electronics','JKL-Z-OLD','Anti-static Room','ESD-protected electronics storage','storage','2026-09-25 11:54:27'),(8,8,'Zone H - Fragile','KUO-Z-OLD','Padded Shelves','Glass and fragile items','storage','2026-09-25 11:54:27'),(9,9,'Zone I - Bulk Floor','LHI-Z-OLD','Open floor space','Pallet and bulk storage','storage','2026-09-25 11:54:27'),(10,10,'Zone J - Returns','POR-Z-OLD','Returns Processing Area','Customer returns awaiting inspection','storage','2026-09-25 11:54:27'),(11,11,'Zone K - Dispatch','LPR-Z-OLD','Dock 2, Exit Gate','Outbound shipping staging','storage','2026-09-25 11:54:27'),(12,12,'Zone L - Quarantine','RVN-Z-OLD','Holding Area','Quality check pending items','storage','2026-09-25 11:54:27'),(13,2,'Dock - Receiving','ESP-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(14,1,'Dock - Receiving','HEL-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(15,7,'Dock - Receiving','JKL-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(16,8,'Dock - Receiving','KUO-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(17,9,'Dock - Receiving','LHI-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(18,11,'Dock - Receiving','LPR-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(19,6,'Dock - Receiving','OUL-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(20,10,'Dock - Receiving','POR-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(21,12,'Dock - Receiving','RVN-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(22,5,'Dock - Receiving','TKU-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(23,4,'Dock - Receiving','TRE-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(24,3,'Dock - Receiving','VAN-RCV-OLD',NULL,NULL,'receiving','2026-09-26 10:24:45'),(28,2,'Storage B - Aisle 1','ESP-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(29,1,'Storage B - Aisle 1','HEL-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(30,7,'Storage B - Aisle 1','JKL-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(31,8,'Storage B - Aisle 1','KUO-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(32,9,'Storage B - Aisle 1','LHI-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(33,11,'Storage B - Aisle 1','LPR-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(34,6,'Storage B - Aisle 1','OUL-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(35,10,'Storage B - Aisle 1','POR-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(36,12,'Storage B - Aisle 1','RVN-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(37,5,'Storage B - Aisle 1','TKU-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(38,4,'Storage B - Aisle 1','TRE-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(39,3,'Storage B - Aisle 1','VAN-A1-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(43,2,'Storage C - Aisle 2','ESP-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(44,1,'Storage C - Aisle 2','HEL-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(45,7,'Storage C - Aisle 2','JKL-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(46,8,'Storage C - Aisle 2','KUO-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(47,9,'Storage C - Aisle 2','LHI-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(48,11,'Storage C - Aisle 2','LPR-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(49,6,'Storage C - Aisle 2','OUL-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(50,10,'Storage C - Aisle 2','POR-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(51,12,'Storage C - Aisle 2','RVN-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(52,5,'Storage C - Aisle 2','TKU-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(53,4,'Storage C - Aisle 2','TRE-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(54,3,'Storage C - Aisle 2','VAN-A2-OLD',NULL,NULL,'storage','2026-09-26 10:24:45'),(58,2,'Dispatch Bay','ESP-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(59,1,'Dispatch Bay','HEL-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(60,7,'Dispatch Bay','JKL-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(61,8,'Dispatch Bay','KUO-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(62,9,'Dispatch Bay','LHI-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(63,11,'Dispatch Bay','LPR-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(64,6,'Dispatch Bay','OUL-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(65,10,'Dispatch Bay','POR-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(66,12,'Dispatch Bay','RVN-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(67,5,'Dispatch Bay','TKU-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(68,4,'Dispatch Bay','TRE-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(69,3,'Dispatch Bay','VAN-DSP-OLD',NULL,NULL,'dispatch','2026-09-26 10:24:45'),(73,2,'Quarantine Area','ESP-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(74,1,'Quarantine Area','HEL-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(75,7,'Quarantine Area','JKL-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(76,8,'Quarantine Area','KUO-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(77,9,'Quarantine Area','LHI-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(78,11,'Quarantine Area','LPR-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(79,6,'Quarantine Area','OUL-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(80,10,'Quarantine Area','POR-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(81,12,'Quarantine Area','RVN-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(82,5,'Quarantine Area','TKU-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(83,4,'Quarantine Area','TRE-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(84,3,'Quarantine Area','VAN-QAR-OLD',NULL,NULL,'quarantine','2026-09-26 10:24:45'),(85,2,'Dock 1 - Inbound','ESP-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(86,1,'Dock 1 - Inbound','HEL-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(87,7,'Dock 1 - Inbound','JKL-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(88,8,'Dock 1 - Inbound','KUO-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(89,9,'Dock 1 - Inbound','LHI-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(90,11,'Dock 1 - Inbound','LPR-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(91,6,'Dock 1 - Inbound','OUL-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(92,10,'Dock 1 - Inbound','POR-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(93,12,'Dock 1 - Inbound','RVN-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(94,5,'Dock 1 - Inbound','TKU-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(95,4,'Dock 1 - Inbound','TRE-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(96,3,'Dock 1 - Inbound','VAN-RCV-01',NULL,NULL,'receiving','2026-09-26 12:23:59'),(97,2,'Dock 2 - Inbound','ESP-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(98,1,'Dock 2 - Inbound','HEL-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(99,7,'Dock 2 - Inbound','JKL-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(100,8,'Dock 2 - Inbound','KUO-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(101,9,'Dock 2 - Inbound','LHI-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(102,11,'Dock 2 - Inbound','LPR-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(103,6,'Dock 2 - Inbound','OUL-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(104,10,'Dock 2 - Inbound','POR-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(105,12,'Dock 2 - Inbound','RVN-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(106,5,'Dock 2 - Inbound','TKU-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(107,4,'Dock 2 - Inbound','TRE-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(108,3,'Dock 2 - Inbound','VAN-RCV-02',NULL,NULL,'receiving','2026-09-26 12:23:59'),(109,2,'Aisle 1 - Rack 01','ESP-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(110,1,'Aisle 1 - Rack 01','HEL-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(111,7,'Aisle 1 - Rack 01','JKL-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(112,8,'Aisle 1 - Rack 01','KUO-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(113,9,'Aisle 1 - Rack 01','LHI-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(114,11,'Aisle 1 - Rack 01','LPR-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(115,6,'Aisle 1 - Rack 01','OUL-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(116,10,'Aisle 1 - Rack 01','POR-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(117,12,'Aisle 1 - Rack 01','RVN-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(118,5,'Aisle 1 - Rack 01','TKU-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(119,4,'Aisle 1 - Rack 01','TRE-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(120,3,'Aisle 1 - Rack 01','VAN-A1-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(121,2,'Aisle 1 - Rack 02','ESP-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(122,1,'Aisle 1 - Rack 02','HEL-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(123,7,'Aisle 1 - Rack 02','JKL-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(124,8,'Aisle 1 - Rack 02','KUO-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(125,9,'Aisle 1 - Rack 02','LHI-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(126,11,'Aisle 1 - Rack 02','LPR-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(127,6,'Aisle 1 - Rack 02','OUL-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(128,10,'Aisle 1 - Rack 02','POR-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(129,12,'Aisle 1 - Rack 02','RVN-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(130,5,'Aisle 1 - Rack 02','TKU-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(131,4,'Aisle 1 - Rack 02','TRE-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(132,3,'Aisle 1 - Rack 02','VAN-A1-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(133,2,'Aisle 1 - Rack 03','ESP-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(134,1,'Aisle 1 - Rack 03','HEL-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(135,7,'Aisle 1 - Rack 03','JKL-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(136,8,'Aisle 1 - Rack 03','KUO-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(137,9,'Aisle 1 - Rack 03','LHI-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(138,11,'Aisle 1 - Rack 03','LPR-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(139,6,'Aisle 1 - Rack 03','OUL-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(140,10,'Aisle 1 - Rack 03','POR-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(141,12,'Aisle 1 - Rack 03','RVN-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(142,5,'Aisle 1 - Rack 03','TKU-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(143,4,'Aisle 1 - Rack 03','TRE-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(144,3,'Aisle 1 - Rack 03','VAN-A1-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(145,2,'Aisle 1 - Rack 04','ESP-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(146,1,'Aisle 1 - Rack 04','HEL-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(147,7,'Aisle 1 - Rack 04','JKL-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(148,8,'Aisle 1 - Rack 04','KUO-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(149,9,'Aisle 1 - Rack 04','LHI-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(150,11,'Aisle 1 - Rack 04','LPR-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(151,6,'Aisle 1 - Rack 04','OUL-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(152,10,'Aisle 1 - Rack 04','POR-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(153,12,'Aisle 1 - Rack 04','RVN-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(154,5,'Aisle 1 - Rack 04','TKU-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(155,4,'Aisle 1 - Rack 04','TRE-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(156,3,'Aisle 1 - Rack 04','VAN-A1-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(157,2,'Aisle 1 - Rack 05','ESP-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(158,1,'Aisle 1 - Rack 05','HEL-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(159,7,'Aisle 1 - Rack 05','JKL-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(160,8,'Aisle 1 - Rack 05','KUO-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(161,9,'Aisle 1 - Rack 05','LHI-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(162,11,'Aisle 1 - Rack 05','LPR-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(163,6,'Aisle 1 - Rack 05','OUL-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(164,10,'Aisle 1 - Rack 05','POR-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(165,12,'Aisle 1 - Rack 05','RVN-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(166,5,'Aisle 1 - Rack 05','TKU-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(167,4,'Aisle 1 - Rack 05','TRE-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(168,3,'Aisle 1 - Rack 05','VAN-A1-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(169,2,'Aisle 2 - Rack 01','ESP-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(170,1,'Aisle 2 - Rack 01','HEL-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(171,7,'Aisle 2 - Rack 01','JKL-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(172,8,'Aisle 2 - Rack 01','KUO-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(173,9,'Aisle 2 - Rack 01','LHI-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(174,11,'Aisle 2 - Rack 01','LPR-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(175,6,'Aisle 2 - Rack 01','OUL-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(176,10,'Aisle 2 - Rack 01','POR-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(177,12,'Aisle 2 - Rack 01','RVN-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(178,5,'Aisle 2 - Rack 01','TKU-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(179,4,'Aisle 2 - Rack 01','TRE-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(180,3,'Aisle 2 - Rack 01','VAN-A2-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(181,2,'Aisle 2 - Rack 02','ESP-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(182,1,'Aisle 2 - Rack 02','HEL-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(183,7,'Aisle 2 - Rack 02','JKL-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(184,8,'Aisle 2 - Rack 02','KUO-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(185,9,'Aisle 2 - Rack 02','LHI-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(186,11,'Aisle 2 - Rack 02','LPR-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(187,6,'Aisle 2 - Rack 02','OUL-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(188,10,'Aisle 2 - Rack 02','POR-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(189,12,'Aisle 2 - Rack 02','RVN-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(190,5,'Aisle 2 - Rack 02','TKU-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(191,4,'Aisle 2 - Rack 02','TRE-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(192,3,'Aisle 2 - Rack 02','VAN-A2-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(193,2,'Aisle 2 - Rack 03','ESP-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(194,1,'Aisle 2 - Rack 03','HEL-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(195,7,'Aisle 2 - Rack 03','JKL-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(196,8,'Aisle 2 - Rack 03','KUO-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(197,9,'Aisle 2 - Rack 03','LHI-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(198,11,'Aisle 2 - Rack 03','LPR-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(199,6,'Aisle 2 - Rack 03','OUL-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(200,10,'Aisle 2 - Rack 03','POR-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(201,12,'Aisle 2 - Rack 03','RVN-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(202,5,'Aisle 2 - Rack 03','TKU-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(203,4,'Aisle 2 - Rack 03','TRE-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(204,3,'Aisle 2 - Rack 03','VAN-A2-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(205,2,'Aisle 2 - Rack 04','ESP-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(206,1,'Aisle 2 - Rack 04','HEL-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(207,7,'Aisle 2 - Rack 04','JKL-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(208,8,'Aisle 2 - Rack 04','KUO-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(209,9,'Aisle 2 - Rack 04','LHI-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(210,11,'Aisle 2 - Rack 04','LPR-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(211,6,'Aisle 2 - Rack 04','OUL-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(212,10,'Aisle 2 - Rack 04','POR-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(213,12,'Aisle 2 - Rack 04','RVN-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(214,5,'Aisle 2 - Rack 04','TKU-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(215,4,'Aisle 2 - Rack 04','TRE-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(216,3,'Aisle 2 - Rack 04','VAN-A2-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(217,2,'Aisle 2 - Rack 05','ESP-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(218,1,'Aisle 2 - Rack 05','HEL-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(219,7,'Aisle 2 - Rack 05','JKL-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(220,8,'Aisle 2 - Rack 05','KUO-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(221,9,'Aisle 2 - Rack 05','LHI-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(222,11,'Aisle 2 - Rack 05','LPR-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(223,6,'Aisle 2 - Rack 05','OUL-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(224,10,'Aisle 2 - Rack 05','POR-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(225,12,'Aisle 2 - Rack 05','RVN-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(226,5,'Aisle 2 - Rack 05','TKU-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(227,4,'Aisle 2 - Rack 05','TRE-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(228,3,'Aisle 2 - Rack 05','VAN-A2-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(229,2,'Aisle 3 - Rack 01','ESP-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(230,1,'Aisle 3 - Rack 01','HEL-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(231,7,'Aisle 3 - Rack 01','JKL-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(232,8,'Aisle 3 - Rack 01','KUO-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(233,9,'Aisle 3 - Rack 01','LHI-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(234,11,'Aisle 3 - Rack 01','LPR-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(235,6,'Aisle 3 - Rack 01','OUL-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(236,10,'Aisle 3 - Rack 01','POR-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(237,12,'Aisle 3 - Rack 01','RVN-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(238,5,'Aisle 3 - Rack 01','TKU-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(239,4,'Aisle 3 - Rack 01','TRE-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(240,3,'Aisle 3 - Rack 01','VAN-A3-B01',NULL,NULL,'storage','2026-09-26 12:23:59'),(241,2,'Aisle 3 - Rack 02','ESP-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(242,1,'Aisle 3 - Rack 02','HEL-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(243,7,'Aisle 3 - Rack 02','JKL-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(244,8,'Aisle 3 - Rack 02','KUO-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(245,9,'Aisle 3 - Rack 02','LHI-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(246,11,'Aisle 3 - Rack 02','LPR-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(247,6,'Aisle 3 - Rack 02','OUL-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(248,10,'Aisle 3 - Rack 02','POR-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(249,12,'Aisle 3 - Rack 02','RVN-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(250,5,'Aisle 3 - Rack 02','TKU-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(251,4,'Aisle 3 - Rack 02','TRE-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(252,3,'Aisle 3 - Rack 02','VAN-A3-B02',NULL,NULL,'storage','2026-09-26 12:23:59'),(253,2,'Aisle 3 - Rack 03','ESP-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(254,1,'Aisle 3 - Rack 03','HEL-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(255,7,'Aisle 3 - Rack 03','JKL-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(256,8,'Aisle 3 - Rack 03','KUO-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(257,9,'Aisle 3 - Rack 03','LHI-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(258,11,'Aisle 3 - Rack 03','LPR-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(259,6,'Aisle 3 - Rack 03','OUL-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(260,10,'Aisle 3 - Rack 03','POR-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(261,12,'Aisle 3 - Rack 03','RVN-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(262,5,'Aisle 3 - Rack 03','TKU-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(263,4,'Aisle 3 - Rack 03','TRE-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(264,3,'Aisle 3 - Rack 03','VAN-A3-B03',NULL,NULL,'storage','2026-09-26 12:23:59'),(265,2,'Aisle 3 - Rack 04','ESP-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(266,1,'Aisle 3 - Rack 04','HEL-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(267,7,'Aisle 3 - Rack 04','JKL-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(268,8,'Aisle 3 - Rack 04','KUO-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(269,9,'Aisle 3 - Rack 04','LHI-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(270,11,'Aisle 3 - Rack 04','LPR-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(271,6,'Aisle 3 - Rack 04','OUL-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(272,10,'Aisle 3 - Rack 04','POR-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(273,12,'Aisle 3 - Rack 04','RVN-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(274,5,'Aisle 3 - Rack 04','TKU-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(275,4,'Aisle 3 - Rack 04','TRE-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(276,3,'Aisle 3 - Rack 04','VAN-A3-B04',NULL,NULL,'storage','2026-09-26 12:23:59'),(277,2,'Aisle 3 - Rack 05','ESP-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(278,1,'Aisle 3 - Rack 05','HEL-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(279,7,'Aisle 3 - Rack 05','JKL-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(280,8,'Aisle 3 - Rack 05','KUO-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(281,9,'Aisle 3 - Rack 05','LHI-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(282,11,'Aisle 3 - Rack 05','LPR-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(283,6,'Aisle 3 - Rack 05','OUL-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(284,10,'Aisle 3 - Rack 05','POR-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(285,12,'Aisle 3 - Rack 05','RVN-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(286,5,'Aisle 3 - Rack 05','TKU-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(287,4,'Aisle 3 - Rack 05','TRE-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(288,3,'Aisle 3 - Rack 05','VAN-A3-B05',NULL,NULL,'storage','2026-09-26 12:23:59'),(289,2,'Dispatch Bay 1','ESP-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(290,1,'Dispatch Bay 1','HEL-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(291,7,'Dispatch Bay 1','JKL-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(292,8,'Dispatch Bay 1','KUO-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(293,9,'Dispatch Bay 1','LHI-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(294,11,'Dispatch Bay 1','LPR-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(295,6,'Dispatch Bay 1','OUL-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(296,10,'Dispatch Bay 1','POR-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(297,12,'Dispatch Bay 1','RVN-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(298,5,'Dispatch Bay 1','TKU-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(299,4,'Dispatch Bay 1','TRE-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(300,3,'Dispatch Bay 1','VAN-DSP-01',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(301,2,'Dispatch Bay 2','ESP-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(302,1,'Dispatch Bay 2','HEL-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(303,7,'Dispatch Bay 2','JKL-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(304,8,'Dispatch Bay 2','KUO-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(305,9,'Dispatch Bay 2','LHI-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(306,11,'Dispatch Bay 2','LPR-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(307,6,'Dispatch Bay 2','OUL-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(308,10,'Dispatch Bay 2','POR-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(309,12,'Dispatch Bay 2','RVN-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(310,5,'Dispatch Bay 2','TKU-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(311,4,'Dispatch Bay 2','TRE-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(312,3,'Dispatch Bay 2','VAN-DSP-02',NULL,NULL,'dispatch','2026-09-26 12:23:59'),(313,2,'Returns Processing','ESP-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(314,1,'Returns Processing','HEL-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(315,7,'Returns Processing','JKL-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(316,8,'Returns Processing','KUO-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(317,9,'Returns Processing','LHI-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(318,11,'Returns Processing','LPR-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(319,6,'Returns Processing','OUL-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(320,10,'Returns Processing','POR-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(321,12,'Returns Processing','RVN-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(322,5,'Returns Processing','TKU-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(323,4,'Returns Processing','TRE-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(324,3,'Returns Processing','VAN-RET-01',NULL,NULL,'returns','2026-09-26 12:23:59'),(325,2,'Quarantine Bay 1','ESP-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(326,1,'Quarantine Bay 1','HEL-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(327,7,'Quarantine Bay 1','JKL-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(328,8,'Quarantine Bay 1','KUO-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(329,9,'Quarantine Bay 1','LHI-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(330,11,'Quarantine Bay 1','LPR-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(331,6,'Quarantine Bay 1','OUL-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(332,10,'Quarantine Bay 1','POR-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(333,12,'Quarantine Bay 1','RVN-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(334,5,'Quarantine Bay 1','TKU-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(335,4,'Quarantine Bay 1','TRE-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(336,3,'Quarantine Bay 1','VAN-QAR-01',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(337,2,'Quarantine Bay 2','ESP-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(338,1,'Quarantine Bay 2','HEL-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(339,7,'Quarantine Bay 2','JKL-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(340,8,'Quarantine Bay 2','KUO-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(341,9,'Quarantine Bay 2','LHI-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(342,11,'Quarantine Bay 2','LPR-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(343,6,'Quarantine Bay 2','OUL-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(344,10,'Quarantine Bay 2','POR-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(345,12,'Quarantine Bay 2','RVN-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(346,5,'Quarantine Bay 2','TKU-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(347,4,'Quarantine Bay 2','TRE-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59'),(348,3,'Quarantine Bay 2','VAN-QAR-02',NULL,NULL,'quarantine','2026-09-26 12:23:59');
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','replied') NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (6,9,'Can I request additional safety gloves for the night shift team?','Yes — submit a purchase requisition under Supplies category. I will approve it today.','2026-09-30 12:13:26','replied','2026-09-30 12:07:42'),(7,9,'Customer returned 5 units of ELEC-0005. Where should I place them?','Place returns in quarantine at your warehouse. QC will inspect within 24h. Do not put back into sellable stock.','2026-09-30 12:13:26','replied','2026-09-30 12:07:42'),(8,10,'Stock count for TOOL-0006 shows 65 but system says 68. Discrepancy of 3.','Recount TOOL-0006 in Zone A1-B03 tomorrow morning with the second scanner. File an adjustment for the 3-unit variance after the recount.','2026-09-30 12:13:26','replied','2026-09-30 12:07:42'),(9,9,'The scanner in Zone B is not reading barcodes properly. Can we get it checked?','Maintenance scheduled for Friday. Use scanner #2 in the meantime.','2026-09-29 16:09:55','replied','2026-09-29 12:09:55'),(10,10,'Shelving in Zone D is overloaded. Need more racks.','Racks ordered. Delivery expected next Tuesday. Please keep aisle clear.','2026-09-27 12:09:55','replied','2026-09-26 12:09:55');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_batches`
--

DROP TABLE IF EXISTS `product_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `batch_number` varchar(100) NOT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `location_id` int(11) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `status` enum('available','quarantine','expired','depleted') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_batch` (`product_id`,`batch_number`),
  KEY `location_id` (`location_id`),
  CONSTRAINT `product_batches_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_batches_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_batches`
--

LOCK TABLES `product_batches` WRITE;
/*!40000 ALTER TABLE `product_batches` DISABLE KEYS */;
INSERT INTO `product_batches` VALUES (1,32,'B-CLEA0001-A','LOT-00032-A','2026-07-27','2027-10-30',100,4,8.99,'available','2026-09-25 12:23:26'),(2,33,'B-CLEA0002-A','LOT-00033-A','2026-07-27','2027-10-30',125,4,3.90,'available','2026-09-25 12:23:26'),(3,34,'B-CLEA0003-A','LOT-00034-A','2026-07-27','2027-10-30',200,4,5.39,'available','2026-09-25 12:23:26'),(4,35,'B-CLEA0004-A','LOT-00035-A','2026-07-27','2027-10-30',60,4,14.99,'available','2026-09-25 12:23:26'),(5,36,'B-CLEA0005-A','LOT-00036-A','2026-07-27','2027-10-30',150,4,5.99,'available','2026-09-25 12:23:26'),(6,95,'B-CLEA0006-A','LOT-00095-A','2026-07-27','2027-10-30',90,4,11.39,'available','2026-09-25 12:23:26'),(7,96,'B-CLEA0007-A','LOT-00096-A','2026-07-27','2027-10-30',300,2,9.59,'available','2026-09-25 12:23:26'),(8,97,'B-CLEA0008-A','LOT-00097-A','2026-07-27','2027-10-30',12,4,107.40,'available','2026-09-25 12:23:26'),(9,98,'B-CLEA0009-A','LOT-00098-A','2026-07-27','2027-10-30',100,2,4.19,'available','2026-09-25 12:23:26'),(10,99,'B-CLEA0010-A','LOT-00099-A','2026-07-27','2027-10-30',15,4,149.40,'available','2026-09-25 12:23:26'),(11,100,'B-CLEA0011-A','LOT-00100-A','2026-07-27','2027-10-30',125,4,4.79,'available','2026-09-25 12:23:26'),(12,47,'B-FOOD0001-A','LOT-00047-A','2026-07-27','2027-10-30',75,5,11.99,'available','2026-09-25 12:23:26'),(13,48,'B-FOOD0002-A','LOT-00048-A','2026-07-27','2027-10-30',100,5,5.39,'available','2026-09-25 12:23:26'),(14,49,'B-FOOD0003-A','LOT-00049-A','2026-07-27','2027-10-30',200,9,7.79,'available','2026-09-25 12:23:26'),(15,50,'B-FOOD0004-A','LOT-00050-A','2026-07-27','2027-10-30',150,5,14.99,'available','2026-09-25 12:23:26'),(16,51,'B-FOOD0005-A','LOT-00051-A','2026-07-27','2027-10-30',125,5,10.19,'available','2026-09-25 12:23:26'),(17,52,'B-MEDI0001-A','LOT-00052-A','2026-07-27','2027-10-30',50,2,20.99,'available','2026-09-25 12:23:26'),(18,53,'B-MEDI0002-A','LOT-00053-A','2026-07-27','2027-10-30',350,2,5.99,'available','2026-09-25 12:23:26'),(19,54,'B-MEDI0003-A','LOT-00054-A','2026-07-27','2027-10-30',200,2,4.19,'available','2026-09-25 12:23:26'),(20,55,'B-MEDI0004-A','LOT-00055-A','2026-07-27','2027-10-30',250,2,3.59,'available','2026-09-25 12:23:26'),(21,56,'B-MEDI0005-A','LOT-00056-A','2026-07-27','2027-10-30',100,2,7.79,'available','2026-09-25 12:23:26'),(32,32,'B-CLEA0001-B','LOT-00032-B','2026-08-26','2028-02-07',100,4,8.99,'available','2026-09-25 12:23:26'),(33,33,'B-CLEA0002-B','LOT-00033-B','2026-08-26','2028-02-07',125,4,3.90,'available','2026-09-25 12:23:26'),(34,34,'B-CLEA0003-B','LOT-00034-B','2026-08-26','2028-02-07',200,4,5.39,'available','2026-09-25 12:23:26'),(35,35,'B-CLEA0004-B','LOT-00035-B','2026-08-26','2028-02-07',60,4,14.99,'available','2026-09-25 12:23:26'),(36,36,'B-CLEA0005-B','LOT-00036-B','2026-08-26','2028-02-07',150,4,5.99,'available','2026-09-25 12:23:26'),(37,95,'B-CLEA0006-B','LOT-00095-B','2026-08-26','2028-02-07',90,4,11.39,'available','2026-09-25 12:23:26'),(38,96,'B-CLEA0007-B','LOT-00096-B','2026-08-26','2028-02-07',300,2,9.59,'available','2026-09-25 12:23:26'),(39,97,'B-CLEA0008-B','LOT-00097-B','2026-08-26','2028-02-07',13,4,107.40,'available','2026-09-25 12:23:26'),(40,98,'B-CLEA0009-B','LOT-00098-B','2026-08-26','2028-02-07',100,2,4.19,'available','2026-09-25 12:23:26'),(41,99,'B-CLEA0010-B','LOT-00099-B','2026-08-26','2028-02-07',15,4,149.40,'available','2026-09-25 12:23:26'),(42,100,'B-CLEA0011-B','LOT-00100-B','2026-08-26','2028-02-07',125,4,4.79,'available','2026-09-25 12:23:26'),(43,47,'B-FOOD0001-B','LOT-00047-B','2026-08-26','2028-02-07',75,5,11.99,'available','2026-09-25 12:23:26'),(44,48,'B-FOOD0002-B','LOT-00048-B','2026-08-26','2028-02-07',100,5,5.39,'available','2026-09-25 12:23:26'),(45,49,'B-FOOD0003-B','LOT-00049-B','2026-08-26','2028-02-07',200,9,7.79,'available','2026-09-25 12:23:26'),(46,50,'B-FOOD0004-B','LOT-00050-B','2026-08-26','2028-02-07',150,5,14.99,'available','2026-09-25 12:23:26'),(47,51,'B-FOOD0005-B','LOT-00051-B','2026-08-26','2028-02-07',125,5,10.19,'available','2026-09-25 12:23:26'),(48,52,'B-MEDI0001-B','LOT-00052-B','2026-08-26','2028-02-07',50,2,20.99,'available','2026-09-25 12:23:26'),(49,53,'B-MEDI0002-B','LOT-00053-B','2026-08-26','2028-02-07',350,2,5.99,'available','2026-09-25 12:23:26'),(50,54,'B-MEDI0003-B','LOT-00054-B','2026-08-26','2028-02-07',200,2,4.19,'available','2026-09-25 12:23:26'),(51,55,'B-MEDI0004-B','LOT-00055-B','2026-08-26','2028-02-07',250,2,3.59,'available','2026-09-25 12:23:26'),(52,56,'B-MEDI0005-B','LOT-00056-B','2026-08-26','2028-02-07',100,2,7.79,'available','2026-09-25 12:23:26'),(63,47,'B-FOOD0001-X1','LOT-00047-X1','2025-05-13','2026-09-05',15,5,11.99,'expired','2026-09-25 12:24:32'),(64,52,'B-MEDI0001-X1','LOT-00052-X1','2024-07-17','2026-07-27',8,2,20.99,'expired','2026-09-25 12:24:32'),(65,48,'B-FOOD0002-C1','LOT-00048-C1','2025-08-21','2026-10-05',40,5,5.39,'available','2026-09-25 12:24:32'),(66,53,'B-MEDI0002-C1','LOT-00053-C1','2025-02-02','2026-10-07',200,2,5.99,'available','2026-09-25 12:24:32'),(67,34,'B-CLEA0003-Q1','LOT-00034-Q1','2026-09-05','2027-10-30',50,12,5.39,'available','2026-09-25 12:24:32'),(68,54,'B-MEDI0003-Q1','LOT-00054-Q1','2026-09-10','2027-12-19',80,12,4.19,'quarantine','2026-09-25 12:24:32'),(69,1,'SEED-ELEC-0001-01',NULL,NULL,'2027-09-27',225,111,NULL,'available','2026-09-27 12:03:59'),(70,2,'SEED-ELEC-0002-01',NULL,NULL,'2027-09-27',500,111,NULL,'available','2026-09-27 12:03:59'),(71,3,'SEED-ELEC-0003-01',NULL,NULL,'2027-09-27',300,111,NULL,'available','2026-09-27 12:03:59'),(72,4,'SEED-ELEC-0004-01',NULL,NULL,'2027-09-27',180,111,NULL,'available','2026-09-27 12:03:59'),(73,5,'SEED-ELEC-0005-01',NULL,NULL,'2027-09-27',100,111,NULL,'available','2026-09-27 12:03:59'),(74,6,'SEED-ELEC-0006-01',NULL,NULL,'2027-09-27',110,111,NULL,'available','2026-09-27 12:03:59'),(75,7,'SEED-ELEC-0007-01',NULL,NULL,'2027-09-27',100,111,NULL,'available','2026-09-27 12:03:59'),(76,8,'SEED-ELEC-0008-01',NULL,NULL,'2027-09-27',180,111,NULL,'available','2026-09-27 12:03:59'),(77,9,'SEED-FURN-0001-01',NULL,NULL,'2027-09-27',100,113,NULL,'available','2026-09-27 12:03:59'),(78,10,'SEED-FURN-0002-01',NULL,NULL,'2027-09-27',100,113,NULL,'available','2026-09-27 12:03:59'),(79,11,'SEED-FURN-0003-01',NULL,NULL,'2027-09-27',100,113,NULL,'available','2026-09-27 12:03:59'),(80,12,'SEED-FURN-0004-01',NULL,NULL,'2027-09-27',100,113,NULL,'available','2026-09-27 12:03:59'),(81,13,'SEED-FURN-0005-01',NULL,NULL,'2027-09-27',100,113,NULL,'available','2026-09-27 12:03:59'),(82,14,'SEED-FURN-0006-01',NULL,NULL,'2027-09-27',200,113,NULL,'available','2026-09-27 12:03:59'),(83,15,'SEED-APPA-0001-01',NULL,NULL,'2027-09-27',300,109,NULL,'available','2026-09-27 12:03:59'),(84,16,'SEED-APPA-0002-01',NULL,NULL,'2027-09-27',450,109,NULL,'available','2026-09-27 12:03:59'),(85,17,'SEED-APPA-0003-01',NULL,NULL,'2027-09-27',600,109,NULL,'available','2026-09-27 12:03:59'),(86,18,'SEED-APPA-0004-01',NULL,NULL,'2027-09-27',150,109,NULL,'available','2026-09-27 12:03:59'),(87,19,'SEED-APPA-0005-01',NULL,NULL,'2027-09-27',100,109,NULL,'available','2026-09-27 12:03:59'),(88,20,'SEED-TOOL-0001-01',NULL,NULL,'2027-09-27',320,120,NULL,'available','2026-09-27 12:03:59'),(89,21,'SEED-TOOL-0002-01',NULL,NULL,'2027-09-27',240,120,NULL,'available','2026-09-27 12:03:59'),(90,22,'SEED-TOOL-0003-01',NULL,NULL,'2027-09-27',150,120,NULL,'available','2026-09-27 12:03:59'),(91,23,'SEED-TOOL-0004-01',NULL,NULL,'2027-09-27',220,120,NULL,'available','2026-09-27 12:03:59'),(92,24,'SEED-TOOL-0005-01',NULL,NULL,'2027-09-27',180,120,NULL,'available','2026-09-27 12:03:59'),(93,25,'SEED-TOOL-0006-01',NULL,NULL,'2027-09-27',100,120,NULL,'available','2026-09-27 12:03:59'),(94,26,'SEED-TOOL-0007-01',NULL,NULL,'2027-09-27',100,120,NULL,'available','2026-09-27 12:03:59'),(95,27,'SEED-OFFI-0001-01',NULL,NULL,'2027-09-27',800,109,NULL,'available','2026-09-27 12:03:59'),(96,28,'SEED-OFFI-0002-01',NULL,NULL,'2027-09-27',500,109,NULL,'available','2026-09-27 12:03:59'),(97,29,'SEED-OFFI-0003-01',NULL,NULL,'2027-09-27',400,109,NULL,'available','2026-09-27 12:03:59'),(98,30,'SEED-OFFI-0004-01',NULL,NULL,'2027-09-27',150,109,NULL,'available','2026-09-27 12:03:59'),(99,31,'SEED-OFFI-0005-01',NULL,NULL,'2027-09-27',350,109,NULL,'available','2026-09-27 12:03:59'),(100,37,'SEED-BATT-0001-01',NULL,NULL,'2027-09-27',600,111,NULL,'available','2026-09-27 12:03:59'),(101,38,'SEED-BATT-0002-01',NULL,NULL,'2027-09-27',550,111,NULL,'available','2026-09-27 12:03:59'),(102,39,'SEED-BATT-0003-01',NULL,NULL,'2027-09-27',200,111,NULL,'available','2026-09-27 12:03:59'),(103,40,'SEED-BATT-0004-01',NULL,NULL,'2027-09-27',150,111,NULL,'available','2026-09-27 12:03:59'),(104,41,'SEED-BATT-0005-01',NULL,NULL,'2027-09-27',300,111,NULL,'available','2026-09-27 12:03:59'),(105,42,'SEED-HARD-0001-01',NULL,NULL,'2027-09-27',500,120,NULL,'available','2026-09-27 12:03:59'),(106,43,'SEED-HARD-0002-01',NULL,NULL,'2027-09-27',410,120,NULL,'available','2026-09-27 12:03:59'),(107,44,'SEED-HARD-0003-01',NULL,NULL,'2027-09-27',350,120,NULL,'available','2026-09-27 12:03:59'),(108,45,'SEED-HARD-0004-01',NULL,NULL,'2027-09-27',400,120,NULL,'available','2026-09-27 12:03:59'),(109,46,'SEED-HARD-0005-01',NULL,NULL,'2027-09-27',300,120,NULL,'available','2026-09-27 12:03:59'),(110,57,'SEED-PACK-0001-01',NULL,NULL,'2027-09-27',500,113,NULL,'available','2026-09-27 12:03:59'),(111,58,'SEED-PACK-0002-01',NULL,NULL,'2027-09-27',600,113,NULL,'available','2026-09-27 12:03:59'),(112,59,'SEED-PACK-0003-01',NULL,NULL,'2027-09-27',800,109,NULL,'available','2026-09-27 12:03:59'),(113,60,'SEED-PACK-0004-01',NULL,NULL,'2027-09-27',200,112,NULL,'available','2026-09-27 12:03:59'),(114,61,'SEED-PACK-0005-01',NULL,NULL,'2027-09-27',400,109,NULL,'available','2026-09-27 12:03:59'),(115,62,'SEED-PACK-0006-01',NULL,NULL,'2027-09-27',250,119,NULL,'available','2026-09-27 12:03:59'),(116,63,'SEED-SAFE-0001-01',NULL,NULL,'2027-09-27',300,109,NULL,'available','2026-09-27 12:03:59'),(117,64,'SEED-SAFE-0002-01',NULL,NULL,'2027-09-27',200,109,NULL,'available','2026-09-27 12:03:59'),(118,65,'SEED-SAFE-0003-01',NULL,NULL,'2027-09-27',100,115,NULL,'available','2026-09-27 12:03:59'),(119,66,'SEED-SAFE-0004-01',NULL,NULL,'2027-09-27',150,119,NULL,'available','2026-09-27 12:03:59'),(120,67,'SEED-SAFE-0005-01',NULL,NULL,'2027-09-27',180,109,NULL,'available','2026-09-27 12:03:59'),(121,68,'SEED-TOOL-0008-01',NULL,NULL,'2027-09-27',100,120,NULL,'available','2026-09-27 12:03:59'),(122,69,'SEED-TOOL-0009-01',NULL,NULL,'2027-09-27',110,120,NULL,'available','2026-09-27 12:03:59'),(123,70,'SEED-TOOL-0010-01',NULL,NULL,'2027-09-27',250,120,NULL,'available','2026-09-27 12:03:59'),(124,71,'SEED-ELEC-0009-01',NULL,NULL,'2027-09-27',130,111,NULL,'available','2026-09-27 12:03:59'),(125,72,'SEED-ELEC-0010-01',NULL,NULL,'2027-09-27',400,111,NULL,'available','2026-09-27 12:03:59'),(126,73,'SEED-ELEC-0011-01',NULL,NULL,'2027-09-27',100,111,NULL,'available','2026-09-27 12:03:59'),(127,74,'SEED-ELEC-0012-01',NULL,NULL,'2027-09-27',140,111,NULL,'available','2026-09-27 12:03:59'),(128,75,'SEED-ELEC-0013-01',NULL,NULL,'2027-09-27',350,111,NULL,'available','2026-09-27 12:03:59'),(129,76,'SEED-ELEC-0014-01',NULL,NULL,'2027-09-27',100,111,NULL,'available','2026-09-27 12:03:59'),(130,77,'SEED-ELEC-0015-01',NULL,NULL,'2027-09-27',200,111,NULL,'available','2026-09-27 12:03:59'),(131,78,'SEED-ELEC-0016-01',NULL,NULL,'2027-09-27',300,111,NULL,'available','2026-09-27 12:03:59'),(132,79,'SEED-ELEC-0017-01',NULL,NULL,'2027-09-27',150,109,NULL,'available','2026-09-27 12:03:59'),(133,80,'SEED-OFFI-0006-01',NULL,NULL,'2027-09-27',180,109,NULL,'available','2026-09-27 12:03:59'),(134,81,'SEED-OFFI-0007-01',NULL,NULL,'2027-09-27',100,112,NULL,'available','2026-09-27 12:03:59'),(135,82,'SEED-OFFI-0008-01',NULL,NULL,'2027-09-27',300,109,NULL,'available','2026-09-27 12:03:59'),(136,83,'SEED-OFFI-0009-01',NULL,NULL,'2027-09-27',500,109,NULL,'available','2026-09-27 12:03:59'),(137,84,'SEED-OFFI-0010-01',NULL,NULL,'2027-09-27',400,109,NULL,'available','2026-09-27 12:03:59'),(138,85,'SEED-OFFI-0011-01',NULL,NULL,'2027-09-27',120,109,NULL,'available','2026-09-27 12:03:59'),(139,86,'SEED-OFFI-0012-01',NULL,NULL,'2027-09-27',100,109,NULL,'available','2026-09-27 12:03:59'),(140,87,'SEED-FURN-0007-01',NULL,NULL,'2027-09-27',100,113,NULL,'available','2026-09-27 12:03:59'),(141,88,'SEED-FURN-0008-01',NULL,NULL,'2027-09-27',100,113,NULL,'available','2026-09-27 12:03:59'),(142,89,'SEED-FURN-0009-01',NULL,NULL,'2027-09-27',100,113,NULL,'available','2026-09-27 12:03:59'),(143,90,'SEED-FURN-0010-01',NULL,NULL,'2027-09-27',120,113,NULL,'available','2026-09-27 12:03:59'),(144,91,'SEED-OFFI-0013-01',NULL,NULL,'2027-09-27',100,112,NULL,'available','2026-09-27 12:03:59'),(145,92,'SEED-SAFE-0006-01',NULL,NULL,'2027-09-27',200,115,NULL,'available','2026-09-27 12:03:59'),(146,93,'SEED-SAFE-0007-01',NULL,NULL,'2027-09-27',100,115,NULL,'available','2026-09-27 12:03:59'),(147,94,'SEED-SAFE-0008-01',NULL,NULL,'2027-09-27',250,119,NULL,'available','2026-09-27 12:03:59'),(148,20,'PO12-20-20260928',NULL,NULL,NULL,300,36,NULL,'available','2026-09-28 10:05:31'),(149,24,'PO12-24-20260928',NULL,NULL,NULL,150,285,NULL,'available','2026-09-28 10:05:31'),(150,25,'PO12-25-20260928',NULL,NULL,NULL,100,261,NULL,'available','2026-09-28 10:05:31'),(151,30,'PO12-30-20260928',NULL,NULL,NULL,150,237,NULL,'available','2026-09-28 10:05:31');
/*!40000 ALTER TABLE `product_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `reorder_point` int(11) DEFAULT 10,
  `status` enum('active','discontinued','pending') DEFAULT 'active',
  `unit_price` decimal(10,2) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location` varchar(255) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `fk_category` (`category_id`),
  KEY `fk_supplier` (`supplier_id`),
  KEY `warehouse_id` (`warehouse_id`),
  CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_3` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Wireless Mouse','Electronics','ELEC-0001','6410000000001','Ergonomic wireless mouse',1,1,250,50,'active',24.99,0.10,'10x6x4 cm','2026-09-25 11:55:42','2026-09-29 12:43:12','Zone G - Electronics',7,7),(2,'USB-C Cable 2m','Electronics','ELEC-0002','6410000000002','USB-C to USB-C fast charge',1,1,500,100,'active',8.99,0.05,'200x1x1 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(3,'HDMI Cable 3m','Electronics','ELEC-0003','6410000000003','High-speed HDMI cable',1,1,300,60,'active',12.50,0.15,'300x1x1 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(4,'Wireless Keyboard','Electronics','ELEC-0004','6410000000004','Full-size wireless keyboard',1,1,180,36,'active',39.99,0.65,'45x15x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(5,'27 inch Monitor','Electronics','ELEC-0005','6410000000005','FHD 27 inch LED monitor',1,1,45,10,'active',189.00,4.50,'65x45x15 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(6,'Bluetooth Speaker','Electronics','ELEC-0006','6410000000006','Portable Bluetooth speaker',1,1,120,24,'active',45.00,0.50,'18x8x8 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(7,'Webcam 1080p','Electronics','ELEC-0007','6410000000007','Full HD webcam',1,1,90,18,'active',59.00,0.20,'10x5x5 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(8,'Power Adapter 65W','Electronics','ELEC-0008','6410000000008','65W USB-C power adapter',1,1,200,40,'active',34.99,0.30,'8x8x4 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(9,'Office Chair Ergonomic','Furniture','FURN-0001','6410000000101','Adjustable ergonomic office chair',2,2,35,10,'active',249.00,14.00,'60x60x120 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(10,'Standing Desk 140cm','Furniture','FURN-0002','6410000000102','Electric standing desk',2,2,20,10,'active',549.00,35.00,'140x70x10 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(11,'Bookshelf 5-shelf','Furniture','FURN-0003','6410000000103','Wooden 5-shelf bookcase',2,2,25,10,'active',129.00,22.00,'80x30x180 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(12,'Filing Cabinet 4-drawer','Furniture','FURN-0004','6410000000104','Steel filing cabinet',2,2,40,10,'active',179.00,28.00,'50x60x130 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(13,'Meeting Table','Furniture','FURN-0005','6410000000105','Conference meeting table',2,2,8,10,'active',899.00,55.00,'240x110x75 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(14,'Warehouse Pallets','Furniture','FURN-0006','6410000000106','Wooden pallet pack of 5',2,2,200,40,'active',45.00,30.00,'120x80x15 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(15,'Staff Polo Shirt','Apparel','APPA-0001','6410000000201','Cotton polo uniform shirt',3,4,300,60,'active',19.99,0.20,'M/L/XL','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(16,'Safety Vest','Apparel','APPA-0002','6410000000202','High-visibility safety vest',3,4,450,90,'active',12.99,0.15,'One size','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(17,'Work Gloves','Apparel','APPA-0003','6410000000203','Protective work gloves pair',3,4,600,120,'active',6.99,0.08,'M/L/XL','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(18,'Safety Helmet','Apparel','APPA-0004','6410000000204','ABS safety helmet',3,4,150,30,'active',22.00,0.35,'Adjustable','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(19,'Winter Jacket','Apparel','APPA-0005','6410000000205','Insulated winter work jacket',3,4,80,16,'active',89.00,1.20,'M/L/XL','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(20,'Steel Ruler 30cm','Tools & Equipment','TOOL-0001','6410000000301','Stainless steel ruler',4,3,350,70,'active',4.50,0.10,'30x3x0.2 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(21,'Measuring Tape 5m','Tools & Equipment','TOOL-0002','6410000000302','Retractable measuring tape',4,3,280,56,'active',6.99,0.15,'7x7x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(22,'Hammer 500g','Tools & Equipment','TOOL-0003','6410000000303','Claw hammer steel head',4,3,150,30,'active',15.50,0.60,'30x12x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(23,'Screwdriver Set 12pcs','Tools & Equipment','TOOL-0004','6410000000304','Precision screwdriver set',4,3,220,44,'active',24.99,0.40,'20x12x4 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(24,'Adjustable Wrench','Tools & Equipment','TOOL-0005','6410000000305','Adjustable wrench 250mm',4,3,180,36,'active',12.99,0.35,'25x6x2 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(25,'Cordless Drill 18V','Tools & Equipment','TOOL-0006','6410000000306','18V cordless drill with battery',4,3,65,13,'active',149.00,2.20,'30x25x10 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(26,'Crowbar 60cm','Tools & Equipment','TOOL-0007','6410000000307','Steel crowbar 60cm',4,3,90,18,'active',18.50,1.50,'60x5x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(27,'A4 Paper 500 sheets','Office Supplies','OFFI-0001','6410000000401','A4 white copy paper ream',5,7,800,160,'active',5.99,2.50,'30x21x5 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(28,'Ballpoint Pens 50pk','Office Supplies','OFFI-0002','6410000000402','Blue ballpoint pens pack',5,7,500,100,'active',12.99,0.30,'15x8x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(29,'Sticky Notes 12pk','Office Supplies','OFFI-0003','6410000000403','Assorted sticky notes',5,7,400,80,'active',8.50,0.25,'10x10x4 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(30,'Stapler Heavy Duty','Office Supplies','OFFI-0004','6410000000404','Heavy duty stapler',5,7,150,30,'active',15.99,0.50,'20x8x6 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(31,'Binder Clips 100pk','Office Supplies','OFFI-0005','6410000000405','Assorted binder clips',5,7,350,70,'active',7.99,0.20,'12x8x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(32,'Floor Cleaner 5L','Cleaning Supplies','CLEA-0001','6410000000501','Multi-surface floor cleaner',6,4,200,40,'active',14.99,5.20,'30x20x20 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone D - Aisle 3',4,4),(33,'Glass Cleaner 1L','Cleaning Supplies','CLEA-0002','6410000000502','Streak-free glass cleaner',6,4,250,50,'active',6.50,1.10,'25x10x10 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone D - Aisle 3',4,4),(34,'Disinfectant Spray 750ml','Cleaning Supplies','CLEA-0003','6410000000503','Antibacterial disinfectant spray',6,4,400,80,'active',8.99,0.85,'25x8x8 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone D - Aisle 3',4,4),(35,'Mop and Bucket Set','Cleaning Supplies','CLEA-0004','6410000000504','Complete mop and bucket',6,4,120,24,'active',24.99,3.50,'40x30x30 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone D - Aisle 3',4,4),(36,'Microfiber Cloths 10pk','Cleaning Supplies','CLEA-0005','6410000000505','Reusable microfiber cloths',6,4,300,60,'active',9.99,0.40,'25x25x5 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone D - Aisle 3',4,4),(37,'AA Batteries 24pk','Batteries & Power','BATT-0001','6410000000601','Alkaline AA batteries',7,1,600,120,'active',12.99,0.60,'12x8x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(38,'AAA Batteries 24pk','Batteries & Power','BATT-0002','6410000000602','Alkaline AAA batteries',7,1,550,110,'active',12.99,0.50,'12x8x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(39,'9V Batteries 4pk','Batteries & Power','BATT-0003','6410000000603','Alkaline 9V batteries',7,1,200,40,'active',9.99,0.30,'10x6x2 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(40,'Power Bank 20000mAh','Batteries & Power','BATT-0004','6410000000604','Dual USB power bank',7,1,150,30,'active',44.99,0.45,'15x8x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(41,'Rechargeable Batteries AA','Batteries & Power','BATT-0005','6410000000605','NiMH rechargeable AA',7,1,300,60,'active',22.99,0.80,'12x8x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(42,'Steel Nails 5cm 1kg','Hardware','HARD-0001','6410000000701','Galvanized steel nails',8,3,500,100,'active',8.50,1.05,'15x15x8 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(43,'Wood Screws 4x40mm 200pk','Hardware','HARD-0002','6410000000702','Wood screws pack',8,3,450,90,'active',12.99,0.85,'12x10x5 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(44,'Bolts and Nuts M10 100pk','Hardware','HARD-0003','6410000000703','Stainless bolts with nuts',8,3,350,70,'active',18.50,1.20,'15x12x6 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(45,'Washers Assorted 500pk','Hardware','HARD-0004','6410000000704','Assorted washers',8,3,400,80,'active',9.99,0.70,'12x10x5 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(46,'Anchors Plastic 100pk','Hardware','HARD-0005','6410000000705','Wall anchors plastic',8,3,300,60,'active',7.99,0.40,'10x8x4 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(47,'Coffee Beans 1kg','Food & Beverage','FOOD-0001','6410000000801','Roasted coffee beans',9,4,150,30,'active',19.99,1.05,'25x15x10 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone E - Cold Storage',5,5),(48,'Tea Bags 100pk','Food & Beverage','FOOD-0002','6410000000802','Black tea bags',9,4,200,40,'active',8.99,0.35,'15x10x8 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone E - Cold Storage',5,5),(49,'Bottled Water 24pk','Food & Beverage','FOOD-0003','6410000000803','Still mineral water 0.5L',9,4,400,80,'active',12.99,13.00,'40x30x25 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(50,'Energy Bars 20pk','Food & Beverage','FOOD-0004','6410000000804','Assorted energy bars',9,4,300,60,'active',24.99,1.00,'20x15x10 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone E - Cold Storage',5,5),(51,'Instant Noodles 40pk','Food & Beverage','FOOD-0005','6410000000805','Chicken flavour noodles',9,4,250,50,'active',16.99,3.20,'40x30x20 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone E - Cold Storage',5,5),(52,'First Aid Kit','Medical Supplies','MEDI-0001','6410000000901','Complete home/office first aid',10,5,100,20,'active',34.99,1.20,'30x20x10 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(53,'Face Masks 50pk','Medical Supplies','MEDI-0002','6410000000902','Disposable face masks',10,5,700,140,'active',9.99,0.30,'20x12x8 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(54,'Hand Sanitizer 500ml','Medical Supplies','MEDI-0003','6410000000903','70% alcohol hand sanitizer',10,5,400,80,'active',6.99,0.55,'20x8x8 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(55,'Bandages Assorted 50pk','Medical Supplies','MEDI-0004','6410000000904','Adhesive bandages pack',10,5,500,100,'active',5.99,0.20,'15x10x5 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(56,'Thermometer Digital','Medical Supplies','MEDI-0005','6410000000905','Digital body thermometer',10,5,200,40,'active',12.99,0.10,'15x5x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(57,'Cardboard Boxes Large 20pk','Packaging','PACK-0001','6410000001001','60x40x40cm shipping boxes',11,6,500,100,'active',22.99,5.00,'60x40x40 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(58,'Cardboard Boxes Medium 25pk','Packaging','PACK-0002','6410000001002','40x30x30cm shipping boxes',11,6,600,120,'active',18.99,4.00,'40x30x30 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(59,'Packing Tape 6pk','Packaging','PACK-0003','6410000001003','Heavy duty packing tape',11,6,800,160,'active',8.99,1.20,'10x10x6 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(60,'Bubble Wrap 50m Roll','Packaging','PACK-0004','6410000001004','Protective bubble wrap',11,6,200,40,'active',15.99,2.50,'50x20x20 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone H - Fragile',8,8),(61,'Shipping Labels 500pk','Packaging','PACK-0005','6410000001005','Self-adhesive shipping labels',11,6,400,80,'active',12.99,0.30,'15x10x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(62,'Stretch Wrap Film','Packaging','PACK-0006','6410000001006','Pallet stretch wrap 500mm',11,6,250,50,'active',9.99,2.00,'50x15x15 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone D - Aisle 3',4,4),(63,'Safety Goggles','Safety Equipment','SAFE-0001','6410000001101','Anti-fog safety goggles',12,8,300,60,'active',14.99,0.15,'18x8x6 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(64,'Ear Protection Muffs','Safety Equipment','SAFE-0002','6410000001102','Noise-reduction ear muffs',12,8,200,40,'active',18.99,0.30,'20x15x10 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(65,'Fire Extinguisher 6kg','Safety Equipment','SAFE-0003','6410000001103','ABC dry powder extinguisher',12,8,80,16,'active',49.99,9.50,'50x20x20 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone F - Hazardous',6,6),(66,'Warning Cones 10pk','Safety Equipment','SAFE-0004','6410000001104','Orange traffic cones',12,8,150,30,'active',34.99,5.00,'40x30x30 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone D - Aisle 3',4,4),(67,'Reflective Tape 50m','Safety Equipment','SAFE-0005','6410000001105','High-visibility reflective tape',12,8,180,36,'active',12.99,0.50,'15x15x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(68,'Toolbox Metal 60cm','Tools & Equipment','TOOL-0008','6410000000308','Metal toolbox with lock',4,3,70,14,'active',39.99,3.00,'60x25x25 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(69,'Level 60cm','Tools & Equipment','TOOL-0009','6410000000309','Aluminium spirit level',4,3,110,22,'active',16.99,0.45,'60x6x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(70,'Utility Knife','Tools & Equipment','TOOL-0010','6410000000310','Retractable utility knife',4,3,250,50,'active',8.99,0.15,'18x4x2 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone C - Aisle 2',3,3),(71,'LED Desk Lamp','Electronics','ELEC-0009','6410000000009','Adjustable LED desk lamp',1,1,130,26,'active',34.99,0.90,'40x15x15 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(72,'USB Flash Drive 64GB','Electronics','ELEC-0010','6410000000010','USB 3.0 flash drive 64GB',1,1,400,80,'active',12.99,0.02,'5x2x1 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(73,'External SSD 500GB','Electronics','ELEC-0011','6410000000011','Portable external SSD',1,1,80,16,'active',79.99,0.10,'10x6x1 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(74,'Wired Headphones','Electronics','ELEC-0012','6410000000012','Over-ear wired headphones',1,1,140,28,'active',39.99,0.30,'20x18x8 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(75,'Ethernet Cable 5m','Electronics','ELEC-0013','6410000000013','Cat6 Ethernet cable',1,1,350,70,'active',9.99,0.20,'500x1x1 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(76,'Wireless Router','Electronics','ELEC-0014','6410000000014','Dual band wireless router',1,1,60,12,'active',89.00,0.60,'25x18x6 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(77,'Extension Cord 5m','Electronics','ELEC-0015','6410000000015','4-socket extension cord',1,1,200,40,'active',14.99,0.80,'30x15x6 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(78,'Smartphone Stand','Electronics','ELEC-0016','6410000000016','Aluminium phone holder',1,1,300,60,'active',12.99,0.20,'12x8x10 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone G - Electronics',7,7),(79,'Laptop Sleeve 15in','Electronics','ELEC-0017','6410000000017','Padded laptop sleeve',1,1,150,30,'active',24.99,0.35,'40x30x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(80,'Desk Organizer','Office Supplies','OFFI-0006','6410000000406','Multi-compartment desk organizer',5,7,180,36,'active',19.99,0.60,'25x15x15 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(81,'Whiteboard 90x60','Office Supplies','OFFI-0007','6410000000407','Magnetic whiteboard',5,7,40,10,'active',59.00,3.50,'90x60x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone H - Fragile',8,8),(82,'Printer Paper A3','Office Supplies','OFFI-0008','6410000000408','A3 paper ream 500 sheets',5,7,300,60,'active',11.99,5.00,'42x30x5 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(83,'Envelopes 200pk','Office Supplies','OFFI-0009','6410000000409','C4 white envelopes',5,7,500,100,'active',8.99,0.80,'25x18x5 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(84,'File Folders 50pk','Office Supplies','OFFI-0010','6410000000410','A4 file folders assorted',5,7,400,80,'active',12.99,1.50,'30x22x8 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(85,'Calculator Scientific','Office Supplies','OFFI-0011','6410000000411','Scientific calculator',5,7,120,24,'active',24.99,0.20,'18x8x2 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(86,'Label Maker','Office Supplies','OFFI-0012','6410000000412','Portable label printer',5,7,60,12,'active',49.99,0.50,'20x15x8 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(87,'Desk Chair Mat','Furniture','FURN-0007','6410000000107','Plastic floor protector mat',2,2,100,20,'active',34.99,2.50,'120x90x1 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(88,'Lockers 3-door','Furniture','FURN-0008','6410000000108','Steel 3-door locker',2,2,30,10,'active',189.00,25.00,'90x45x180 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(89,'Storage Shelving Unit','Furniture','FURN-0009','6410000000109','5-tier metal shelving',2,2,50,10,'active',79.00,15.00,'90x45x180 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(90,'Anti-Fatigue Mat','Furniture','FURN-0010','6410000000110','Ergonomic floor mat',2,2,120,24,'active',39.99,1.80,'90x60x2 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone I - Bulk Floor',9,9),(91,'Paper Shredder','Office Supplies','OFFI-0013','6410000000413','Cross-cut paper shredder',5,7,45,10,'active',89.00,6.50,'40x30x55 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone H - Fragile',8,8),(92,'Fire Blanket','Safety Equipment','SAFE-0006','6410000001106','Fire retardant blanket',12,8,200,40,'active',15.99,0.60,'25x15x8 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone F - Hazardous',6,6),(93,'Lockout Tagout Kit','Safety Equipment','SAFE-0007','6410000001107','LOTO safety kit',12,8,40,10,'active',79.00,1.50,'30x25x10 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone F - Hazardous',6,6),(94,'Barrier Tape 500m','Safety Equipment','SAFE-0008','6410000001108','Caution barrier tape',12,8,250,50,'active',7.99,0.40,'15x15x5 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone D - Aisle 3',4,4),(95,'Broom Heavy Duty','Cleaning Supplies','CLEA-0006','6410000000506','Industrial broom',6,4,180,36,'active',18.99,1.50,'140x30x10 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone D - Aisle 3',4,4),(96,'Trash Bags 100pk','Cleaning Supplies','CLEA-0007','6410000000507','Heavy duty trash bags',6,4,600,120,'active',15.99,2.50,'30x25x15 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(97,'Pressure Washer','Cleaning Supplies','CLEA-0008','6410000000508','Electric pressure washer',6,4,25,10,'active',179.00,12.00,'45x35x50 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone D - Aisle 3',4,4),(98,'Floor Sign - Wet','Cleaning Supplies','CLEA-0009','6410000000509','Caution wet floor sign',6,4,200,40,'active',6.99,0.70,'30x25x3 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone B - Aisle 1',2,2),(99,'Vacuum Cleaner Industrial','Cleaning Supplies','CLEA-0010','6410000000510','Wet/dry industrial vacuum',6,4,30,10,'active',249.00,15.00,'60x50x70 cm','2026-09-25 11:55:42','2026-09-25 12:21:10','Zone D - Aisle 3',4,4),(100,'Dustpan and Brush Set','Cleaning Supplies','CLEA-0011','6410000000511','Dustpan with brush set',6,4,250,50,'active',7.99,0.45,'30x25x5 cm','2026-09-25 11:56:58','2026-09-25 12:21:10','Zone D - Aisle 3',4,4);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (1,1,1,NULL,NULL,500,18.00),(2,1,2,NULL,NULL,1000,6.50),(3,1,4,NULL,NULL,300,28.00),(4,1,5,NULL,NULL,100,150.00),(5,2,9,NULL,NULL,50,200.00),(6,2,10,NULL,NULL,30,450.00),(7,2,11,NULL,NULL,40,100.00),(8,2,12,NULL,NULL,60,150.00),(9,3,20,NULL,NULL,500,3.50),(10,3,21,NULL,NULL,400,5.00),(11,3,22,NULL,NULL,200,12.00),(12,3,23,NULL,NULL,300,20.00),(13,4,15,NULL,NULL,500,15.00),(14,4,16,NULL,NULL,700,10.00),(15,4,17,NULL,NULL,900,5.50),(16,4,18,NULL,NULL,250,18.00),(17,5,26,NULL,NULL,1000,4.50),(18,5,27,NULL,NULL,700,10.00),(19,5,28,NULL,NULL,500,6.50),(20,5,29,NULL,NULL,200,12.00),(21,6,31,NULL,NULL,300,11.00),(22,6,32,NULL,NULL,400,5.00),(23,6,33,NULL,NULL,500,7.50),(24,6,34,NULL,NULL,200,20.00),(25,7,36,NULL,NULL,800,10.00),(26,7,37,NULL,NULL,800,10.00),(27,7,38,NULL,NULL,400,9.00),(28,7,39,NULL,NULL,250,8.00),(29,8,41,NULL,NULL,900,8.50),(30,8,42,NULL,NULL,800,10.00),(31,8,43,NULL,NULL,600,15.00),(32,8,44,NULL,NULL,600,12.00),(33,9,51,NULL,NULL,200,15.00),(34,9,52,NULL,NULL,500,8.00),(35,9,53,NULL,NULL,600,5.50),(36,9,54,NULL,NULL,700,4.50),(37,10,61,NULL,NULL,700,18.00),(38,10,62,NULL,NULL,900,15.00),(39,10,63,NULL,NULL,700,7.00),(40,10,64,NULL,NULL,800,10.00),(41,11,1,NULL,NULL,300,22.00),(42,11,6,NULL,NULL,200,40.00),(43,11,7,NULL,NULL,150,50.00),(44,11,8,NULL,NULL,300,30.00),(45,12,20,'PO12-20-20260928',NULL,300,3.50),(46,12,24,'PO12-24-20260928',NULL,150,12.00),(47,12,25,'PO12-25-20260928',NULL,100,18.00),(48,12,30,'PO12-30-20260928',NULL,150,12.00);
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `expected_date` date DEFAULT NULL,
  `status` enum('pending','received','cancelled') DEFAULT 'pending',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `created_by` (`created_by`),
  KEY `warehouse_id` (`warehouse_id`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,1,1,'2026-08-01','2026-08-10','received',38900.00,NULL,'2026-08-10 00:00:00',6),(2,2,2,'2026-08-05','2026-08-15','received',36500.00,NULL,'2026-08-15 00:00:00',6),(3,3,3,'2026-08-12','2026-08-22','received',12150.00,NULL,'2026-08-22 00:00:00',6),(4,4,4,'2026-08-20','2026-08-30','received',23950.00,NULL,'2026-08-30 00:00:00',6),(5,5,5,'2026-09-01','2026-09-10','received',17150.00,NULL,'2026-09-10 00:00:00',6),(6,6,6,'2026-09-05','2026-09-15','received',13050.00,NULL,'2026-09-15 00:00:00',6),(7,7,7,'2026-09-10','2026-09-20','pending',21600.00,NULL,NULL,6),(8,8,8,'2026-09-12','2026-09-22','pending',31850.00,NULL,NULL,6),(9,1,9,'2026-09-15','2026-09-25','pending',13450.00,NULL,NULL,6),(10,3,10,'2026-09-18','2026-09-28','pending',39000.00,NULL,NULL,6),(11,2,11,'2026-09-20','2026-09-30','pending',31100.00,NULL,NULL,6),(12,4,12,'2026-09-21','2026-10-01','received',6450.00,NULL,'2026-09-28 13:05:31',6),(13,5,1,'2026-09-22','2026-10-02','cancelled',0.00,NULL,NULL,6),(14,6,2,'2026-09-23','2026-10-03','pending',0.00,NULL,NULL,6),(15,7,3,'2026-09-24','2026-10-04','pending',0.00,NULL,NULL,6);
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `shift_type` enum('morning','evening','night') NOT NULL,
  `starts_at` time NOT NULL,
  `ends_at` time NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_date` (`user_id`,`shift_date`),
  KEY `idx_date` (`shift_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts`
--

LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
INSERT INTO `shifts` VALUES (1,9,'2026-10-01','morning','06:00:00','14:00:00','Helsinki picking',7,'2026-10-01 08:58:33');
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipment_items`
--

DROP TABLE IF EXISTS `shipment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipment_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shipment_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipment_id` (`shipment_id`),
  KEY `product_id` (`product_id`),
  KEY `batch_id` (`batch_id`),
  CONSTRAINT `shipment_items_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shipment_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shipment_items_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipment_items`
--

LOCK TABLES `shipment_items` WRITE;
/*!40000 ALTER TABLE `shipment_items` DISABLE KEYS */;
INSERT INTO `shipment_items` VALUES (1,1,1,NULL,20),(2,1,2,NULL,50),(3,1,6,NULL,10),(4,2,9,NULL,5),(5,2,10,NULL,3),(6,2,14,NULL,20),(7,3,15,NULL,50),(8,3,16,NULL,70),(9,3,17,NULL,100),(10,4,26,NULL,100),(11,4,27,NULL,80),(12,4,30,NULL,30),(13,5,31,NULL,40),(14,5,32,NULL,50),(15,5,35,NULL,30),(16,6,36,NULL,80),(17,6,37,NULL,80),(18,6,38,NULL,40),(19,7,41,NULL,100),(20,7,42,NULL,80),(21,7,44,NULL,60),(22,8,51,NULL,30),(23,8,52,NULL,60),(24,8,53,NULL,80),(25,9,61,NULL,80),(26,9,62,NULL,100),(27,9,64,NULL,80),(28,10,1,NULL,30),(29,10,4,NULL,20),(30,10,6,NULL,15),(31,11,20,88,30),(32,11,21,89,40),(33,12,31,NULL,20),(34,17,33,NULL,30),(35,13,41,NULL,30),(36,18,43,106,40),(37,14,51,NULL,50),(38,16,53,NULL,60),(39,15,1,NULL,25),(40,15,6,NULL,10),(41,15,8,NULL,20),(42,15,1,69,25),(43,15,6,74,10),(44,15,8,76,20);
/*!40000 ALTER TABLE `shipment_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipments`
--

DROP TABLE IF EXISTS `shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `shipped_by` int(11) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `shipment_date` date DEFAULT NULL,
  `status` enum('pending','shipped','delivered') DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipped_by` (`shipped_by`),
  KEY `warehouse_id` (`warehouse_id`),
  CONSTRAINT `shipments_ibfk_1` FOREIGN KEY (`shipped_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipments`
--

LOCK TABLES `shipments` WRITE;
/*!40000 ALTER TABLE `shipments` DISABLE KEYS */;
INSERT INTO `shipments` VALUES (1,'Helsinki Logistics Oy','Mannerheimintie 12, Helsinki',6,1,'2026-08-05','delivered',NULL,NULL,'2026-08-05 00:00:00'),(2,'Espoo Retail Group','Keilaranta 5, Espoo',6,1,'2026-08-12','delivered',NULL,NULL,'2026-08-12 00:00:00'),(3,'Vantaa Distribution AB','Tikkurilantie 100, Vantaa',6,1,'2026-08-20','delivered',NULL,NULL,'2026-08-20 00:00:00'),(4,'Turku Supply Co','Kauppatori 1, Turku',6,1,'2026-08-28','delivered',NULL,NULL,'2026-08-28 00:00:00'),(5,'Tampere Wholesale','Hämeenkatu 20, Tampere',6,1,'2026-09-05','delivered',NULL,NULL,'2026-09-05 00:00:00'),(6,'Oulu Trading Ltd','Kirkkokatu 15, Oulu',6,1,'2026-09-10','delivered',NULL,NULL,'2026-09-10 00:00:00'),(7,'Jyväskylä Retail Oy','Kauppakatu 30, Jyväskylä',6,1,'2026-09-15','shipped',NULL,NULL,'2026-09-15 00:00:00'),(8,'Kuopio Supplies','Puijonkatu 25, Kuopio',6,1,'2026-09-18','shipped',NULL,NULL,'2026-09-18 00:00:00'),(9,'Lahti Distribution','Vesijärvenkatu 10, Lahti',6,1,'2026-09-20','shipped',NULL,NULL,'2026-09-20 00:00:00'),(10,'Pori Wholesale Oy','Yrjönkatu 12, Pori',6,1,'2026-09-22','shipped',NULL,NULL,'2026-09-22 00:00:00'),(11,'Kouvola Retail','Keskuskatu 8, Kouvola',6,3,'2026-09-23','shipped','WH-20260928-SH11-69F4',NULL,'2026-09-28 13:20:20'),(12,'Lappeenranta Trade','Kauppakatu 40, Lappeenranta',6,2,'2026-09-24','pending',NULL,NULL,NULL),(13,'Rovaniemi Logistics','Rovakatu 3, Rovaniemi',6,7,'2026-09-24','pending',NULL,NULL,NULL),(14,'Joensuu Supply Co','Siltakatu 20, Joensuu',6,5,'2026-09-25','pending',NULL,NULL,NULL),(15,'Vaasa Wholesale AB','Hovioikeudenpuistikko 15, Vaasa',7,7,'2026-09-25','shipped',NULL,NULL,'2026-09-27 15:22:40'),(16,'Joensuu Supply Co','Siltakatu 20, Joensuu',6,2,'2026-09-25','pending',NULL,'Split from SH-14 — Espoo line',NULL),(17,'Lappeenranta Trade','Kauppakatu 40, Lappeenranta',6,4,'2026-09-24','pending',NULL,'Split from SH-12',NULL),(18,'Rovaniemi Logistics','Rovakatu 3, Rovaniemi',6,3,'2026-09-24','delivered','WH-20260930-SH18-721A','Split from SH-13','2026-09-30 13:10:54');
/*!40000 ALTER TABLE `shipments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `from_location` int(11) DEFAULT NULL,
  `to_location` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `movement_type` enum('inbound','outbound','transfer','adjustment') NOT NULL,
  `reason` text DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `approval_status` enum('auto','pending','approved','rejected') NOT NULL DEFAULT 'auto',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `from_location` (`from_location`),
  KEY `to_location` (`to_location`),
  KEY `user_id` (`user_id`),
  KEY `batch_id` (`batch_id`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`from_location`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_movements_ibfk_3` FOREIGN KEY (`to_location`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_movements_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_movements_ibfk_5` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=280 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (1,1,NULL,NULL,7,250,'inbound','Initial stock received',NULL,NULL,'2025-01-15 08:00:00',6,'auto',NULL,NULL),(2,2,NULL,NULL,7,500,'inbound','Initial stock received',NULL,NULL,'2025-01-15 08:00:00',6,'auto',NULL,NULL),(3,3,NULL,NULL,7,300,'inbound','Initial stock received',NULL,NULL,'2025-01-15 08:00:00',6,'auto',NULL,NULL),(4,4,NULL,NULL,7,180,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(5,5,NULL,NULL,7,45,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(6,6,NULL,NULL,7,120,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(7,7,NULL,NULL,7,90,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(8,8,NULL,NULL,7,200,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(9,9,NULL,NULL,9,35,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(10,10,NULL,NULL,9,20,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(11,11,NULL,NULL,9,25,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(12,12,NULL,NULL,9,40,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(13,13,NULL,NULL,9,8,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(14,14,NULL,NULL,9,200,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(15,15,NULL,NULL,2,300,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(16,16,NULL,NULL,2,450,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(17,17,NULL,NULL,2,600,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(18,18,NULL,NULL,2,150,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(19,19,NULL,NULL,2,80,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(20,20,NULL,NULL,3,350,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(21,21,NULL,NULL,3,280,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(22,22,NULL,NULL,3,150,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(23,23,NULL,NULL,3,220,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(24,24,NULL,NULL,3,180,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(25,25,NULL,NULL,3,65,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(26,26,NULL,NULL,3,90,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(27,27,NULL,NULL,2,800,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(28,28,NULL,NULL,2,500,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(29,29,NULL,NULL,2,400,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(30,30,NULL,NULL,2,150,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(31,31,NULL,NULL,2,350,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(32,32,NULL,NULL,4,200,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(33,33,NULL,NULL,4,250,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(34,34,NULL,NULL,4,400,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(35,35,NULL,NULL,4,120,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(36,36,NULL,NULL,4,300,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(37,37,NULL,NULL,7,600,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(38,38,NULL,NULL,7,550,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(39,39,NULL,NULL,7,200,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(40,40,NULL,NULL,7,150,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(41,41,NULL,NULL,7,300,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(42,42,NULL,NULL,3,500,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(43,43,NULL,NULL,3,450,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(44,44,NULL,NULL,3,350,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(45,45,NULL,NULL,3,400,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(46,46,NULL,NULL,3,300,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(47,47,NULL,NULL,5,150,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(48,48,NULL,NULL,5,200,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(49,49,NULL,NULL,9,400,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(50,50,NULL,NULL,5,300,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(51,51,NULL,NULL,5,250,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(52,52,NULL,NULL,2,100,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(53,53,NULL,NULL,2,700,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(54,54,NULL,NULL,2,400,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(55,55,NULL,NULL,2,500,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(56,56,NULL,NULL,2,200,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(57,57,NULL,NULL,9,500,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(58,58,NULL,NULL,9,600,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(59,59,NULL,NULL,2,800,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(60,60,NULL,NULL,8,200,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(61,61,NULL,NULL,2,400,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(62,62,NULL,NULL,4,250,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(63,63,NULL,NULL,2,300,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(64,64,NULL,NULL,2,200,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(65,65,NULL,NULL,6,80,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(66,66,NULL,NULL,4,150,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(67,67,NULL,NULL,2,180,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(68,68,NULL,NULL,3,70,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(69,69,NULL,NULL,3,110,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(70,70,NULL,NULL,3,250,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(71,71,NULL,NULL,7,130,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(72,72,NULL,NULL,7,400,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(73,73,NULL,NULL,7,80,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(74,74,NULL,NULL,7,140,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(75,75,NULL,NULL,7,350,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(76,76,NULL,NULL,7,60,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(77,77,NULL,NULL,7,200,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(78,78,NULL,NULL,7,300,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(79,79,NULL,NULL,2,150,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(80,80,NULL,NULL,2,180,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(81,81,NULL,NULL,8,40,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(82,82,NULL,NULL,2,300,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(83,83,NULL,NULL,2,500,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(84,84,NULL,NULL,2,400,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(85,85,NULL,NULL,2,120,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(86,86,NULL,NULL,2,60,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(87,87,NULL,NULL,9,100,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(88,88,NULL,NULL,9,30,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(89,89,NULL,NULL,9,50,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(90,90,NULL,NULL,9,120,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(91,91,NULL,NULL,8,45,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(92,92,NULL,NULL,6,200,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(93,93,NULL,NULL,6,40,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(94,94,NULL,NULL,4,250,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(95,95,NULL,NULL,4,180,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(96,96,NULL,NULL,2,600,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(97,97,NULL,NULL,4,25,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(98,98,NULL,NULL,2,200,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(99,99,NULL,NULL,4,30,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(100,100,NULL,NULL,4,250,'inbound','Initial stock received',NULL,NULL,'2026-09-25 11:58:55',6,'auto',NULL,NULL),(129,1,69,NULL,111,250,'inbound','Initial stock seed','SEED-1',NULL,'2025-01-15 08:00:00',6,'auto',NULL,NULL),(130,2,70,NULL,111,500,'inbound','Initial stock seed','SEED-2',NULL,'2025-01-15 08:00:00',6,'auto',NULL,NULL),(131,3,71,NULL,111,300,'inbound','Initial stock seed','SEED-3',NULL,'2025-01-15 08:00:00',6,'auto',NULL,NULL),(132,4,72,NULL,111,180,'inbound','Initial stock seed','SEED-4',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(133,5,73,NULL,111,100,'inbound','Initial stock seed','SEED-5',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(134,6,74,NULL,111,120,'inbound','Initial stock seed','SEED-6',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(135,7,75,NULL,111,100,'inbound','Initial stock seed','SEED-7',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(136,8,76,NULL,111,200,'inbound','Initial stock seed','SEED-8',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(137,9,77,NULL,113,100,'inbound','Initial stock seed','SEED-9',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(138,10,78,NULL,113,100,'inbound','Initial stock seed','SEED-10',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(139,11,79,NULL,113,100,'inbound','Initial stock seed','SEED-11',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(140,12,80,NULL,113,100,'inbound','Initial stock seed','SEED-12',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(141,13,81,NULL,113,100,'inbound','Initial stock seed','SEED-13',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(142,14,82,NULL,113,200,'inbound','Initial stock seed','SEED-14',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(143,15,83,NULL,109,300,'inbound','Initial stock seed','SEED-15',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(144,16,84,NULL,109,450,'inbound','Initial stock seed','SEED-16',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(145,17,85,NULL,109,600,'inbound','Initial stock seed','SEED-17',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(146,18,86,NULL,109,150,'inbound','Initial stock seed','SEED-18',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(147,19,87,NULL,109,100,'inbound','Initial stock seed','SEED-19',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(148,20,88,NULL,120,350,'inbound','Initial stock seed','SEED-20',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(149,21,89,NULL,120,280,'inbound','Initial stock seed','SEED-21',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(150,22,90,NULL,120,150,'inbound','Initial stock seed','SEED-22',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(151,23,91,NULL,120,220,'inbound','Initial stock seed','SEED-23',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(152,24,92,NULL,120,180,'inbound','Initial stock seed','SEED-24',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(153,25,93,NULL,120,100,'inbound','Initial stock seed','SEED-25',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(154,26,94,NULL,120,100,'inbound','Initial stock seed','SEED-26',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(155,27,95,NULL,109,800,'inbound','Initial stock seed','SEED-27',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(156,28,96,NULL,109,500,'inbound','Initial stock seed','SEED-28',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(157,29,97,NULL,109,400,'inbound','Initial stock seed','SEED-29',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(158,30,98,NULL,109,150,'inbound','Initial stock seed','SEED-30',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(159,31,99,NULL,109,350,'inbound','Initial stock seed','SEED-31',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(160,37,100,NULL,111,600,'inbound','Initial stock seed','SEED-37',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(161,38,101,NULL,111,550,'inbound','Initial stock seed','SEED-38',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(162,39,102,NULL,111,200,'inbound','Initial stock seed','SEED-39',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(163,40,103,NULL,111,150,'inbound','Initial stock seed','SEED-40',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(164,41,104,NULL,111,300,'inbound','Initial stock seed','SEED-41',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(165,42,105,NULL,120,500,'inbound','Initial stock seed','SEED-42',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(166,43,106,NULL,120,450,'inbound','Initial stock seed','SEED-43',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(167,44,107,NULL,120,350,'inbound','Initial stock seed','SEED-44',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(168,45,108,NULL,120,400,'inbound','Initial stock seed','SEED-45',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(169,46,109,NULL,120,300,'inbound','Initial stock seed','SEED-46',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(170,57,110,NULL,113,500,'inbound','Initial stock seed','SEED-57',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(171,58,111,NULL,113,600,'inbound','Initial stock seed','SEED-58',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(172,59,112,NULL,109,800,'inbound','Initial stock seed','SEED-59',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(173,60,113,NULL,112,200,'inbound','Initial stock seed','SEED-60',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(174,61,114,NULL,109,400,'inbound','Initial stock seed','SEED-61',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(175,62,115,NULL,119,250,'inbound','Initial stock seed','SEED-62',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(176,63,116,NULL,109,300,'inbound','Initial stock seed','SEED-63',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(177,64,117,NULL,109,200,'inbound','Initial stock seed','SEED-64',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(178,65,118,NULL,115,100,'inbound','Initial stock seed','SEED-65',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(179,66,119,NULL,119,150,'inbound','Initial stock seed','SEED-66',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(180,67,120,NULL,109,180,'inbound','Initial stock seed','SEED-67',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(181,68,121,NULL,120,100,'inbound','Initial stock seed','SEED-68',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(182,69,122,NULL,120,110,'inbound','Initial stock seed','SEED-69',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(183,70,123,NULL,120,250,'inbound','Initial stock seed','SEED-70',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(184,71,124,NULL,111,130,'inbound','Initial stock seed','SEED-71',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(185,72,125,NULL,111,400,'inbound','Initial stock seed','SEED-72',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(186,73,126,NULL,111,100,'inbound','Initial stock seed','SEED-73',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(187,74,127,NULL,111,140,'inbound','Initial stock seed','SEED-74',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(188,75,128,NULL,111,350,'inbound','Initial stock seed','SEED-75',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(189,76,129,NULL,111,100,'inbound','Initial stock seed','SEED-76',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(190,77,130,NULL,111,200,'inbound','Initial stock seed','SEED-77',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(191,78,131,NULL,111,300,'inbound','Initial stock seed','SEED-78',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(192,79,132,NULL,109,150,'inbound','Initial stock seed','SEED-79',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(193,80,133,NULL,109,180,'inbound','Initial stock seed','SEED-80',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(194,81,134,NULL,112,100,'inbound','Initial stock seed','SEED-81',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(195,82,135,NULL,109,300,'inbound','Initial stock seed','SEED-82',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(196,83,136,NULL,109,500,'inbound','Initial stock seed','SEED-83',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(197,84,137,NULL,109,400,'inbound','Initial stock seed','SEED-84',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(198,85,138,NULL,109,120,'inbound','Initial stock seed','SEED-85',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(199,86,139,NULL,109,100,'inbound','Initial stock seed','SEED-86',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(200,87,140,NULL,113,100,'inbound','Initial stock seed','SEED-87',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(201,88,141,NULL,113,100,'inbound','Initial stock seed','SEED-88',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(202,89,142,NULL,113,100,'inbound','Initial stock seed','SEED-89',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(203,90,143,NULL,113,120,'inbound','Initial stock seed','SEED-90',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(204,91,144,NULL,112,100,'inbound','Initial stock seed','SEED-91',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(205,92,145,NULL,115,200,'inbound','Initial stock seed','SEED-92',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(206,93,146,NULL,115,100,'inbound','Initial stock seed','SEED-93',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(207,94,147,NULL,119,250,'inbound','Initial stock seed','SEED-94',NULL,'2026-09-27 12:04:55',6,'auto',NULL,NULL),(256,1,69,111,NULL,25,'outbound','Picked for shipment SH-15','SH-15',NULL,'2025-01-15 08:00:00',7,'auto',NULL,NULL),(257,6,74,111,NULL,10,'outbound','Picked for shipment SH-15','SH-15',NULL,'2026-09-27 12:22:40',7,'auto',NULL,NULL),(258,8,76,111,NULL,20,'outbound','Picked for shipment SH-15','SH-15',NULL,'2026-09-27 12:22:40',7,'auto',NULL,NULL),(259,20,148,NULL,36,300,'inbound','Received on PO-12','PO-12-1',NULL,'2026-09-28 10:05:31',7,'auto',NULL,NULL),(260,24,149,NULL,285,150,'inbound','Received on PO-12','PO-12-2',NULL,'2026-09-28 10:05:31',7,'auto',NULL,NULL),(261,25,150,NULL,261,100,'inbound','Received on PO-12','PO-12-3',NULL,'2026-09-28 10:05:31',7,'auto',NULL,NULL),(262,30,151,NULL,237,150,'inbound','Received on PO-12','PO-12-4',NULL,'2026-09-28 10:05:31',7,'auto',NULL,NULL),(263,20,88,120,NULL,30,'outbound','Picked for shipment SH-11','SH-11-1',NULL,'2026-09-28 10:20:20',7,'auto',NULL,NULL),(264,21,89,120,NULL,40,'outbound','Picked for shipment SH-11','SH-11-2',NULL,'2026-09-28 10:20:20',7,'auto',NULL,NULL),(265,1,69,111,123,10,'transfer','Bin-to-bin transfer','TR-2026-00001',NULL,'2025-01-15 08:00:00',7,'auto',NULL,NULL),(272,72,125,NULL,60,2,'adjustment','found','ADJ-2026-00001',NULL,'2026-09-28 11:58:38',7,'auto',NULL,NULL),(273,27,NULL,NULL,109,100,'adjustment','recount','CC-2026-00001',NULL,'2026-09-28 12:49:14',7,'auto',NULL,NULL),(274,56,NULL,NULL,109,100,'adjustment','recount','CC-2026-00002',NULL,'2026-09-28 12:49:14',7,'auto',NULL,NULL),(275,19,NULL,NULL,109,20,'adjustment','recount','CC-2026-00003',NULL,'2026-09-28 12:49:14',7,'auto',NULL,NULL),(276,27,NULL,109,NULL,50,'adjustment','recount','CC-2026-00004',NULL,'2026-09-28 12:54:03',7,'auto',NULL,NULL),(277,34,67,12,NULL,50,'adjustment','quarantine_release','QR-2026-00001',NULL,'2026-09-28 12:59:00',7,'auto',NULL,NULL),(278,1,NULL,111,123,25,'transfer','Test transfer pending approval','TR-2026-00002',NULL,'2025-01-15 08:00:00',7,'approved',7,'2026-09-28 16:08:25'),(279,43,106,120,NULL,40,'outbound','Picked for shipment SH-18','SH-18',NULL,'2026-09-30 10:10:54',6,'auto',NULL,NULL);
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Nordic Electronics Oy','Mikko Virtanen','mikko@nordicelec.fi','+358 40 111 2233','Helsinki, Finland','active'),(2,'Baltic Furniture Ltd','Anna Kask','anna@balticfurn.ee','+372 5555 1234','Tallinn, Estonia','active'),(3,'Global Tools GmbH','Hans Müller','hans@globaltools.de','+49 30 1234 5678','Berlin, Germany','active'),(4,'Scandi Supplies AB','Erik Larsson','erik@scandisupply.se','+46 8 123 456','Stockholm, Sweden','active'),(5,'MediCare Wholesale','Sarah Johnson','sales@medicarewholesale.fi','+358 50 998 7766','Espoo, Finland','active'),(6,'PackRight Solutions','Liisa Korhonen','liisa@packright.fi','+358 45 332 1100','Vantaa, Finland','active'),(7,'OfficePro Nordic','Juha Mäkinen','juha@officepro.fi','+358 44 556 7788','Tampere, Finland','active'),(8,'SafetyFirst Oy','Pekka Nieminen','pekka@safetyfirst.fi','+358 40 223 4455','Turku, Finland','active');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timeclock`
--

DROP TABLE IF EXISTS `timeclock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `timeclock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `clock_in_at` datetime NOT NULL,
  `clock_out_at` datetime DEFAULT NULL,
  `hours_worked` decimal(6,2) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_clock_in` (`clock_in_at`),
  KEY `idx_open` (`clock_out_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timeclock`
--

LOCK TABLES `timeclock` WRITE;
/*!40000 ALTER TABLE `timeclock` DISABLE KEYS */;
INSERT INTO `timeclock` VALUES (1,9,'2026-10-01 13:12:33','2026-10-01 13:12:38',0.00,NULL,'2026-10-01 10:12:33');
/*!40000 ALTER TABLE `timeclock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','staff') NOT NULL DEFAULT 'staff',
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (6,'matias','$2y$10$eEam7SQcJ8sb4jpPGva7c.1d.N/ZJgnPd29tTgkisecfct706I1pi','admin','matiasmasih4131@gmail.com','0402435420','2025-07-25 06:32:31','2026-10-01 14:42:11',1),(7,'Masih','$2y$10$C6eg.0Ao8aLuH6.TKqqXROrCuhy72z84Dx.oLNcX3sTywoITEN9wC','manager','aziznoyan50@gmail.com','0413114312','2025-07-25 09:34:36','2026-10-01 14:42:41',1),(9,'ali','$2y$10$3EysrysB7bJsvhZPCUKsG.1Vfk3YI3x9CM8kQu.MV96w.5BzqGnfG','staff','aziz.noyan@outlook.com','0402435420','2025-08-01 06:58:33','2026-10-01 14:33:11',1),(10,'aziz','$2y$10$h8ce/3QCnhQpDItBuYfj8u69BXHogDP6ZYUbVjA.ieT2suhOOxLme','staff','aziz.noyan1994@gmail.com',NULL,'2026-04-20 09:21:34',NULL,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `manager_id` (`manager_id`),
  CONSTRAINT `warehouses_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,'Helsinki Central Warehouse','HEL','Teollisuuskatu 10','Helsinki','Finland','+358 9 100 1000','helsinki@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(2,'Espoo Distribution Hub','ESP','Tekniikantie 25','Espoo','Finland','+358 9 200 2000','espoo@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(3,'Vantaa Logistics Center','VAN','Tikkurilantie 150','Vantaa','Finland','+358 9 300 3000','vantaa@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(4,'Tampere Regional Warehouse','TRE','Hatanpään valtatie 30','Tampere','Finland','+358 3 400 4000','tampere@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(5,'Turku Southwest Hub','TKU','Kärsämäentie 40','Turku','Finland','+358 2 500 5000','turku@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(6,'Oulu Northern Warehouse','OUL','Kiviharjuntie 20','Oulu','Finland','+358 8 600 6000','oulu@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(7,'Jyväskylä Central Hub','JKL','Aholaidantie 15','Jyväskylä','Finland','+358 14 700 7000','jyvaskyla@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(8,'Kuopio East Warehouse','KUO','Teollisuuskatu 5','Kuopio','Finland','+358 17 800 8000','kuopio@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(9,'Lahti Distribution Center','LHI','Teollisuuskatu 25','Lahti','Finland','+358 3 900 9000','lahti@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(10,'Pori West Hub','POR','Itsenäisyydenkatu 12','Pori','Finland','+358 2 1000 1000','pori@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(11,'Lappeenranta Southeast Depot','LPR','Kauppakatu 40','Lappeenranta','Finland','+358 5 1100 1100','lappeenranta@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(12,'Rovaniemi Arctic Hub','RVN','Teollisuuskatu 8','Rovaniemi','Finland','+358 16 1200 1200','rovaniemi@warehouse.fi',NULL,'active','2026-09-25 12:19:15'),(13,'Test Warehouse','TST',NULL,'Helsinki','Finland','0413114312',NULL,7,'active','2026-09-30 12:31:32');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-01 15:42:58
