-- =====================================================================
-- university-hr: полный дамп базы (структура + тестовые данные)
-- Данные вымышлены. Пароль всех пользователей: Secret123!
-- Таблица api_tokens — только структура: выданные токены не публикуем.
-- Импорт (база должна существовать, существующие таблицы будут заменены):
--   mysql -u hr_demo -p university_hr < database/dump.sql
-- =====================================================================
-- MySQL dump 10.13  Distrib 9.6.0, for macos26.3 (arm64)
--
-- Host: localhost    Database: university_hr
-- ------------------------------------------------------
-- Server version	9.6.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `faculty_id` int unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `head_employee_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departments_faculty_name` (`faculty_id`,`name`),
  KEY `fk_departments_head` (`head_employee_id`),
  CONSTRAINT `fk_departments_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_departments_head` FOREIGN KEY (`head_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,1,'Кафедра программирования и информационных систем',1),(2,1,'Кафедра сетей связи и систем коммутации',6),(3,1,'Кафедра высшей математики и физики',11),(4,2,'Кафедра электроэнергетики',16),(5,2,'Кафедра технологии машиностроения',20),(6,2,'Кафедра автомобилей и транспорта',24),(7,3,'Кафедра промышленного и гражданского строительства',28),(8,3,'Кафедра архитектуры и дизайна',33),(9,4,'Кафедра экономики и управления',37),(10,4,'Кафедра языков',41);
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `last_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birth_date` date NOT NULL,
  `gender` enum('m','f') COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` int unsigned NOT NULL,
  `position_id` int unsigned NOT NULL,
  `academic_degree` enum('none','candidate','doctor','phd') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `hire_date` date NOT NULL,
  `status` enum('active','on_leave','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employees_email` (`email`),
  KEY `idx_employees_last_name` (`last_name`),
  KEY `idx_employees_department` (`department_id`),
  KEY `fk_employees_position` (`position_id`),
  CONSTRAINT `fk_employees_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_employees_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'Назаров','Джамшед','Хакимович','1978-03-14','m','+992 92 701 11 01','j.nazarov@example.tj',1,1,'candidate','2004-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(2,'Рахимов','Фарход','Джамшедович','1990-06-21','m','+992 92 701 11 02','f.rahimov@example.tj',1,4,'none','2014-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(3,'Хамидова','Мунира','Рахматовна','1986-11-02','f','+992 92 701 11 03','m.hamidova@example.tj',1,3,'candidate','2010-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(4,'Кодиров','Гайрат','Олимович','1995-02-17','m','+992 92 701 11 04','g.qodirov@example.tj',1,5,'none','2019-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(5,'Гафурова','Нигина','Шарифовна','1998-08-09','f','+992 92 701 11 05',NULL,1,11,'none','2021-02-15','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(6,'Шарипов','Хусейн','Ахмадович','1975-01-30','m','+992 93 702 22 01','h.sharipov@example.tj',2,1,'candidate','2001-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(7,'Иброхимов','Сухроб','Каххорович','1988-04-12','m','+992 93 702 22 02','s.ibrohimov@example.tj',2,3,'candidate','2012-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(8,'Мирзоева','Зарина','Джураевна','1993-07-25','f','+992 93 702 22 03','z.mirzoeva@example.tj',2,4,'none','2017-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(9,'Тоджиддинов','Бахтиёр','Гуломович','1999-12-03','m',NULL,NULL,2,6,'none','2023-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(10,'Абдуллоева','Шахло','Хасановна','1991-05-19','f','+992 93 702 22 05','sh.abdulloeva@example.tj',2,9,'none','2016-03-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(11,'Джалолов','Абдурахмон','Исмоилович','1962-09-08','m','+992 90 703 33 01','a.jalolov@example.tj',3,1,'doctor','1988-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(12,'Саидова','Гулчехра','Курбоновна','1970-02-28','f','+992 90 703 33 02','g.saidova@example.tj',3,2,'doctor','1994-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(13,'Урунов','Фаррух','Ганиевич','1984-10-15','m','+992 90 703 33 03','f.urunov@example.tj',3,3,'candidate','2009-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(14,'Назарова','Мавджуда','Тохировна','1996-03-22','f','+992 90 703 33 04','m.nazarova@example.tj',3,5,'none','2020-09-01','on_leave',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(15,'Хакимов','Далер','Раджабович','1982-06-30','m',NULL,NULL,3,12,'none','2011-01-10','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(16,'Курбонов','Илхом','Саъдуллоевич','1972-11-11','m','+992 91 704 44 01','i.qurbonov@example.tj',4,1,'candidate','1999-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(17,'Ашурова','Малика','Джамолиддиновна','1987-01-05','f','+992 91 704 44 02','m.ashurova@example.tj',4,3,'phd','2013-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(18,'Неъматов','Джахонгир','Хайдарович','1992-08-14','m','+992 91 704 44 03','j.nematov@example.tj',4,4,'none','2016-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(19,'Холова','Фарзона','Уктамовна','1997-04-27','f',NULL,'f.holova@example.tj',4,6,'none','2022-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(20,'Раджабов','Гулом','Хамроевич','1968-05-16','m','+992 92 705 55 01','g.rajabov@example.tj',5,1,'candidate','1993-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(21,'Юсупов','Ахтам','Косимович','1979-12-24','m','+992 92 705 55 02','a.yusupov@example.tj',5,3,'candidate','2006-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(22,'Давлатова','Ситора','Хусейновна','1994-09-13','f','+992 92 705 55 03','s.davlatova@example.tj',5,5,'none','2018-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(23,'Мансуров','Шухрат','Иззатуллоевич','1985-03-03','m',NULL,NULL,5,10,'none','2010-02-01','dismissed',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(24,'Зокиров','Мансур','Джурабоевич','1976-07-07','m','+992 93 706 66 01','m.zokirov@example.tj',6,1,'candidate','2003-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(25,'Хайдаров','Парвиз','Абдугаффорович','1989-10-20','m','+992 93 706 66 02','p.haydarov@example.tj',6,4,'none','2015-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(26,'Ганиев','Рустам','Шамсиддинович','1993-02-11','m','+992 93 706 66 03','r.ganiev@example.tj',6,5,'none','2019-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(27,'Каримова','Дилноза','Ахмадджоновна','1990-06-06','f','+992 93 706 66 04','d.karimova@example.tj',6,8,'none','2014-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(28,'Исмоилов','Бободжон','Рахимович','1965-04-04','m','+992 90 707 77 01','b.ismoilov@example.tj',7,1,'doctor','1990-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(29,'Латифова','Мадина','Каюмовна','1983-08-18','f','+992 90 707 77 02','m.latifova@example.tj',7,3,'candidate','2008-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(30,'Хусейнов','Фирдавс','Нуриддинович','1995-11-29','m','+992 90 707 77 03','f.huseynov@example.tj',7,5,'none','2020-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(31,'Олимова','Нозанин','Джамшедовна','2000-01-15','f',NULL,'n.olimova@example.tj',7,6,'none','2024-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(32,'Бобоев','Джавлон','Тохирович','1981-02-08','m',NULL,NULL,7,12,'none','2012-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(33,'Махмудова','Хилола','Иброхимовна','1974-12-12','f','+992 91 708 88 01','h.mahmudova@example.tj',8,1,'candidate','2000-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(34,'Шоев','Комрон','Ахлиддинович','1991-09-09','m','+992 91 708 88 02','k.shoev@example.tj',8,4,'none','2016-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(35,'Каххорова','Рухшона','Олимовна','1996-05-05','f','+992 91 708 88 03','r.qahhorova@example.tj',8,5,'none','2021-09-01','on_leave',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(36,'Рахмонов','Сино','Джураевич','1987-03-17','m','+992 91 708 88 04','s.rahmonov@example.tj',8,3,'phd','2013-02-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(37,'Джураев','Абдулло','Кодирович','1969-08-25','m','+992 92 709 99 01','a.juraev@example.tj',9,1,'doctor','1995-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(38,'Хасанова','Шабнам','Гуломовна','1985-10-10','f','+992 92 709 99 02','sh.hasanova@example.tj',9,3,'candidate','2010-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(39,'Умаров','Нозим','Хамидович','1994-01-23','m','+992 92 709 99 03','n.umarov@example.tj',9,4,'none','2018-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(40,'Ниёзова','Парвина','Урунбоевна','1998-07-31','f',NULL,'p.niyozova@example.tj',9,7,'none','2022-03-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(41,'Мирзоев','Хикмат','Гафорович','1971-06-15','m','+992 93 710 10 01','h.mirzoev@example.tj',10,1,'candidate','1997-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(42,'Солиева','Манижа','Курбоналиевна','1980-09-27','f','+992 93 710 10 02','m.solieva@example.tj',10,4,'none','2005-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(43,'Тоирова','Джамила','Ахроровна','1989-11-08','f','+992 93 710 10 03','j.toirova@example.tj',10,5,'none','2015-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(44,'Абдурахимов','Улмас','Сафарович','1992-12-19','m',NULL,'u.abdurahimov@example.tj',10,6,'none','2017-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(45,'Каримов','Хомид','Нуруллоевич','1997-04-04','m','+992 92 701 11 06','h.karimov@example.tj',1,9,'none','2020-10-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16'),(46,'Шодиева','Фируза','Халимовна','1993-03-13','f',NULL,'f.shodieva@example.tj',1,6,'none','2023-09-01','active',NULL,'2026-11-24 09:11:16','2026-11-24 09:11:16');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faculties`
--

DROP TABLE IF EXISTS `faculties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faculties` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_faculties_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculties`
--

LOCK TABLES `faculties` WRITE;
/*!40000 ALTER TABLE `faculties` DISABLE KEYS */;
INSERT INTO `faculties` VALUES (4,'Факультет инженерного бизнеса и управления'),(1,'Факультет информационных технологий и коммуникаций'),(3,'Факультет строительства и архитектуры'),(2,'Факультет энергетики и машиностроения');
/*!40000 ALTER TABLE `faculties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned NOT NULL,
  `type` enum('annual','study','unpaid','sick') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','head_approved','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `head_decision_by` int unsigned DEFAULT NULL,
  `hr_decision_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_leave_requests_status` (`status`),
  KEY `idx_leave_requests_employee` (`employee_id`),
  KEY `fk_leave_head_decision` (`head_decision_by`),
  KEY `fk_leave_hr_decision` (`hr_decision_by`),
  CONSTRAINT `fk_leave_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_head_decision` FOREIGN KEY (`head_decision_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_leave_hr_decision` FOREIGN KEY (`hr_decision_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_leave_dates` CHECK ((`end_date` >= `start_date`))
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
INSERT INTO `leave_requests` VALUES (1,2,'annual','2026-12-21','2027-01-10','Ежегодный отпуск в зимние каникулы','pending',NULL,NULL,'2026-10-28 04:15:00'),(2,3,'study','2026-11-16','2026-11-27','Учебный отпуск: защита диссертации','head_approved',3,NULL,'2026-10-20 06:40:00'),(3,4,'annual','2026-07-01','2026-08-25','Ежегодный оплачиваемый отпуск','approved',3,2,'2026-06-10 05:00:00'),(4,5,'unpaid','2026-11-05','2026-11-07','Семейные обстоятельства','rejected',3,NULL,'2026-10-30 09:20:00'),(5,14,'annual','2026-10-26','2026-11-20','Ежегодный отпуск','approved',1,2,'2026-10-05 03:30:00'),(6,35,'sick','2026-10-30','2026-11-13','Больничный лист № 0457 (вымышленный)','approved',1,2,'2026-10-30 03:05:00'),(7,7,'annual','2026-12-01','2026-12-14','Поездка к родственникам','pending',NULL,NULL,'2026-10-29 11:45:00'),(8,18,'study','2027-01-12','2027-01-30','Курсы повышения квалификации','head_approved',1,NULL,'2026-10-15 07:00:00'),(9,38,'annual','2026-06-15','2026-07-31','Ежегодный отпуск','approved',1,2,'2026-05-20 04:00:00'),(10,42,'unpaid','2026-11-10','2026-11-12',NULL,'pending',NULL,NULL,'2026-11-01 05:10:00'),(11,29,'annual','2026-12-07','2026-12-31','Ежегодный отпуск','rejected',1,2,'2026-10-12 10:30:00'),(12,1,'annual','2027-01-05','2027-01-25','Ежегодный отпуск заведующего кафедрой','pending',NULL,NULL,'2026-11-01 12:00:00');
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('hire','transfer','dismissal','leave') COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` int unsigned NOT NULL,
  `order_date` date NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_number` (`number`),
  KEY `idx_orders_employee` (`employee_id`),
  KEY `fk_orders_created_by` (`created_by`),
  CONSTRAINT `fk_orders_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'ПР-2014-0002','hire',2,'2014-08-29','Принять Рахимова Ф. Д. на должность старшего преподавателя кафедры программирования и ИС с 01.09.2014.',2),(2,'ПР-2023-0046','hire',46,'2023-08-30','Принять Шодиеву Ф. Х. на должность ассистента кафедры программирования и ИС с 01.09.2023.',2),(3,'ПЕ-2022-0025','transfer',25,'2022-08-25','Перевести Хайдарова П. А. на должность старшего преподавателя кафедры автомобилей и транспорта.',2),(4,'УВ-2026-0023','dismissal',23,'2026-06-30','Уволить Мансурова Ш. И. по собственному желанию с 30.06.2026.',2),(5,'ОТ-2026-0003','leave',4,'2026-06-20','Предоставить Кодирову Г. О. ежегодный отпуск с 01.07.2026 по 25.08.2026.',2),(6,'ОТ-2026-0005','leave',14,'2026-10-12','Предоставить Назаровой М. Т. ежегодный отпуск с 26.10.2026 по 20.11.2026.',2),(7,'ОТ-2026-0006','leave',35,'2026-10-30','Оформить Каххоровой Р. О. отпуск по болезни с 30.10.2026 по 13.11.2026.',2),(8,'ОТ-2026-0009','leave',38,'2026-05-25','Предоставить Хасановой Ш. Г. ежегодный отпуск с 15.06.2026 по 31.07.2026.',2);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `positions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('academic','administrative','support') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_positions_title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,'Заведующий кафедрой','academic'),(2,'Профессор','academic'),(3,'Доцент','academic'),(4,'Старший преподаватель','academic'),(5,'Преподаватель','academic'),(6,'Ассистент','academic'),(7,'Методист','administrative'),(8,'Секретарь кафедры','administrative'),(9,'Инженер-программист','administrative'),(10,'Старший лаборант','support'),(11,'Лаборант','support'),(12,'Техник','support');
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int unsigned DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','hr','head','employee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'employee',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_employee` (`employee_id`),
  CONSTRAINT `fk_users_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,NULL,'admin','$2y$10$Oe0Iq6YYB/HlD8L5UKwUw.xKSTSOaQG5zpZYoG1ntsohvurNta1Zi','admin',1,NULL),(2,NULL,'hr','$2y$12$lprDnoHiwSYgXfJ6hhlUDu.TjM0l/zDPyvsOqvVwWWciFvha4ocbG','hr',1,'2026-11-24 15:03:09'),(3,1,'head_pis','$2y$10$CriKqbs7IiL5foNRMOkfheFHJdWizs0X75nMQyNjpvfaI3LnLS5ZC','head',1,NULL),(4,2,'teacher1','$2y$10$adc37yoOLuwVQ5nodsHLmuQsq9WWMYNw/bgNCEiH1ZzFlPl75Gr.i','employee',1,NULL);
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

-- Dump completed
-- MySQL dump 10.13  Distrib 9.6.0, for macos26.3 (arm64)
--
-- Host: localhost    Database: university_hr
-- ------------------------------------------------------
-- Server version	9.6.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `api_tokens`
--

DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_tokens_hash` (`token_hash`),
  KEY `fk_api_tokens_user` (`user_id`),
  CONSTRAINT `fk_api_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
