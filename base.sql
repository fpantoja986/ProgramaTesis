-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: tesis
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `contenidos`
--

DROP TABLE IF EXISTS `contenidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contenidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `tipo` enum('articulo','video','podcast','imagen') NOT NULL,
  `contenido_texto` text,
  `archivo_path` longblob,
  `fecha_creacion` datetime NOT NULL,
  `id_admin` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_admin` (`id_admin`),
  CONSTRAINT `contenidos_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contenidos`
--

LOCK TABLES `contenidos` WRITE;
/*!40000 ALTER TABLE `contenidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `contenidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `foros`
--

DROP TABLE IF EXISTS `foros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `foros` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text,
  `id_admin` int NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_admin` (`id_admin`),
  CONSTRAINT `foros_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `foros`
--

LOCK TABLES `foros` WRITE;
/*!40000 ALTER TABLE `foros` DISABLE KEYS */;
INSERT INTO `foros` VALUES (1,'mania','mmaa',5,1,'2025-09-01 00:37:59');
/*!40000 ALTER TABLE `foros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `respuestas_foro`
--

DROP TABLE IF EXISTS `respuestas_foro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `respuestas_foro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contenido` text NOT NULL,
  `id_tema` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_tema` (`id_tema`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `respuestas_foro_ibfk_1` FOREIGN KEY (`id_tema`) REFERENCES `temas_foro` (`id`) ON DELETE CASCADE,
  CONSTRAINT `respuestas_foro_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `respuestas_foro`
--

LOCK TABLES `respuestas_foro` WRITE;
/*!40000 ALTER TABLE `respuestas_foro` DISABLE KEYS */;
/*!40000 ALTER TABLE `respuestas_foro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `temas_foro`
--

DROP TABLE IF EXISTS `temas_foro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temas_foro` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `contenido` text NOT NULL,
  `id_foro` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fijado` tinyint(1) DEFAULT '0',
  `cerrado` tinyint(1) DEFAULT '0',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_foro` (`id_foro`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `temas_foro_ibfk_1` FOREIGN KEY (`id_foro`) REFERENCES `foros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `temas_foro_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `temas_foro`
--

LOCK TABLES `temas_foro` WRITE;
/*!40000 ALTER TABLE `temas_foro` DISABLE KEYS */;
/*!40000 ALTER TABLE `temas_foro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  `token_verificacion` varchar(100) DEFAULT NULL,
  `verificado` tinyint(1) DEFAULT '0',
  `token_reset` varchar(100) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `genero` varchar(50) DEFAULT NULL,
  `rol` varchar(20) DEFAULT 'usuario',
  `require_reset_pass` tinyint(1) DEFAULT '1',
  `foto_perfil` longblob,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (5,'Administrador','administrador@gmail.com','$2y$10$l1nHFiS/nEFUdXp8tzs2JOL2Bo26UAIsBzErb.L/eJYX3/IR93dqK','2025-07-14 13:19:43',NULL,1,NULL,NULL,'Otro','administrador',1,NULL),(35,'andres Pantoja','andresf.pantoja212@umariana.edu.co','$2y$10$6P3n4q9Aeh/cbdP2nRS0yORKDVf6GPCMZy8r9CQOspCh3CwhhuSz.','2025-07-25 17:04:08',NULL,1,NULL,NULL,'Masculino','administrador',1,NULL),(36,'andres','fpantoja986@gmail.com','$2y$10$fIR/sUuEHOvFWByQlLrNteITD5QV6SD1UWyS9fhqeNMNtzRU804hu','2025-08-31 20:00:47',NULL,1,NULL,NULL,'Masculino','usuario',1,NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-31 20:52:04
