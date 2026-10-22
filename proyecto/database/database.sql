-- MariaDB / MySQL dump 10.19
-- Base de datos: `aulavir7_Aulavirtualsalle` / `aulavirtual_salle`
-- Instituto La Salle Ciénaga Magdalena
-- Fecha: 2026-10-01 21:02:55
-- ------------------------------------------------------

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
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mapeo legacy: Rol o tipo de usuario asignado en sistema anterior',
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Definición de roles para control de acceso RBAC';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `nombre_rol`, `descripcion`) VALUES 
('1','administrador','Superusuario del sistema, acceso completo a configuraciones y auditoría'),
('2','administrativo','Personal de Tesorería, Caja, Admisiones y Secretaría General'),
('3','docente','Profesor titular y catedrático con acceso a notas, asistencias y planes'),
('4','psicologo','Orientador escolar y psicología con observador 100% confidencial'),
('5','estudiante','Alumno matriculado con consulta de boletines, tareas y asistencias'),
('6','acudiente','Padre o acudiente legal con acceso a estado financiero y firma digital'),
('7','coordinador','Coordinador académico y de convivencia institucional');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `rol_id` int(11) NOT NULL,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mapeo legacy: Email de la tabla usuarios_antiguos',
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Contraseña cifrada con BCRYPT',
  `estado_cuenta` enum('activo','inactivo','suspendido') COLLATE utf8mb4_unicode_ci DEFAULT 'activo' COMMENT 'Estado para habilitar/deshabilitar acceso',
  `debe_cambiar_password` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Requiere cambio de contraseña en el primer inicio de sesión',
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  KEY `fk_usuarios_roles` (`rol_id`),
  CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=222 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Credenciales de autenticación y estado del usuario';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` (`id`, `rol_id`, `correo`, `password_hash`, `estado_cuenta`, `debe_cambiar_password`, `creado_en`, `actualizado_en`) VALUES 
('1','1','admin@lasallecienaga.edu.co','$2y$10$9K8iDu0S//zKCRnp0oBCqOKVo3o4Fgs8f/9HR7BB8weRwB.5jgvo6','activo','0','2026-08-19 15:40:22','2026-09-08 20:17:44'),
('2','2','tesoreria@lasallecienaga.edu.co','$2y$10$bYLBlS7ekRg9v37MANMQU.vXxgmpf8CNYDcOlsocHzJPgj4EIGJQy','activo','0','2026-08-19 15:40:22','2026-09-10 10:11:38'),
('3','3','docente.garcia@lasallecienaga.edu.co','$2y$10$xPuqIYSldfbmQ0DckGSYVOwRdhlDi5X7dw/c/c9u2EuAaz1KySahy','activo','0','2026-08-19 15:40:22','2026-09-23 22:37:05'),
('4','4','psicologia@lasallecienaga.edu.co','$2y$10$S5YnGe/gJCS6MK.MG9k5lelK8J9pKBW.5lDtDqQsPfW2ERR5Ylqty','activo','0','2026-08-19 15:40:22','2026-09-10 10:55:41'),
('5','5','estudiante.martinez@lasallecienaga.edu.co','$2y$10$7OgOWwAMp43pn9FJdh0rS.12MKCcvHG2zW7Ig/YYnMUFfEC5zeBYW','activo','1','2026-08-19 15:40:22','2026-09-07 23:21:35'),
('7','3','candres@lasallecienaga.edu.co','$2y$10$U4TSTLibKX/VsyO3pp4Pv.LUCKQEEfDCs3t7g7.vG4H/X2pC4hX2i','activo','1','2026-08-19 16:20:23','2026-09-15 17:24:08'),
('168','3','carollayotero@lasallecienaga.edu.co','$2y$10$OTCdOXC.1Tn3UEc9Xi3akOU3dlIJYYisgWrb3kLXkmRuEsivxQbHS','activo','1','2026-09-06 11:08:05','2026-09-15 17:24:08'),
('169','3','lilianalozano@lasallecienaga.edu.co','$2y$10$.kOpNQrQgm1nQerBi/ODFOUncvZFOVbpXNP/6M/EiA6m0DP8B5Gny','activo','1','2026-09-06 11:08:05','2026-09-15 17:24:08'),
('170','3','dairialvarado@lasallecienaga.edu.co','$2y$10$8CipCvbBILODLGZJO2QdveCanSHCKee8gdenR7HMEsfk6n7eCToZe','activo','1','2026-09-06 11:08:05','2026-09-15 17:24:08'),
('171','3','andreacuadrado@lasallecienaga.edu.co','$2y$10$RyDchPcP0haoJvJC3cIKeeIIklgca0RRAgRb0HgC1eWZ/iUnnWWqG','activo','1','2026-09-06 11:08:06','2026-09-15 17:24:08'),
('172','3','efigeniajimeno@lasallecienaga.edu.co','$2y$10$LNeQ7laL3ZyMy1ukDkJEw..rzq3ebXILl5UTUyOMUXKFowgw37V9m','activo','1','2026-09-06 11:08:06','2026-09-06 12:14:57'),
('173','2','rector@lasallecienaga.edu.co','$2y$10$0FFNrnaHdq/dOznytokqZuVzxBzpmBSm3i6xhtuX81tJrlyDdn4PG','activo','1','2026-09-06 11:08:06','2026-09-10 11:17:23'),
('174','3','leonardotorres@lasallecienaga.edu.co','$2y$10$UjnbiMRy1otkLUYEyCLovuvpoB01ES54ScXNRVHM4snyF5edjzx/e','activo','1','2026-09-06 11:08:06','2026-09-15 17:24:08'),
('175','1','secretarialafloresta@lasallecienaga.edu.co','$2y$10$cQVnXmy8LIN9nmwwD2JmSuJcK/YQN34gx88U9nfq3/8JcWnJYIM0q','activo','1','2026-09-06 11:08:06','2026-09-10 09:56:06'),
('176','3','laurengiovannetti@lasalle.edu.co','$2y$10$5byXru9be96ueAUI.MZas.1E7SY6wfKOol9HzcFfit9bES1ItquGy','activo','1','2026-09-06 11:08:06','2026-09-06 12:14:57'),
('177','3','Administracion@lasallecienaga.edu.co','$2y$10$4TTuCF4h9PGhxwZOFuksoud1ZVTzKVG11L1EUFt2NUeqk/EBxMISC','activo','1','2026-09-06 11:08:07','2026-09-06 12:14:57'),
('178','3','marilinmolina@lasallecienaga.edu.co','$2y$10$7FBk/n.jMcE5b0lvjrqsyely9DjEdZMefRc15jIq6QyeMocxYNV/q','activo','1','2026-09-06 11:08:07','2026-09-15 17:24:08'),
('179','3','shakiraobispo@lasallecienaga.edu.co','$2y$10$vdM0rSYcrHg/kwJqozJwOuwmB1x0cg.C9TMvsoUZwegsCo7ZsPtfW','activo','1','2026-09-06 11:08:07','2026-09-06 12:14:57'),
('180','3','milagrosvidal@lasallecienaga.edu.co','$2y$10$oPilbKN8dP2bV/OQ5GoEr.87r65ybRvpa6khCQNmI7DDZxJI9jhMe','activo','1','2026-09-06 11:08:07','2026-09-06 12:14:57'),
('181','3','samirfigueroa@lasallecienaga.edu.co','$2y$10$MsBjQ8ea4FKk6sVduj668e0o163H0JT2b4hR.m6MbZtQn0FgYotDm','activo','1','2026-09-06 11:08:07','2026-09-15 17:24:08'),
('182','3','mariasolano@lasallecienaga.edu.co','$2y$10$VtfodoeoLd45.N79EeFsCOtJUGJBm/EWaj.yA86QmNECq3IKHPx5e','activo','1','2026-09-06 11:08:07','2026-09-15 17:24:08'),
('183','3','Nazlygarcia@lasallecienaga.edu.co','$2y$10$zi63GHXHt9QiJHIRW/U6pefG6qAEG.6YkjuLpAfYZSa7l4xBe2FUW','activo','1','2026-09-06 11:08:07','2026-09-15 17:24:08'),
('184','3','darlcysanchez@lasallecienaga.edu.co','$2y$10$ejycjj2ECPP4S5MbJLdijOiislpK5LZ7dItks9JWGIAb0SNThs5zC','activo','1','2026-09-06 11:08:08','2026-09-06 12:14:57'),
('185','3','cindybolano@lasallecienaga.edu.co','$2y$10$01CWQGgQmeDVa1dlq035Z.T4h95Wrtye4ETqzuE7QMjmIQvfVBjm2','activo','1','2026-09-06 11:08:08','2026-09-15 17:24:08'),
('186','3','dayanacaro@lasallecienaga.edu.co','$2y$10$1raD7OnxPlys/TO5bYhBpuKWoZrbUCKpePeuE7LKbWxn.IqmkIbxy','activo','1','2026-09-06 11:08:08','2026-09-15 17:24:08'),
('187','3','jazminguette@lasallecienaga.edu.co','$2y$10$g3vM/qQk/iCX/laGcIWbZOE2lfd/1EEhcn/vX.umBmVWTGqOyJ2ui','activo','1','2026-09-06 11:08:08','2026-09-15 17:24:08'),
('188','3','yamithcera@lasallecienaga.edu.co','$2y$10$nfWFQmvvyOfkGPfjiJFJB.vWLy0WUSCh8qB5mHHBXfbZoCBGnAkJC','activo','1','2026-09-06 11:08:08','2026-09-15 17:24:08'),
('189','3','jhonvasquez@lasallecienaga.edu.co','$2y$10$iP7lBn4pJU35Z/duMDM.W.CD3AiZtRVlKzy9SQKls4Jw8bpRi5sPq','activo','1','2026-09-06 11:08:09','2026-09-15 17:24:08'),
('190','3','edismartinez@lasallecienaga.edu.co','$2y$10$tA0x42u11lgI6FPWNhUCc.r1s/1NEQtZgCBGJDJoQs3ACXKWaoZwK','activo','1','2026-09-06 11:08:09','2026-09-15 17:24:08'),
('191','5','Sharolthomas@lasallecienaga.edu.co','$2y$10$muZUxj2F95ON.NXRgUy/gu1yTpH02D5nhV0FsxpaIgED7eaN1gwiu','activo','1','2026-09-07 21:43:08','2026-09-07 21:43:08'),
('192','5','fredynunez@lasallecienaga.edu.co','$2y$10$scbP0Tpy5gNkb2JfLoe5kO4QToa.qwZaGRW5CJa1VjcvxNOCh9mPe','activo','1','2026-09-07 21:43:08','2026-09-07 21:43:08'),
('193','5','matiasamaya@lasallecienaga.edu.co','$2y$10$F9ikruPDQipuOXl5cKTM7ejq8mpInc3TjakTtP1JLQLJ4Pn6sG4F6','activo','1','2026-09-07 21:43:09','2026-09-07 21:43:09'),
('194','5','holmanramirez@lasallecienaga.edu.co','$2y$10$9v.JVNtEz5.x/Uj6Y./ujOJ.gzpzx9nnUHGnVOA7s7WTgvSW3d21q','activo','1','2026-09-07 21:43:09','2026-09-07 21:43:09'),
('195','5','lauramoreno@lasallecienaga.edu.co','$2y$10$oSHpkmWxbq/9dYGqEcf9ku8Mck/j0g3y7bj6SDg8engyx6rkIkxfC','activo','1','2026-09-07 21:43:09','2026-09-07 21:43:09'),
('196','5','diegopinilla@lasallecienaga.edu.co','$2y$10$IM9ITF62NTNZ2IiP0beqVOlFDCBC68ctIppmCetTr.AU837Pf7gkK','activo','1','2026-09-07 21:43:09','2026-09-07 21:43:09'),
('197','5','samueltoncel@lasallecienaga.edu.co','$2y$10$Nd/tVcViY/F8R0RqI.bsJO5R1ucMo4Z/PhQkG6DPzGdYUOWE6skY6','activo','1','2026-09-07 21:43:09','2026-09-07 21:43:09'),
('198','5','antonyescorcia@lasallecienaga.edu.co','$2y$10$XfhwfQZGvDMnxQq9OAJc5e0yW0AY6n8GSohVVA5wiqTOgyX.CBOvO','activo','1','2026-09-07 21:43:09','2026-09-07 22:57:26'),
('199','5','luisalopez@lasallecienaga.edu.co','$2y$10$Rq.88uSPdQe.RModYbmx..q6/oiqHilugZzEwedSE.KdFHLegxPbu','activo','1','2026-09-07 21:43:10','2026-09-07 21:43:10'),
('200','5','camilaurieles@lasallecienaga.edu.co','$2y$10$9hj2bpVqjFo1zAS1FIksme8oBcaKcsgqKRr6FDN5wft7d58rRwUn2','activo','1','2026-09-07 21:43:10','2026-09-07 21:43:10'),
('201','5','frankmolina@lasallecienaga.edu.co','$2y$10$lTAlc6B1.Cr9P1sBG089heCTKXkeOEO.MOUqFZmtfZWT22huI3sAy','activo','1','2026-09-07 21:43:10','2026-09-07 21:43:10'),
('202','5','diegosanchez@lasallecienaga.edu.co','$2y$10$zN/wGJMgvc6B7PZksXJD6Okida3p5htVuKWY5IRa0scJd9yAl5YV6','activo','1','2026-09-07 21:43:10','2026-09-07 21:43:10'),
('203','5','gabrielamartinez@lasallecienaga.edu.co','$2y$10$9oc/thDKO/q/xAaOD7ba3.ZpuIibe2SWuqwUc5rTTYh7g3s71lrz6','activo','1','2026-09-07 21:43:10','2026-09-07 22:57:26'),
('204','5','nikollmartinez@lasallecienaga.edu.co','$2y$10$SNGevArYEcirjph0VAO9/uE3yx5lyEjV5sezVk7XFDn2l0uea82K2','activo','1','2026-09-07 21:43:10','2026-09-07 22:57:26'),
('205','5','gabrieldelahoz@lasallecienaga.edu.co','$2y$10$poh8GH9xbqxkTrukNuKcne.0Cw2wynwKHzXcim3ce2o8ldtep40ha','activo','1','2026-09-07 21:43:11','2026-09-07 22:57:26'),
('206','5','hilaryyanes@lasallecienaga.edu.co','$2y$10$.4LvYpWJhQe4ChB0nkuNcux2IKkIhV1G6vhYkc4iItMHCEmMyqXi6','activo','1','2026-09-07 21:43:11','2026-09-07 22:57:26'),
('207','5','ericecheverria@lasallecienaga.edu.co','$2y$10$J5wLMAj.sEHCQO/scveXZ.pJo9Fv0TUJNPTMDTcCIGcs2XrsLp9CK','activo','1','2026-09-07 21:43:11','2026-09-07 21:43:11'),
('208','5','sebastianpenuela@lasallecienaga.edu.co','$2y$10$9AXgfYkWA220gvA4StxPAOYhyrQ4npdT.m2pdCIrRdSpT9K9bCmeW','activo','1','2026-09-07 21:43:11','2026-09-07 22:57:26'),
('209','5','estiventoscano@lasallecienaga.edu.co','$2y$10$YUqnEubxym/EVkt0B8vtUeolT/2aQNUd.yELfcbyb1/O2g.MSMD4.','activo','1','2026-09-07 21:43:11','2026-09-07 22:57:26'),
('210','5','santiagomedina@lasallecienaga.edu.co','$2y$10$PduxEmbP6Qlst.giXH6Nzu49uwEAF2J6rYBPr7D/MwyqWPiiCvlze','activo','1','2026-09-07 21:43:11','2026-09-07 22:57:26'),
('211','5','johanysgutierrez@lasallecienaga.edu.co','$2y$10$5fI.96RY425WWNT6ZET0eeUMot9A9tlJMV9LoUIn2JzfulbmhkoOq','activo','1','2026-09-07 21:43:12','2026-09-07 21:43:12'),
('212','5','carlosptz14@gmail.com','$2y$10$mEFjCY0YaQqWybOhFOlqI.t1y3L8B7xvDtojG1YnEYxPOCBFBhHU.','activo','1','2026-09-08 17:14:02','2026-09-15 17:24:08'),
('213','4','elexiavelasquez@lasallecienaga.edu.co','$2y$10$vA8rtetRSK.ilJ3pTlVBL.V1kv8OOSunjmXyd178J98vvHy0yuWt.','activo','0','2026-09-10 12:28:48','2026-09-10 12:33:38'),
('214','3','joaquinrada@lasallecienaga.edu.co','$2y$10$cZiBanoXluhPof6VIE4OEe8Mjp9sEMshLCwebmQvjP0tg9sORPrc2','activo','1','2026-09-10 12:30:13','2026-09-15 17:24:08'),
('215','3','manuelsanchez@lasallecienaga.edu.co','$2y$10$Q5SY0cqZ5bg0wlShIkukd.uhORYHIHADAriACcRXKY5T/0z0S9h5G','activo','1','2026-09-10 12:32:10','2026-09-15 17:24:08'),
('216','3','yuranisaraujo@lasallecienaga.edu.co','$2y$10$OzCoNHcGsdQ94gTNFx1K7.cyxGVgtT1luGPZnElj0cK9rwiW7LhhO','activo','1','2026-09-10 12:36:34','2026-09-15 17:24:08'),
('217','3','kendryescorcia@lasallecienaga.edu.co','$2y$10$VX28hiF8K6qUsGP5esnzAObsxhvt4uhLeziwPa.dSYUDJ3bXjMg7.','activo','1','2026-09-10 13:44:28','2026-09-15 17:24:08'),
('218','3','jhoncorrea@lasallecienaga.edu.co','$2y$10$SN10iN17qNUWe2DUTk65yeF66tZhkVEQK7tzpwRdrHthIHRZw16QO','activo','1','2026-09-10 13:51:00','2026-09-15 17:24:08'),
('220','5','felipe.restrepo@lasallecienaga.edu.co','$2y$10$lULTrdSmPV5O6PodVubhge8d5XARC7FjfxDbcxjmFyGlkxZmMuie2','activo','1','2026-09-15 17:42:48','2026-09-15 17:42:48'),
('221','7','coordinacion@lasallecienaga.edu.co','$2y$10$FSnaFbqzUeOaHBv5ZQ9hzO7sfLSpvkWWKhGDXlQN6Bwy6T5e..sXG','activo','0','2026-09-23 22:17:29','2026-09-23 22:17:29');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perfiles_detalles`
--

DROP TABLE IF EXISTS `perfiles_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `perfiles_detalles` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint(20) NOT NULL COMMENT 'Relación 1:1 con tabla usuarios',
  `primer_nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segundo_nombre` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primer_apellido` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segundo_apellido` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_documento` enum('CC','TI','CE','RC','PASAPORTE') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tipo de identificación',
  `documento_identidad` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número de documento único',
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sede_caja` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ambas',
  `foto_perfil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default_avatar.png',
  `firma_digital` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Firma digital o mecanografiada del docente (.png, .jpg, .pdf)',
  `acudiente_nombre` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acudiente_telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acudiente_correo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Correo de contacto institucional/personal del acudiente',
  `metadata_migracion_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  UNIQUE KEY `uk_documento_identidad` (`documento_identidad`),
  CONSTRAINT `fk_perfiles_usuarios` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Datos personales e información biográfica';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perfiles_detalles`
--

LOCK TABLES `perfiles_detalles` WRITE;
/*!40000 ALTER TABLE `perfiles_detalles` DISABLE KEYS */;
INSERT INTO `perfiles_detalles` (`id`, `usuario_id`, `primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`, `tipo_documento`, `documento_identidad`, `telefono`, `direccion`, `sede_caja`, `foto_perfil`, `firma_digital`, `acudiente_nombre`, `acudiente_telefono`, `acudiente_correo`, `metadata_migracion_id`) VALUES 
('1','1','Herman','Gualberto','Rodríguez','Pérez','CC','1082987001','3001234567','Calle 12 # 8-45 Ciénaga','ambas','default_avatar.png',NULL,NULL,NULL,NULL,'MIG-001'),
('2','2','Irania','Marcela','Ramirez','Gongola','CC','1221967242','3013862814','Cra 28 # 18-49 Ciénaga','ambas','default_avatar.png','public/uploads/firmas/firma_canvas_tesoreria_2_1789053317.png',NULL,NULL,NULL,'MIG-002'),
('3','3','Carlos','Alberto','García','López','CC','1082987003','3104567890','Calle 7 # 12-30 Ciénaga','ambas','default_avatar.png',NULL,NULL,NULL,NULL,'MIG-003'),
('4','4','Soreyis','Marcela','Arango','Salamanca','CC','1083569196','3014231535','Cra 35 # 16-88 Ciénaga','ambas','default_avatar.png',NULL,NULL,NULL,NULL,'MIG-004'),
('5','5','Mateo','Andrés','Martínez','Suárez','TI','1082987005','3201122334','Calle 18 # 14-50 Ciénaga','ambas','default_avatar.png',NULL,'Jorge Luis Martínez B.','3201122334','acudiente.martinez@lasallecienaga.edu.co','MIG-005'),
('7','7','Carlos',NULL,'Padilla',NULL,'CC','1083455808',NULL,NULL,'ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('9','168','Carollay','Briyith','Otero','Pabón','TI','1221972820','3012917464','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('10','169','Liliana',NULL,'Lozano','Bolaño','CC','26719710','3014504219','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('11','170','Dairi',NULL,'Alvarado','Rodríguez','TI','1090448336','3116569108','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('12','171','Andrea',NULL,'Cuadrado','Casares','TI','1221982044','3012163346','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('13','172','Efigenia',NULL,'Jimeno',NULL,'TI','1004380555','3234863908','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('14','173','Cherlys','','Dominguez','Ramirez','CC','39033967','3015040240','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('15','174','Leonardo',NULL,'Torres',NULL,'TI','1083038911','3015188760','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('16','175','Héctor','','Sandoval','Ojeda','TI','1083553798','3238764848','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('17','176','Lauren',NULL,'Giovannetti','Duarte','TI','1221973786','3004417437','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('18','177','Carlos',NULL,'Hernandez','Lopez','CC','8487634','3013095701','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('19','178','Marilin',NULL,'Molina','Mellado','TI','1010107911','3022649142','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('20','179','Shakira',NULL,'Obispo','Mendoza','TI','1004377741','3023478049','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('21','180','Milagros',NULL,'Vidal','López','CC','39002062','3003223085','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('22','181','Samir','Johan','Figueroa','Padilla','TI','1080427408','1080427408','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('23','182','María','José','Solano','Aguirre','TI','1004380682','3126810710','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('24','183','Nazly',NULL,'Garcia','Carrillo','TI','1084559038','3134264429','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('25','184','Darlcy','Oneirs','Sanchez','Perez','TI','1098620766','3163277750','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('26','185','Cindy',NULL,'Bolaño',NULL,'TI','1083465992','3241168071','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('27','186','Dayana',NULL,'Caro','Fernandez','TI','1193134552','3042832870','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('28','187','Jazmín',NULL,'Guette','Campo','CC','39001643','3045885283','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('29','188','Yamith','Yusep','Cera','Gonzales','CC','12448438','3132263145','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('30','189','Jhon','Elvis','Vasquez','Fornaris','TI','1004376491','3007134223','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('31','190','Edis','Miguel','Martínez','Orozco','TI','1082978504','3104816784','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('32','191','Sharol','Taliana','Thomas','Miranda','TI','1080430071','','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Shirly Miranda','3155391304','shirly.miranda@gmail.com',NULL),
('33','192','Fredy','Esteban','Nuñez','Ballestero','TI','1080431912',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Olga','3001093166','fredyestebannunezballestero@gmail.com',NULL),
('34','193','Matías','Rafael','Amaya','González','TI','1083567310',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Noris Judith Gonzáles Herrera','3234099274','matiasrafelamayagonzalez8@gmail.com',NULL),
('35','194','Hollman','José','Ramírez','Salcedo','TI','1083467496',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Martha Isabel Salcedo padilla','3052142452','Hollmanjoseramirezsalcedo@gmail.com',NULL),
('36','195','laura','valentina','moreno','jaime','TI','1083566112',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'nidia johana jaime vásquez','3116951303','nidiajaime375@gmail.com',NULL),
('37','196','Diego','Fernando','Pinilla','Zapata','TI','1083559826',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Marelfis Zapata','3017530214','marelfizz@gmail.com',NULL),
('38','197','Samuel','Eduardo','Toncel','Gonzalez','TI','1080433994',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Luis','3015003380','Bedrok979@gmail.com',NULL),
('39','198','Antony','Jose','Escorcia','Olivero','TI','1080434115',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Tony xavier escorcia olivero','3126621905','Escorciatony506@gmail.com',NULL),
('40','199','Luisa','Fernanda','López','Orozco','TI','1080433006',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Yerlis','3023877152','Yerlisorozco@com.co',NULL),
('41','200','Camila','Andrea','Urieles','Bolaño','TI','1083559393',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Karen margarita Bolaño Acosta','3017095346','camilaurieles@gmail.com',NULL),
('42','201','Frank','David','Molina','Silvera','TI','1041773299',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Livis silvera','3023165898','Silveralivis12@gmail.com',NULL),
('43','202','Diego','Andres','Sanchez','Cabana','TI','1080432245',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Margareth pineda','3003860424','cabanadiego973@gmail.com',NULL),
('44','203','Gabriela','Sofía','Martínez','Valiente','TI','1119396198',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Celia Elisa valiente Hernández','3216548568','ceilavalienteh0588@gmail.com',NULL),
('45','204','NIKOLL','MICHELL','MARTINEZ','RADA','TI','1083557552',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Dulcina Rada','3502292620','Martinezmicheln@gmail.com',NULL),
('46','205','Gabriel','Elías','De la hoz','Cervantes','TI','1083651252',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Sileibis Patricia Cervantes Cervantes','3216532451','Cervantesileibis@hotmail.com',NULL),
('47','206','Hillary','Valentina','Yanes','Borrero','TI','1080438007',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Irene patricia borrero thomas','3018967001','22alvarojose2@gmail.com',NULL),
('48','207','Eric','Gabriel','Echeverria','De La Cruz','TI','1083558335',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Nataly Victoria De la Cruz Pasion','3127543114','marygabriel.1422@gmail.com',NULL),
('49','208','Sebastián','Danilo','Peñuela','Barrios','TI','1083557059',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Kelly barrios granados','3017357288','Kelixeth1803@outlook.es',NULL),
('50','209','Stiven','Alfonso','Toscano','Escorcia','TI','1083559893',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Stiven Toscano','3007134223','stiventoscanoo@gmail.com',NULL),
('51','210','Santiago','Andres','Medina','Polo','TI','1082927078',NULL,'Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Ana sofia','3122465265','Anasofiapolopardo5@gmail.com',NULL),
('52','211','Johanys','Eliana','Gutiérrez','Gutiérrez','TI','10833559604','','Ciénaga, Magdalena','ambas','default_avatar.png',NULL,'Mayra Alejandra Gutiérrez bravo','3022689128','mairagutierrez272@gmail.com',NULL),
('53','212','Carlos','','Padilla','','CC','1083553700','','','ambas','default_avatar.png',NULL,'viviana','3008450447','viviana4@gmail.com',NULL),
('54','213','Elexia','Isabel','Velasquez','Polo','CC','57414257','3003251267','Calle 22 # 8-16','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('55','214','Joaquin','','Rada','Medina','CC','1082409830','3006807216','Calle 24 #06-47 Apt 01','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('56','215','Manuel','Fernando','Sanchez','Meriño','CC','1082866767','3006974115','Carrera 38 #18c -93','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('57','216','Yuranis','Mercedes','Araujo','Orozco','CC','1083455235','3013470188','Calle 16a Carrera 25','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('58','217','Kendry','Johanny','Escorcia','Hernandez','CC','1004375981','3127733866','Carrera 36a #18D-49','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('59','218','Jhon','Jairo','Correa','Ramirez','CC','1083462327','3015908606','Carrera 30 #50-09 Reserva De Curinca Laguna','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL),
('61','220','Felipe','Andrés','Restrepo','Vargas','TI','1099887766','3009988771','Calle 18 # 10-25','ambas','default_avatar.png',NULL,'Martha Vargas Polo','3127788990','marthavargas.prueba@gmail.com',NULL),
('62','221','Coordinación','Académica y','Convivencia','Escolar','CC','1082987007','3007654321','Calle 12 # 8-45 Ciénaga','ambas','default_avatar.png',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `perfiles_detalles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cursos`
--

DROP TABLE IF EXISTS `cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grado` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: 6°, 7°, 10°, 11°',
  `grupo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: A, B, 01',
  `jornada` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Mañana',
  `sede` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sede Principal',
  `ano_lectivo` int(11) NOT NULL COMMENT 'Año académico',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_curso_ano_jornada_sede` (`grado`,`grupo`,`ano_lectivo`,`jornada`,`sede`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cursos` WRITE;
/*!40000 ALTER TABLE `cursos` DISABLE KEYS */;
INSERT INTO `cursos` (`id`, `grado`, `grupo`, `jornada`, `sede`, `ano_lectivo`) VALUES
(1, '10°', 'A', 'Mañana', 'Sede Principal', 2026),
(3, '11°', 'A', 'Mañana', 'Sede Principal', 2026),
(5, 'Transición', 'A', 'Mañana', 'Sede Principal', 2026),
(7, '1°', 'A', 'Mañana', 'Sede Principal', 2026),
(9, '2°', 'A', 'Mañana', 'Sede Principal', 2026),
(11, '3°', 'A', 'Mañana', 'Sede Principal', 2026),
(13, '4°', 'A', 'Mañana', 'Sede Principal', 2026),
(15, '5°', 'A', 'Mañana', 'Sede Principal', 2026),
(17, '6°', 'A', 'Mañana', 'Sede Principal', 2026),
(19, '7°', 'A', 'Mañana', 'Sede Principal', 2026),
(21, '8°', 'A', 'Mañana', 'Sede Principal', 2026),
(23, '9°', 'A', 'Mañana', 'Sede Principal', 2026),
(26, 'Transición', 'B', 'Tarde', 'Sede Principal', 2026),
(28, '1°', 'B', 'Tarde', 'Sede Principal', 2026),
(30, '2°', 'B', 'Tarde', 'Sede Principal', 2026),
(32, '3°', 'B', 'Tarde', 'Sede Principal', 2026),
(34, '4°', 'B', 'Tarde', 'Sede Principal', 2026),
(36, '5°', 'B', 'Tarde', 'Sede Principal', 2026),
(38, '6°', 'B', 'Tarde', 'Sede Principal', 2026),
(40, '7°', 'B', 'Tarde', 'Sede Principal', 2026),
(42, '8°', 'B', 'Tarde', 'Sede Principal', 2026),
(44, '9°', 'B', 'Tarde', 'Sede Principal', 2026),
(46, '10°', 'B', 'Tarde', 'Sede Principal', 2026),
(48, '11°', 'B', 'Tarde', 'Sede Principal', 2026),
(49, 'Párvulo', 'A', 'Mañana', 'Sede Principal', 2026),
(50, 'Párvulo', 'B', 'Tarde', 'Sede Principal', 2026),
(51, 'Prejardín', 'A', 'Mañana', 'Sede Principal', 2026),
(52, 'Prejardín', 'B', 'Tarde', 'Sede Principal', 2026),
(53, 'Jardín', 'A', 'Mañana', 'Sede Principal', 2026),
(54, 'Jardín', 'B', 'Tarde', 'Sede Principal', 2026),
(55, 'Párvulo', 'A', 'Mañana', 'Sede Victoria', 2026),
(56, 'Párvulo', 'B', 'Tarde', 'Sede Victoria', 2026),
(57, 'Prejardín', 'A', 'Mañana', 'Sede Victoria', 2026),
(58, 'Prejardín', 'B', 'Tarde', 'Sede Victoria', 2026),
(59, 'Jardín', 'A', 'Mañana', 'Sede Victoria', 2026),
(60, 'Jardín', 'B', 'Tarde', 'Sede Victoria', 2026),
(61, 'Transición', 'A', 'Mañana', 'Sede Victoria', 2026),
(62, 'Transición', 'B', 'Tarde', 'Sede Victoria', 2026),
(63, '1°', 'A', 'Mañana', 'Sede Victoria', 2026),
(64, '1°', 'B', 'Tarde', 'Sede Victoria', 2026),
(65, '2°', 'A', 'Mañana', 'Sede Victoria', 2026),
(66, '2°', 'B', 'Tarde', 'Sede Victoria', 2026),
(67, '3°', 'A', 'Mañana', 'Sede Victoria', 2026),
(68, '3°', 'B', 'Tarde', 'Sede Victoria', 2026),
(69, '4°', 'A', 'Mañana', 'Sede Victoria', 2026),
(70, '4°', 'B', 'Tarde', 'Sede Victoria', 2026),
(71, '5°', 'A', 'Mañana', 'Sede Victoria', 2026),
(72, '5°', 'B', 'Tarde', 'Sede Victoria', 2026),
(73, '6°', 'A', 'Mañana', 'Sede Victoria', 2026),
(74, '6°', 'B', 'Tarde', 'Sede Victoria', 2026),
(75, '7°', 'A', 'Mañana', 'Sede Victoria', 2026),
(76, '7°', 'B', 'Tarde', 'Sede Victoria', 2026),
(77, '8°', 'A', 'Mañana', 'Sede Victoria', 2026),
(78, '8°', 'B', 'Tarde', 'Sede Victoria', 2026),
(79, '9°', 'A', 'Mañana', 'Sede Victoria', 2026),
(80, '9°', 'B', 'Tarde', 'Sede Victoria', 2026),
(81, '10°', 'A', 'Mañana', 'Sede Victoria', 2026),
(82, '10°', 'B', 'Tarde', 'Sede Victoria', 2026),
(83, '11°', 'A', 'Mañana', 'Sede Victoria', 2026),
(84, '11°', 'B', 'Tarde', 'Sede Victoria', 2026);
/*!40000 ALTER TABLE `cursos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asignaturas`
--

DROP TABLE IF EXISTS `asignaturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asignaturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_asignatura` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asignaturas`
--

LOCK TABLES `asignaturas` WRITE;
/*!40000 ALTER TABLE `asignaturas` DISABLE KEYS */;
INSERT INTO `asignaturas` (`id`, `nombre_asignatura`, `codigo`) VALUES 
('1','Química','CNAT-QUI'),
('2','Física','CNAT-FIS'),
('3','Laboratorio de Ciencias','CNAT-LAB'),
('4','Economía Política','CSOC-ECO'),
('5','Religión','RELI-001'),
('6','Lengua Castellana','HUMA-LEN'),
('7','Comprensión Lectora','HUMA-CLT'),
('8','Producción Textual','HUMA-PTX'),
('9','Inglés','IDIO-ING'),
('10','Geometría','MATH-GEO'),
('11','Estadística','MATH-EST'),
('12','Trigonometría','MATH-TRI'),
('13','Expresión Corporal','EDFI-EXP'),
('14','Danza','EDFI-DAN'),
('15','Informática','TECH-INF'),
('16','Robótica','TECH-ROB'),
('17','Programación','TECH-PRG'),
('18','Filosofía','FILO-001'),
('19','Cátedra La Sallista','CSLL-001'),
('20','Metodología de la Investigación','INVE-001'),
('21','Emprendimiento','EMPR-001'),
('22','Educación Emocional','EMOC-001');
/*!40000 ALTER TABLE `asignaturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curso_asignatura_docente`
--

DROP TABLE IF EXISTS `curso_asignatura_docente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `curso_asignatura_docente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `docente_id` bigint(20) NOT NULL,
  `sistema_calif` enum('1-5','1-10','letras') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1-5' COMMENT 'Escala fijada por administrativo',
  PRIMARY KEY (`id`),
  KEY `fk_cad_curso` (`curso_id`),
  KEY `fk_cad_asignatura` (`asignatura_id`),
  KEY `fk_cad_docente` (`docente_id`),
  CONSTRAINT `fk_cad_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cad_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cad_docente` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=401 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curso_asignatura_docente`
--

LOCK TABLES `curso_asignatura_docente` WRITE;
/*!40000 ALTER TABLE `curso_asignatura_docente` DISABLE KEYS */;
INSERT INTO `curso_asignatura_docente` (`id`, `curso_id`, `asignatura_id`, `docente_id`, `sistema_calif`) VALUES 
('17','1','1','3','1-5'),
('18','1','2','7','1-5'),
('19','1','3','168','1-5'),
('20','1','4','169','1-5'),
('21','1','5','170','1-5'),
('22','1','6','171','1-5'),
('23','1','7','172','1-5'),
('24','1','8','174','1-5'),
('25','2','1','7','1-5'),
('26','2','2','168','1-5'),
('27','2','3','169','1-5'),
('28','2','4','170','1-5'),
('29','2','5','171','1-5'),
('30','2','6','172','1-5'),
('31','2','7','174','1-5'),
('32','2','8','176','1-5'),
('33','3','1','168','1-5'),
('34','3','2','169','1-5'),
('35','3','3','170','1-5'),
('36','3','4','171','1-5'),
('37','3','5','172','1-5'),
('38','3','6','174','1-5'),
('39','3','7','176','1-5'),
('40','3','8','177','1-5'),
('41','4','1','169','1-5'),
('42','4','2','170','1-5'),
('43','4','3','171','1-5'),
('44','4','4','172','1-5'),
('45','4','5','174','1-5'),
('46','4','6','176','1-5'),
('47','4','7','177','1-5'),
('48','4','8','178','1-5'),
('49','5','1','170','1-5'),
('50','5','2','171','1-5'),
('51','5','3','172','1-5'),
('52','5','4','174','1-5'),
('53','5','5','176','1-5'),
('54','5','6','177','1-5'),
('55','5','7','178','1-5'),
('56','5','8','179','1-5'),
('57','6','1','171','1-5'),
('58','6','2','172','1-5'),
('59','6','3','174','1-5'),
('60','6','4','176','1-5'),
('61','6','5','177','1-5'),
('62','6','6','178','1-5'),
('63','6','7','179','1-5'),
('64','6','8','180','1-5'),
('65','7','1','172','1-5'),
('66','7','2','174','1-5'),
('67','7','3','176','1-5'),
('68','7','4','177','1-5'),
('69','7','5','178','1-5'),
('70','7','6','179','1-5'),
('71','7','7','180','1-5'),
('72','7','8','181','1-5'),
('73','8','1','174','1-5'),
('74','8','2','176','1-5'),
('75','8','3','177','1-5'),
('76','8','4','178','1-5'),
('77','8','5','179','1-5'),
('78','8','6','180','1-5'),
('79','8','7','181','1-5'),
('80','8','8','182','1-5'),
('81','9','1','176','1-5'),
('82','9','2','177','1-5'),
('83','9','3','178','1-5'),
('84','9','4','179','1-5'),
('85','9','5','180','1-5'),
('86','9','6','181','1-5'),
('87','9','7','182','1-5'),
('88','9','8','183','1-5'),
('89','10','1','177','1-5'),
('90','10','2','178','1-5'),
('91','10','3','179','1-5'),
('92','10','4','180','1-5'),
('93','10','5','181','1-5'),
('94','10','6','182','1-5'),
('95','10','7','183','1-5'),
('96','10','8','184','1-5'),
('97','11','1','178','1-5'),
('98','11','2','179','1-5'),
('99','11','3','180','1-5'),
('100','11','4','181','1-5'),
('101','11','5','182','1-5'),
('102','11','6','183','1-5'),
('103','11','7','184','1-5'),
('104','11','8','185','1-5'),
('105','12','1','179','1-5'),
('106','12','2','180','1-5'),
('107','12','3','181','1-5'),
('108','12','4','182','1-5'),
('109','12','5','183','1-5'),
('110','12','6','184','1-5'),
('111','12','7','185','1-5'),
('112','12','8','186','1-5'),
('113','13','1','180','1-5'),
('114','13','2','181','1-5'),
('115','13','3','182','1-5'),
('116','13','4','183','1-5');
INSERT INTO `curso_asignatura_docente` (`id`, `curso_id`, `asignatura_id`, `docente_id`, `sistema_calif`) VALUES 
('117','13','5','184','1-5'),
('118','13','6','185','1-5'),
('119','13','7','186','1-5'),
('120','13','8','187','1-5'),
('121','14','1','181','1-5'),
('122','14','2','182','1-5'),
('123','14','3','183','1-5'),
('124','14','4','184','1-5'),
('125','14','5','185','1-5'),
('126','14','6','186','1-5'),
('127','14','7','187','1-5'),
('128','14','8','188','1-5'),
('129','15','1','182','1-5'),
('130','15','2','183','1-5'),
('131','15','3','184','1-5'),
('132','15','4','185','1-5'),
('133','15','5','186','1-5'),
('134','15','6','187','1-5'),
('135','15','7','188','1-5'),
('136','15','8','189','1-5'),
('137','16','1','183','1-5'),
('138','16','2','184','1-5'),
('139','16','3','185','1-5'),
('140','16','4','186','1-5'),
('141','16','5','187','1-5'),
('142','16','6','188','1-5'),
('143','16','7','189','1-5'),
('144','16','8','190','1-5'),
('145','17','1','184','1-5'),
('146','17','2','185','1-5'),
('147','17','3','186','1-5'),
('148','17','4','187','1-5'),
('149','17','5','188','1-5'),
('150','17','6','189','1-5'),
('151','17','7','190','1-5'),
('152','17','8','214','1-5'),
('153','18','1','185','1-5'),
('154','18','2','186','1-5'),
('155','18','3','187','1-5'),
('156','18','4','188','1-5'),
('157','18','5','189','1-5'),
('158','18','6','190','1-5'),
('159','18','7','214','1-5'),
('160','18','8','215','1-5'),
('161','19','1','186','1-5'),
('162','19','2','187','1-5'),
('163','19','3','188','1-5'),
('164','19','4','189','1-5'),
('165','19','5','190','1-5'),
('166','19','6','214','1-5'),
('167','19','7','215','1-5'),
('168','19','8','216','1-5'),
('169','20','1','187','1-5'),
('170','20','2','188','1-5'),
('171','20','3','189','1-5'),
('172','20','4','190','1-5'),
('173','20','5','214','1-5'),
('174','20','6','215','1-5'),
('175','20','7','216','1-5'),
('176','20','8','217','1-5'),
('177','21','1','188','1-5'),
('178','21','2','189','1-5'),
('179','21','3','190','1-5'),
('180','21','4','214','1-5'),
('181','21','5','215','1-5'),
('182','21','6','216','1-5'),
('183','21','7','217','1-5'),
('184','21','8','218','1-5'),
('185','22','1','189','1-5'),
('186','22','2','190','1-5'),
('187','22','3','214','1-5'),
('188','22','4','215','1-5'),
('189','22','5','216','1-5'),
('190','22','6','217','1-5'),
('191','22','7','218','1-5'),
('192','22','8','3','1-5'),
('193','23','1','190','1-5'),
('194','23','2','214','1-5'),
('195','23','3','215','1-5'),
('196','23','4','216','1-5'),
('197','23','5','217','1-5'),
('198','23','6','218','1-5'),
('199','23','7','3','1-5'),
('200','23','8','7','1-5'),
('201','24','1','214','1-5'),
('202','24','2','215','1-5'),
('203','24','3','216','1-5'),
('204','24','4','217','1-5'),
('205','24','5','218','1-5'),
('206','24','6','3','1-5'),
('207','24','7','7','1-5'),
('208','24','8','168','1-5'),
('209','25','1','215','1-5'),
('210','25','2','216','1-5'),
('211','25','3','217','1-5'),
('212','25','4','218','1-5'),
('213','25','5','3','1-5'),
('214','25','6','7','1-5'),
('215','25','7','168','1-5'),
('216','25','8','169','1-5');
INSERT INTO `curso_asignatura_docente` (`id`, `curso_id`, `asignatura_id`, `docente_id`, `sistema_calif`) VALUES 
('217','26','1','216','1-5'),
('218','26','2','217','1-5'),
('219','26','3','218','1-5'),
('220','26','4','3','1-5'),
('221','26','5','7','1-5'),
('222','26','6','168','1-5'),
('223','26','7','169','1-5'),
('224','26','8','170','1-5'),
('225','27','1','217','1-5'),
('226','27','2','218','1-5'),
('227','27','3','3','1-5'),
('228','27','4','7','1-5'),
('229','27','5','168','1-5'),
('230','27','6','169','1-5'),
('231','27','7','170','1-5'),
('232','27','8','171','1-5'),
('233','28','1','218','1-5'),
('234','28','2','3','1-5'),
('235','28','3','7','1-5'),
('236','28','4','168','1-5'),
('237','28','5','169','1-5'),
('238','28','6','170','1-5'),
('239','28','7','171','1-5'),
('240','28','8','172','1-5'),
('241','29','1','3','1-5'),
('242','29','2','7','1-5'),
('243','29','3','168','1-5'),
('244','29','4','169','1-5'),
('245','29','5','170','1-5'),
('246','29','6','171','1-5'),
('247','29','7','172','1-5'),
('248','29','8','174','1-5'),
('249','30','1','7','1-5'),
('250','30','2','168','1-5'),
('251','30','3','169','1-5'),
('252','30','4','170','1-5'),
('253','30','5','171','1-5'),
('254','30','6','172','1-5'),
('255','30','7','174','1-5'),
('256','30','8','176','1-5'),
('257','31','1','168','1-5'),
('258','31','2','169','1-5'),
('259','31','3','170','1-5'),
('260','31','4','171','1-5'),
('261','31','5','172','1-5'),
('262','31','6','174','1-5'),
('263','31','7','176','1-5'),
('264','31','8','177','1-5'),
('265','32','1','169','1-5'),
('266','32','2','170','1-5'),
('267','32','3','171','1-5'),
('268','32','4','172','1-5'),
('269','32','5','174','1-5'),
('270','32','6','176','1-5'),
('271','32','7','177','1-5'),
('272','32','8','178','1-5'),
('273','33','1','170','1-5'),
('274','33','2','171','1-5'),
('275','33','3','172','1-5'),
('276','33','4','174','1-5'),
('277','33','5','176','1-5'),
('278','33','6','177','1-5'),
('279','33','7','178','1-5'),
('280','33','8','179','1-5'),
('281','34','1','171','1-5'),
('282','34','2','172','1-5'),
('283','34','3','174','1-5'),
('284','34','4','176','1-5'),
('285','34','5','177','1-5'),
('286','34','6','178','1-5'),
('287','34','7','179','1-5'),
('288','34','8','180','1-5'),
('289','35','1','172','1-5'),
('290','35','2','174','1-5'),
('291','35','3','176','1-5'),
('292','35','4','177','1-5'),
('293','35','5','178','1-5'),
('294','35','6','179','1-5'),
('295','35','7','180','1-5'),
('296','35','8','181','1-5'),
('297','36','1','174','1-5'),
('298','36','2','176','1-5'),
('299','36','3','177','1-5'),
('300','36','4','178','1-5'),
('301','36','5','179','1-5'),
('302','36','6','180','1-5'),
('303','36','7','181','1-5'),
('304','36','8','182','1-5'),
('305','37','1','176','1-5'),
('306','37','2','177','1-5'),
('307','37','3','178','1-5'),
('308','37','4','179','1-5'),
('309','37','5','180','1-5'),
('310','37','6','181','1-5'),
('311','37','7','182','1-5'),
('312','37','8','183','1-5'),
('313','38','1','177','1-5'),
('314','38','2','178','1-5'),
('315','38','3','179','1-5'),
('316','38','4','180','1-5');
INSERT INTO `curso_asignatura_docente` (`id`, `curso_id`, `asignatura_id`, `docente_id`, `sistema_calif`) VALUES 
('317','38','5','181','1-5'),
('318','38','6','182','1-5'),
('319','38','7','183','1-5'),
('320','38','8','184','1-5'),
('321','39','1','178','1-5'),
('322','39','2','179','1-5'),
('323','39','3','180','1-5'),
('324','39','4','181','1-5'),
('325','39','5','182','1-5'),
('326','39','6','183','1-5'),
('327','39','7','184','1-5'),
('328','39','8','185','1-5'),
('329','40','1','179','1-5'),
('330','40','2','180','1-5'),
('331','40','3','181','1-5'),
('332','40','4','182','1-5'),
('333','40','5','183','1-5'),
('334','40','6','184','1-5'),
('335','40','7','185','1-5'),
('336','40','8','186','1-5'),
('337','41','1','180','1-5'),
('338','41','2','181','1-5'),
('339','41','3','182','1-5'),
('340','41','4','183','1-5'),
('341','41','5','184','1-5'),
('342','41','6','185','1-5'),
('343','41','7','186','1-5'),
('344','41','8','187','1-5'),
('345','42','1','181','1-5'),
('346','42','2','182','1-5'),
('347','42','3','183','1-5'),
('348','42','4','184','1-5'),
('349','42','5','185','1-5'),
('350','42','6','186','1-5'),
('351','42','7','187','1-5'),
('352','42','8','188','1-5'),
('353','43','1','182','1-5'),
('354','43','2','183','1-5'),
('355','43','3','184','1-5'),
('356','43','4','185','1-5'),
('357','43','5','186','1-5'),
('358','43','6','187','1-5'),
('359','43','7','188','1-5'),
('360','43','8','189','1-5'),
('361','44','1','183','1-5'),
('362','44','2','184','1-5'),
('363','44','3','185','1-5'),
('364','44','4','186','1-5'),
('365','44','5','187','1-5'),
('366','44','6','188','1-5'),
('367','44','7','189','1-5'),
('368','44','8','190','1-5'),
('369','45','1','184','1-5'),
('370','45','2','185','1-5'),
('371','45','3','186','1-5'),
('372','45','4','187','1-5'),
('373','45','5','188','1-5'),
('374','45','6','189','1-5'),
('375','45','7','190','1-5'),
('376','45','8','214','1-5'),
('377','46','1','185','1-5'),
('378','46','2','186','1-5'),
('379','46','3','187','1-5'),
('380','46','4','188','1-5'),
('381','46','5','189','1-5'),
('382','46','6','190','1-5'),
('383','46','7','214','1-5'),
('384','46','8','215','1-5'),
('385','47','1','186','1-5'),
('386','47','2','187','1-5'),
('387','47','3','188','1-5'),
('388','47','4','189','1-5'),
('389','47','5','190','1-5'),
('390','47','6','214','1-5'),
('391','47','7','215','1-5'),
('392','47','8','216','1-5'),
('393','48','1','187','1-5'),
('394','48','2','188','1-5'),
('395','48','3','189','1-5'),
('396','48','4','190','1-5'),
('397','48','5','214','1-5'),
('398','48','6','215','1-5'),
('399','48','7','216','1-5'),
('400','48','8','217','1-5');
/*!40000 ALTER TABLE `curso_asignatura_docente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `matriculas`
--

DROP TABLE IF EXISTS `matriculas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matriculas` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `estudiante_id` bigint(20) NOT NULL COMMENT 'Usuario ID con rol estudiante',
  `curso_id` int(11) NOT NULL,
  `ano_lectivo` int(11) NOT NULL,
  `estado_matricula` enum('activa','retirado','graduado','suspendido') COLLATE utf8mb4_unicode_ci DEFAULT 'activa',
  `codigo_matricula` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ej: EST-2026-10001',
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_estudiante_ano` (`estudiante_id`,`ano_lectivo`),
  KEY `fk_matricula_curso` (`curso_id`),
  CONSTRAINT `fk_matricula_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  CONSTRAINT `fk_matricula_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `matriculas`
--

LOCK TABLES `matriculas` WRITE;
/*!40000 ALTER TABLE `matriculas` DISABLE KEYS */;
INSERT INTO `matriculas` (`id`, `estudiante_id`, `curso_id`, `ano_lectivo`, `estado_matricula`, `codigo_matricula`, `creado_en`) VALUES 
('3','191','3','2026','activa','EST-2026-00191','2026-09-07 21:43:08'),
('4','192','3','2026','activa','EST-2026-00192','2026-09-07 21:43:08'),
('5','193','3','2026','activa','EST-2026-00193','2026-09-07 21:43:09'),
('6','194','3','2026','activa','EST-2026-00194','2026-09-07 21:43:09'),
('7','195','3','2026','activa','EST-2026-00195','2026-09-07 21:43:09'),
('8','196','3','2026','activa','EST-2026-00196','2026-09-07 21:43:09'),
('9','197','3','2026','activa','EST-2026-00197','2026-09-07 21:43:09'),
('10','198','3','2026','activa','EST-2026-00198','2026-09-07 21:43:09'),
('11','199','3','2026','activa','EST-2026-00199','2026-09-07 21:43:10'),
('12','200','3','2026','activa','EST-2026-00200','2026-09-07 21:43:10'),
('13','201','3','2026','activa','EST-2026-00201','2026-09-07 21:43:10'),
('14','202','3','2026','activa','EST-2026-00202','2026-09-07 21:43:10'),
('15','203','3','2026','activa','EST-2026-00203','2026-09-07 21:43:10'),
('16','204','3','2026','activa','EST-2026-00204','2026-09-07 21:43:10'),
('17','205','3','2026','activa','EST-2026-00205','2026-09-07 21:43:11'),
('18','206','3','2026','activa','EST-2026-00206','2026-09-07 21:43:11'),
('19','207','3','2026','activa','EST-2026-00207','2026-09-07 21:43:11'),
('20','208','3','2026','activa','EST-2026-00208','2026-09-07 21:43:11'),
('21','209','3','2026','activa','EST-2026-00209','2026-09-07 21:43:11'),
('22','210','3','2026','activa','EST-2026-00210','2026-09-07 21:43:11'),
('23','211','3','2026','activa','EST-2026-00211','2026-09-07 21:43:12'),
('69','212','3','2026','activa','EST-2026-00212','2026-09-08 17:14:02'),
('72','220','3','2026','activa','EST-2026-00220','2026-09-15 17:42:48'),
('73','5','3','2026','activa','EST-2026-00005','2026-09-23 21:34:40');
/*!40000 ALTER TABLE `matriculas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asistencias`
--

DROP TABLE IF EXISTS `asistencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asistencias` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `matricula_id` bigint(20) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('Asistió','Faltó','Excusa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asistió',
  `observacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_asistencia_diaria` (`matricula_id`,`asignatura_id`,`fecha`),
  KEY `fk_asist_asignatura` (`asignatura_id`),
  CONSTRAINT `fk_asist_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asist_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asistencias`
--

LOCK TABLES `asistencias` WRITE;
/*!40000 ALTER TABLE `asistencias` DISABLE KEYS */;
/*!40000 ALTER TABLE `asistencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calificaciones_logros`
--

DROP TABLE IF EXISTS `calificaciones_logros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calificaciones_logros` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `matricula_id` bigint(20) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `periodo` tinyint(4) NOT NULL COMMENT 'Valores [1, 2, 3, 4]',
  `nota_cognitiva` decimal(4,2) NOT NULL DEFAULT '0.00',
  `nota_procedimental` decimal(4,2) NOT NULL DEFAULT '0.00',
  `nota_actitudinal` decimal(4,2) NOT NULL DEFAULT '0.00',
  `nota_final` decimal(4,2) NOT NULL DEFAULT '0.00',
  `logros_observaciones` text COLLATE utf8mb4_unicode_ci,
  `actualizado_en` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_calificacion_periodo` (`matricula_id`,`asignatura_id`,`periodo`),
  KEY `fk_calif_asignatura` (`asignatura_id`),
  CONSTRAINT `fk_calif_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_calif_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calificaciones_logros`
--

LOCK TABLES `calificaciones_logros` WRITE;
/*!40000 ALTER TABLE `calificaciones_logros` DISABLE KEYS */;
INSERT INTO `calificaciones_logros` (`id`, `matricula_id`, `asignatura_id`, `periodo`, `nota_cognitiva`, `nota_procedimental`, `nota_actitudinal`, `nota_final`, `logros_observaciones`, `actualizado_en`) VALUES 
('14','5','12','1','4.50','4.50','4.50','4.50','Aplica con precisión razones trigonométricas.','2026-09-16 20:12:59'),
('15','5','6','1','4.80','4.80','4.80','4.80','Excelente comprensión y análisis de textos líricos.','2026-09-16 20:12:59'),
('16','5','1','1','4.20','4.20','4.20','4.20','Identifica la estructura atómica y enlaces químicos.','2026-09-16 20:12:59'),
('17','5','9','1','4.70','4.70','4.70','4.70','Demuestra fluidez y dominio de vocabulario técnico.','2026-09-16 20:12:59'),
('18','3','6','1','4.90','4.90','4.90','4.90','Sobresaliente desempeño en producción textual y ortografía.','2026-09-16 20:12:59'),
('19','3','12','1','4.10','4.10','4.10','4.10','Resuelve problemas algebraicos con criterio lógico.','2026-09-16 20:12:59');
/*!40000 ALTER TABLE `calificaciones_logros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banco_logros`
--

DROP TABLE IF EXISTS `banco_logros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banco_logros` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `docente_id` bigint(20) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `periodo` tinyint(4) NOT NULL DEFAULT '1',
  `escala` enum('Superior','Alto','Basico','Bajo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion_logro` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_doc_cur_asig_per_esc` (`docente_id`,`curso_id`,`asignatura_id`,`periodo`,`escala`),
  KEY `idx_busqueda_logros` (`curso_id`,`asignatura_id`,`periodo`,`escala`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banco_logros`
--

LOCK TABLES `banco_logros` WRITE;
/*!40000 ALTER TABLE `banco_logros` DISABLE KEYS */;
INSERT INTO `banco_logros` (`id`, `docente_id`, `curso_id`, `asignatura_id`, `periodo`, `escala`, `descripcion_logro`, `created_at`, `updated_at`) VALUES 
('1','3','1','1','1','Superior','Demuestra excelencia y dominio sobresaliente en la resolución de problemas químicos del primer periodo.','2026-09-11 01:06:17','2026-09-11 01:06:17');
/*!40000 ALTER TABLE `banco_logros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `citaciones_acudientes`
--

DROP TABLE IF EXISTS `citaciones_acudientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `citaciones_acudientes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `estudiante_id` bigint(20) NOT NULL,
  `psicologo_id` bigint(20) NOT NULL,
  `fecha_citacion` datetime NOT NULL,
  `modalidad` enum('Presencial','Online','Telefónica') COLLATE utf8mb4_unicode_ci DEFAULT 'Presencial',
  `motivo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('Pendiente','Completada','Cancelada') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendiente',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_cit_estudiante` (`estudiante_id`),
  KEY `fk_cit_psicologo` (`psicologo_id`),
  CONSTRAINT `fk_cit_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cit_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `citaciones_acudientes`
--

LOCK TABLES `citaciones_acudientes` WRITE;
/*!40000 ALTER TABLE `citaciones_acudientes` DISABLE KEYS */;
/*!40000 ALTER TABLE `citaciones_acudientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contabilidad_caja`
--

DROP TABLE IF EXISTS `contabilidad_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contabilidad_caja` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `usuario_id` bigint(20) NOT NULL COMMENT 'Estudiante o acudiente',
  `concepto_pago` enum('Matricula','Pension','Constancia','Otros') COLLATE utf8mb4_unicode_ci NOT NULL,
  `mes_pension` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `metodo_pago` enum('Efectivo','Transferencia','PSE','Tarjeta') COLLATE utf8mb4_unicode_ci DEFAULT 'Efectivo',
  `estado_pago` enum('Pendiente','Pagado','Vencido','Anulado') COLLATE utf8mb4_unicode_ci DEFAULT 'Pendiente',
  `fecha_pago` datetime DEFAULT NULL,
  `fecha_vencimiento` date NOT NULL,
  `recibo_numero` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sede_caja` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'principal',
  PRIMARY KEY (`id`),
  KEY `fk_caja_usuario` (`usuario_id`),
  CONSTRAINT `fk_caja_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contabilidad_caja`
--

LOCK TABLES `contabilidad_caja` WRITE;
/*!40000 ALTER TABLE `contabilidad_caja` DISABLE KEYS */;
/*!40000 ALTER TABLE `contabilidad_caja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contabilidad_egresos`
--

DROP TABLE IF EXISTS `contabilidad_egresos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contabilidad_egresos` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `egreso_numero` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `beneficiario` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `metodo_pago` enum('Efectivo','Transferencia','Cheque','Tarjeta') COLLATE utf8mb4_unicode_ci DEFAULT 'Efectivo',
  `fecha_egreso` datetime NOT NULL,
  `soporte_factura` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sede_caja` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'principal',
  `usuario_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `egreso_numero` (`egreso_numero`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contabilidad_egresos`
--

LOCK TABLES `contabilidad_egresos` WRITE;
/*!40000 ALTER TABLE `contabilidad_egresos` DISABLE KEYS */;
/*!40000 ALTER TABLE `contabilidad_egresos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contratos_prestacion`
--

DROP TABLE IF EXISTS `contratos_prestacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contratos_prestacion` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `estudiante_id` bigint(20) NOT NULL,
  `acudiente_id` bigint(20) NOT NULL,
  `ano_lectivo` int(11) NOT NULL,
  `clausulas_html` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `costo_anual_pactado` decimal(12,2) NOT NULL,
  `firmado_digitalmente` tinyint(1) DEFAULT '0',
  `token_firma` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_firma` datetime DEFAULT NULL,
  `ip_firma` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_contrato_estudiante` (`estudiante_id`),
  KEY `fk_contrato_acudiente` (`acudiente_id`),
  CONSTRAINT `fk_contrato_acudiente` FOREIGN KEY (`acudiente_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_contrato_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contratos_prestacion`
--

LOCK TABLES `contratos_prestacion` WRITE;
/*!40000 ALTER TABLE `contratos_prestacion` DISABLE KEYS */;
/*!40000 ALTER TABLE `contratos_prestacion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `planes_clase`
--

DROP TABLE IF EXISTS `planes_clase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `planes_clase` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `docente_id` bigint(20) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `semana_numero` int(11) DEFAULT '1',
  `periodo` int(11) NOT NULL DEFAULT '1',
  `tema_principal` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `objetivos_aprendizaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenido_pei` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Alineación institucional con PEI La Salle',
  `archivo_pdf` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_aprobacion` enum('Borrador','Enviado','Aprobado','Rechazado') COLLATE utf8mb4_unicode_ci DEFAULT 'Borrador',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_plan_docente` (`docente_id`),
  KEY `fk_plan_asignatura` (`asignatura_id`),
  KEY `fk_plan_curso` (`curso_id`),
  CONSTRAINT `fk_plan_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`),
  CONSTRAINT `fk_plan_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  CONSTRAINT `fk_plan_docente` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `planes_clase`
--

LOCK TABLES `planes_clase` WRITE;
/*!40000 ALTER TABLE `planes_clase` DISABLE KEYS */;
/*!40000 ALTER TABLE `planes_clase` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `observador_estudiante`
--

DROP TABLE IF EXISTS `observador_estudiante`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `observador_estudiante` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `estudiante_id` bigint(20) NOT NULL,
  `psicologo_id` bigint(20) NOT NULL,
  `nivel_gravedad` enum('Bajo','Medio','Alto','Gravísimo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Bajo',
  `bitacora_cifrada` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Texto cifrado con AES-256-CBC + IV embebido en Base64',
  `compromisos` text COLLATE utf8mb4_unicode_ci,
  `firma_acudiente_requerida` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_obs_est_estudiante` (`estudiante_id`),
  KEY `fk_obs_est_psicologo` (`psicologo_id`),
  CONSTRAINT `fk_obs_est_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_obs_est_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Observador confidencial módulo psicología — cifrado AES-256';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `observador_estudiante`
--

LOCK TABLES `observador_estudiante` WRITE;
/*!40000 ALTER TABLE `observador_estudiante` DISABLE KEYS */;
/*!40000 ALTER TABLE `observador_estudiante` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `observador_psicologia`
--

DROP TABLE IF EXISTS `observador_psicologia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `observador_psicologia` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `estudiante_id` bigint(20) NOT NULL,
  `psicologo_id` bigint(20) NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `bitacora_confidencial` blob NOT NULL COMMENT 'Texto cifrado con AES-256 para alta privacidad',
  `nivel_gravedad` enum('Bajo','Medio','Alto','Gravísimo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Bajo',
  `compromisos` text COLLATE utf8mb4_unicode_ci,
  `firma_digital_padre` text COLLATE utf8mb4_unicode_ci COMMENT 'Firma Base64 o token hash',
  PRIMARY KEY (`id`),
  KEY `fk_obs_estudiante` (`estudiante_id`),
  KEY `fk_obs_psicologo` (`psicologo_id`),
  CONSTRAINT `fk_obs_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_obs_psicologo` FOREIGN KEY (`psicologo_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Módulo de Convivencia y Psicología 100% privado';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `observador_psicologia`
--

LOCK TABLES `observador_psicologia` WRITE;
/*!40000 ALTER TABLE `observador_psicologia` DISABLE KEYS */;
/*!40000 ALTER TABLE `observador_psicologia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `solicitudes_admision`
--

DROP TABLE IF EXISTS `solicitudes_admision`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes_admision` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `codigo_solicitud` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: SOL-2026-0001',
  `primer_nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segundo_nombre` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primer_apellido` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segundo_apellido` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_documento` enum('RC','TI','CC','CE','NES','Pasaporte') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TI',
  `documento_identidad` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` enum('Masculino','Femenino','Otro') COLLATE utf8mb4_unicode_ci DEFAULT 'Masculino',
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barrio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Ciénaga',
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grado_solicitado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: 10°, 11°, 6°, etc.',
  `jornada_solicitada` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Mañana',
  `sede_solicitada` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sede Principal',
  `curso_asignado_id` int(11) DEFAULT NULL COMMENT 'FK a cursos cuando se aprueba',
  `ano_lectivo` int(11) NOT NULL DEFAULT '2026',
  `colegio_origen` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acudiente_nombre` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acudiente_tipo_doc` enum('CC','CE','Pasaporte') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CC',
  `acudiente_documento` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acudiente_parentesco` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Padre/Madre',
  `acudiente_telefono` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acudiente_correo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acudiente_direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acudiente_ocupacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('Pendiente','Aprobada','Rechazada','Matriculado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `monto_matricula` decimal(12,2) NOT NULL DEFAULT '350000.00',
  `observaciones_revision` text COLLATE utf8mb4_unicode_ci,
  `motivo_rechazo` text COLLATE utf8mb4_unicode_ci,
  `recibo_caja_id` bigint(20) DEFAULT NULL COMMENT 'FK contabilidad_caja tras el pago',
  `usuario_estudiante_id` bigint(20) DEFAULT NULL COMMENT 'FK usuarios tras crear cuenta',
  `usuario_acudiente_id` bigint(20) DEFAULT NULL COMMENT 'FK usuarios acudiente si aplica',
  `matricula_id` bigint(20) DEFAULT NULL COMMENT 'FK matriculas tras crear registro formal',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_solicitud` (`codigo_solicitud`),
  KEY `idx_estado` (`estado`),
  KEY `idx_documento_est` (`documento_identidad`),
  KEY `idx_documento_acud` (`acudiente_documento`),
  KEY `idx_codigo` (`codigo_solicitud`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de solicitudes de inscripción y admisión de nuevos estudiantes';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `solicitudes_admision`
--

LOCK TABLES `solicitudes_admision` WRITE;
/*!40000 ALTER TABLE `solicitudes_admision` DISABLE KEYS */;
/*!40000 ALTER TABLE `solicitudes_admision` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `actas_convivencia`
--

DROP TABLE IF EXISTS `actas_convivencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `actas_convivencia` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `numero_acta` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_acta` enum('Tipo I','Tipo II','Tipo III') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tipo I',
  `estudiante_id` bigint(20) NOT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `creador_id` bigint(20) NOT NULL,
  `creador_rol` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `creador_nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_acta` date NOT NULL,
  `faltas_seleccionadas` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `descargos` text COLLATE utf8mb4_unicode_ci,
  `testigos` text COLLATE utf8mb4_unicode_ci,
  `afectados_o_bienes` text COLLATE utf8mb4_unicode_ci,
  `compromisos` text COLLATE utf8mb4_unicode_ci,
  `remision_entidad` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otros` text COLLATE utf8mb4_unicode_ci,
  `firmas_nombres` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_acta_est` (`estudiante_id`),
  KEY `idx_acta_tipo` (`tipo_acta`),
  KEY `idx_acta_fecha` (`fecha_acta`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actas_convivencia`
--

LOCK TABLES `actas_convivencia` WRITE;
/*!40000 ALTER TABLE `actas_convivencia` DISABLE KEYS */;
INSERT INTO `actas_convivencia` (`id`, `numero_acta`, `tipo_acta`, `estudiante_id`, `curso_id`, `creador_id`, `creador_rol`, `creador_nombre`, `fecha_acta`, `faltas_seleccionadas`, `descargos`, `testigos`, `afectados_o_bienes`, `compromisos`, `remision_entidad`, `otros`, `firmas_nombres`, `created_at`) VALUES 
('1','ACTA-2026-0001','Tipo I','7','23','221','coordinador','Coordinador General','2026-09-23','[\"2. Porte, consumo, distribución o facilitación de sustancias psicoactivas o bebidas alcohólicas.\"]','Versión de prueba','Sin testigos','Sin daños','Compromiso de puntualidad',NULL,NULL,'{\"alumno\":\"Anahy Ahumada\",\"docente\":\"Coordinación\"}','2026-09-23 22:30:13'),
('2','ACTA-2026-0001','Tipo I','7','23','221','coordinador','Coordinador General','2026-09-23','[\"2. Porte, consumo, distribución o facilitación de sustancias psicoactivas o bebidas alcohólicas.\"]','Versión de prueba','Sin testigos','Sin daños','Compromiso de puntualidad',NULL,NULL,'{\"alumno\":\"Anahy Ahumada\",\"docente\":\"Coordinación\"}','2026-09-23 22:34:00'),
('3','ACTA-2026-0001','Tipo I','7','23','221','coordinador','Coordinador General','2026-09-23','[\"2. Porte, consumo, distribución o facilitación de sustancias psicoactivas o bebidas alcohólicas.\"]','Versión de prueba','Sin testigos','Sin daños','Compromiso de puntualidad',NULL,NULL,'{\"alumno\":\"Anahy Ahumada\",\"docente\":\"Coordinación\"}','2026-09-23 23:28:10');
/*!40000 ALTER TABLE `actas_convivencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notificaciones_correo`
--

DROP TABLE IF EXISTS `notificaciones_correo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones_correo` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `destinatario_correo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destinatario_nombre` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_notificacion` enum('psicologia_citacion','cartera_cobro','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `asunto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cuerpo_html` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia_id` bigint(20) DEFAULT NULL COMMENT 'ID de citacion o de recibo/deuda',
  `estado_envio` enum('enviado','fallido','simulado') COLLATE utf8mb4_unicode_ci DEFAULT 'enviado',
  `fecha_envio` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notificaciones_correo`
--

LOCK TABLES `notificaciones_correo` WRITE;
/*!40000 ALTER TABLE `notificaciones_correo` DISABLE KEYS */;
INSERT INTO `notificaciones_correo` (`id`, `destinatario_correo`, `destinatario_nombre`, `tipo_notificacion`, `asunto`, `cuerpo_html`, `referencia_id`, `estado_envio`, `fecha_envio`) VALUES 
('9','acudiente.test@lasallecienaga.edu.co','González Laura','','CITACIÓN OFICIAL — Instituto La Salle: Acudiente de Matías Amaya','\n        <!DOCTYPE html>\n        <html lang=\'es\'>\n        <head>\n            <meta charset=\'UTF-8\'>\n            <style>\n                body { font-family: \'Segoe UI\', Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }\n                .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #cbd5e1; }\n                .email-header { background: linear-gradient(135deg, #0B3C68 0%, #1D568A 100%); color: #ffffff; padding: 25px 20px; text-align: center; }\n                .email-header h2 { margin: 0; font-size: 20px; letter-spacing: 0.5px; }\n                .email-body { padding: 28px 24px; font-size: 14px; line-height: 1.6; }\n                .data-card { background: #F8FAFC; border-left: 4px solid #0B3C68; padding: 16px; margin: 18px 0; border-radius: 4px; }\n                .data-row { margin-bottom: 8px; }\n                .data-label { font-weight: bold; color: #475569; width: 130px; display: inline-block; }\n                .motivo-box { background: #EFF6FF; border: 1px solid #BFDBFE; padding: 14px; border-radius: 6px; color: #1E3A8A; margin-top: 15px; }\n                .email-footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; }\n            </style>\n        </head>\n        <body>\n            <div class=\'email-container\'>\n                <div class=\'email-header\'>\n                    <h2>INSTITUTO LA SALLE</h2>\n                    <p style=\'margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;\'>Gestión Académica y Convivencia Escolar • Ciénaga Magdalena</p>\n                </div>\n                <div class=\'email-body\'>\n                    <p>Estimado(a) Sr.(a) <strong>González Laura</strong>,</p>\n                    <p>Reciba un cordial saludo de la Comunidad Educativa Lasallista. Por medio de la presente, se le convoca de manera formal a una citación institucional:</p>\n                    \n                    <div class=\'data-card\'>\n                        <div class=\'data-row\'><span class=\'data-label\'>Estudiante:</span> <strong>Matías Amaya</strong> (Grado 11° A)</div>\n                        <div class=\'data-row\'><span class=\'data-label\'>Fecha y Hora:</span> <strong style=\'color:#0B3C68;\'>25/09/2026 08:30 AM</strong></div>\n                        <div class=\'data-row\'><span class=\'data-label\'>Modalidad:</span> <strong>Presencial — Coordinación</strong></div>\n                        <div class=\'data-row\'><span class=\'data-label\'>Convocante:</span> <strong>Coordinador de Convivencia</strong></div>\n                    </div>\n\n                    <p><strong>Motivo / Asunto de la Citación:</strong></p>\n                    <div class=\'motivo-box\'>\n                        Revisión y firma de compromisos convivenciales y académicos.\n                    </div>\n\n                    <p style=\'margin-top: 20px; font-size: 13px; color: #64748B;\'>\n                        Agradecemos su puntual asistencia y compromiso con el proceso formativo de su acudido(a).\n                    </p>\n                </div>\n                <div class=\'email-footer\'>\n                    Instituto La Salle • Ciénaga Magdalena • Notificación Oficial Generada por AulaVirtual Salle\n                </div>\n            </div>\n        </body>\n        </html>\n        ',NULL,'simulado','2026-09-23 23:36:15'),
('10','acudiente.test@lasallecienaga.edu.co','González Laura','','NOTIFICACIÓN OFICIAL: Acta de Convivencia Escolar (Tipo I) — Estudiante Matías Amaya','\n        <!DOCTYPE html>\n        <html lang=\'es\'>\n        <head>\n            <meta charset=\'UTF-8\'>\n            <style>\n                body { font-family: \'Segoe UI\', Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }\n                .email-container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #cbd5e1; }\n                .email-header { background: linear-gradient(135deg, #0B3C68 0%, #1D568A 100%); color: #ffffff; padding: 25px 20px; text-align: center; }\n                .email-body { padding: 28px 24px; font-size: 14px; line-height: 1.6; }\n                .acta-badge { display: inline-block; background: #2563EB; color: #ffffff; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; margin-bottom: 15px; }\n                .data-card { background: #F8FAFC; border-left: 4px solid #2563EB; padding: 16px; margin: 16px 0; border-radius: 4px; }\n                .data-row { margin-bottom: 8px; }\n                .data-label { font-weight: bold; color: #475569; width: 140px; display: inline-block; }\n                .box-compromisos { background: #F0FDF4; border: 1px solid #BBF7D0; padding: 12px; border-radius: 6px; color: #166534; margin: 10px 0; }\n                .box-descargos { background: #F1F5F9; padding: 12px; border-radius: 6px; font-style: italic; color: #334155; margin: 10px 0; }\n                .email-footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; }\n            </style>\n        </head>\n        <body>\n            <div class=\'email-container\'>\n                <div class=\'email-header\'>\n                    <h2 style=\'margin: 0; font-size: 20px;\'>INSTITUTO LA SALLE</h2>\n                    <p style=\'margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;\'>Comité de Convivencia Escolar • Ley 1620 de 2013</p>\n                </div>\n                <div class=\'email-body\'>\n                    <div class=\'acta-badge\'>ACTA DE CONVIVENCIA N° ACTA-2026-0005 — Tipo I</div>\n                    <p>Estimado(a) Acudiente <strong>González Laura</strong>,</p>\n                    <p>Le notificamos que en el seguimiento formativo del estudiante se ha formalizado la siguiente acta de convivencia escolar:</p>\n                    \n                    <div class=\'data-card\'>\n                        <div class=\'data-row\'><span class=\'data-label\'>Estudiante:</span> <strong>Matías Amaya</strong> (Grado 11° A)</div>\n                        <div class=\'data-row\'><span class=\'data-label\'>Fecha del Acta:</span> <strong>24/09/2026</strong></div>\n                        <div class=\'data-row\'><span class=\'data-label\'>Diligenciado por:</span> <strong>Coordinación de Convivencia</strong></div>\n                    </div>\n\n                    <p><strong>Falta(s) o Situación(es) Registrada(s):</strong></p>\n                    <div style=\'background: #FFFBEB; border: 1px solid #FDE68A; padding: 12px; border-radius: 6px;\'>\n                        <ul style=\"margin: 0; padding-left: 20px; line-height: 1.6;\"><li>1. Llegar tarde al colegio o al aula de clase.</li></ul>\n                    </div>\n\n                    <p style=\'margin-top: 14px;\'><strong>Versión / Descargos del Estudiante:</strong></p><div class=\'box-descargos\'>Versión registrada en observador.</div>\n\n                    <p style=\'margin-top: 14px;\'><strong>Acciones Pedagógicas y Compromisos Acordados:</strong></p><div class=\'box-compromisos\'>Llegar puntual.</div>\n\n                    <p style=\'margin-top: 18px; font-size: 13px; color: #64748B;\'>\n                        Este documento reposa en el observador escolar del estudiante. Si requiere dialogar al respecto, puede contactar a Coordinación de Convivencia.\n                    </p>\n                </div>\n                <div class=\'email-footer\'>\n                    Instituto La Salle Ciénaga Magdalena • Notificación Oficial del Sistema de Convivencia Escolar\n                </div>\n            </div>\n        </body>\n        </html>\n        ',NULL,'simulado','2026-09-23 23:36:19'),
('11','acudiente.test@lasallecienaga.edu.co','González Laura','','CITACIÓN OFICIAL — Instituto La Salle: Acudiente de Matías Amaya','\n        <!DOCTYPE html>\n        <html lang=\'es\'>\n        <head>\n            <meta charset=\'UTF-8\'>\n            <style>\n                body { font-family: \'Segoe UI\', Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }\n                .email-container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #cbd5e1; }\n                .email-header { background: linear-gradient(135deg, #0B3C68 0%, #1D568A 100%); color: #ffffff; padding: 25px 20px; text-align: center; }\n                .email-header h2 { margin: 0; font-size: 20px; letter-spacing: 0.5px; }\n                .email-body { padding: 28px 24px; font-size: 14px; line-height: 1.6; }\n                .data-card { background: #F8FAFC; border-left: 4px solid #0B3C68; padding: 16px; margin: 18px 0; border-radius: 4px; }\n                .data-row { margin-bottom: 8px; }\n                .data-label { font-weight: bold; color: #475569; width: 130px; display: inline-block; }\n                .motivo-box { background: #EFF6FF; border: 1px solid #BFDBFE; padding: 14px; border-radius: 6px; color: #1E3A8A; margin-top: 15px; }\n                .email-footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; }\n            </style>\n        </head>\n        <body>\n            <div class=\'email-container\'>\n                <div class=\'email-header\'>\n                    <h2>INSTITUTO LA SALLE</h2>\n                    <p style=\'margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;\'>Gestión Académica y Convivencia Escolar • Ciénaga Magdalena</p>\n                </div>\n                <div class=\'email-body\'>\n                    <p>Estimado(a) Sr.(a) <strong>González Laura</strong>,</p>\n                    <p>Reciba un cordial saludo de la Comunidad Educativa Lasallista. Por medio de la presente, se le convoca de manera formal a una citación institucional:</p>\n                    \n                    <div class=\'data-card\'>\n                        <div class=\'data-row\'><span class=\'data-label\'>Estudiante:</span> <strong>Matías Amaya</strong> (Grado 11° A)</div>\n                        <div class=\'data-row\'><span class=\'data-label\'>Fecha y Hora:</span> <strong style=\'color:#0B3C68;\'>25/09/2026 08:30 AM</strong></div>\n                        <div class=\'data-row\'><span class=\'data-label\'>Modalidad:</span> <strong>Presencial — Coordinación</strong></div>\n                        <div class=\'data-row\'><span class=\'data-label\'>Convocante:</span> <strong>Coordinador de Convivencia</strong></div>\n                    </div>\n\n                    <p><strong>Motivo / Asunto de la Citación:</strong></p>\n                    <div class=\'motivo-box\'>\n                        Revisión y firma de compromisos convivenciales y académicos.\n                    </div>\n\n                    <p style=\'margin-top: 20px; font-size: 13px; color: #64748B;\'>\n                        Agradecemos su puntual asistencia y compromiso con el proceso formativo de su acudido(a).\n                    </p>\n                </div>\n                <div class=\'email-footer\'>\n                    Instituto La Salle • Ciénaga Magdalena • Notificación Oficial Generada por AulaVirtual Salle\n                </div>\n            </div>\n        </body>\n        </html>\n        ',NULL,'simulado','2026-09-23 23:37:47'),
('12','acudiente.test@lasallecienaga.edu.co','González Laura','','NOTIFICACIÓN OFICIAL: Acta de Convivencia Escolar (Tipo I) — Estudiante Matías Amaya','\n        <!DOCTYPE html>\n        <html lang=\'es\'>\n        <head>\n            <meta charset=\'UTF-8\'>\n            <style>\n                body { font-family: \'Segoe UI\', Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }\n                .email-container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #cbd5e1; }\n                .email-header { background: linear-gradient(135deg, #0B3C68 0%, #1D568A 100%); color: #ffffff; padding: 25px 20px; text-align: center; }\n                .email-body { padding: 28px 24px; font-size: 14px; line-height: 1.6; }\n                .acta-badge { display: inline-block; background: #2563EB; color: #ffffff; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; margin-bottom: 15px; }\n                .data-card { background: #F8FAFC; border-left: 4px solid #2563EB; padding: 16px; margin: 16px 0; border-radius: 4px; }\n                .data-row { margin-bottom: 8px; }\n                .data-label { font-weight: bold; color: #475569; width: 140px; display: inline-block; }\n                .box-compromisos { background: #F0FDF4; border: 1px solid #BBF7D0; padding: 12px; border-radius: 6px; color: #166534; margin: 10px 0; }\n                .box-descargos { background: #F1F5F9; padding: 12px; border-radius: 6px; font-style: italic; color: #334155; margin: 10px 0; }\n                .email-footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; }\n            </style>\n        </head>\n        <body>\n            <div class=\'email-container\'>\n                <div class=\'email-header\'>\n                    <h2 style=\'margin: 0; font-size: 20px;\'>INSTITUTO LA SALLE</h2>\n                    <p style=\'margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;\'>Comité de Convivencia Escolar • Ley 1620 de 2013</p>\n                </div>\n                <div class=\'email-body\'>\n                    <div class=\'acta-badge\'>ACTA DE CONVIVENCIA N° ACTA-2026-0005 — Tipo I</div>\n                    <p>Estimado(a) Acudiente <strong>González Laura</strong>,</p>\n                    <p>Le notificamos que en el seguimiento formativo del estudiante se ha formalizado la siguiente acta de convivencia escolar:</p>\n                    \n                    <div class=\'data-card\'>\n                        <div class=\'data-row\'><span class=\'data-label\'>Estudiante:</span> <strong>Matías Amaya</strong> (Grado 11° A)</div>\n                        <div class=\'data-row\'><span class=\'data-label\'>Fecha del Acta:</span> <strong>24/09/2026</strong></div>\n                        <div class=\'data-row\'><span class=\'data-label\'>Diligenciado por:</span> <strong>Coordinación de Convivencia</strong></div>\n                    </div>\n\n                    <p><strong>Falta(s) o Situación(es) Registrada(s):</strong></p>\n                    <div style=\'background: #FFFBEB; border: 1px solid #FDE68A; padding: 12px; border-radius: 6px;\'>\n                        <ul style=\"margin: 0; padding-left: 20px; line-height: 1.6;\"><li>1. Llegar tarde al colegio o al aula de clase.</li></ul>\n                    </div>\n\n                    <p style=\'margin-top: 14px;\'><strong>Versión / Descargos del Estudiante:</strong></p><div class=\'box-descargos\'>Versión registrada en observador.</div>\n\n                    <p style=\'margin-top: 14px;\'><strong>Acciones Pedagógicas y Compromisos Acordados:</strong></p><div class=\'box-compromisos\'>Llegar puntual.</div>\n\n                    <p style=\'margin-top: 18px; font-size: 13px; color: #64748B;\'>\n                        Este documento reposa en el observador escolar del estudiante. Si requiere dialogar al respecto, puede contactar a Coordinación de Convivencia.\n                    </p>\n                </div>\n                <div class=\'email-footer\'>\n                    Instituto La Salle Ciénaga Magdalena • Notificación Oficial del Sistema de Convivencia Escolar\n                </div>\n            </div>\n        </body>\n        </html>\n        ',NULL,'simulado','2026-09-23 23:37:51');
/*!40000 ALTER TABLE `notificaciones_correo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluacion_docente_periodos`
--

DROP TABLE IF EXISTS `evaluacion_docente_periodos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluacion_docente_periodos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Evaluación Docente 2026 - Período 1',
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `estado` enum('activo','inactivo','cerrado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `ano_lectivo` int(11) NOT NULL DEFAULT '2026',
  `periodo_academico` int(11) NOT NULL DEFAULT '1',
  `indicaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluacion_docente_periodos`
--

LOCK TABLES `evaluacion_docente_periodos` WRITE;
/*!40000 ALTER TABLE `evaluacion_docente_periodos` DISABLE KEYS */;
INSERT INTO `evaluacion_docente_periodos` (`id`, `titulo`, `fecha_inicio`, `fecha_fin`, `estado`, `ano_lectivo`, `periodo_academico`, `indicaciones`, `created_at`) VALUES 
('1','Evaluación Institucional Docente 2026 - I Semestre','2026-02-01 08:00:00','2026-12-15 23:59:59','activo','2026','1','Apreciado estudiante: califica a tus docentes con sinceridad y respeto para continuar mejorando la calidad pedagógica salle.','2026-10-01 13:14:26');
/*!40000 ALTER TABLE `evaluacion_docente_periodos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluaciones_docentes`
--

DROP TABLE IF EXISTS `evaluaciones_docentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluaciones_docentes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `periodo_id` int(11) NOT NULL,
  `estudiante_id` bigint(20) NOT NULL,
  `docente_id` bigint(20) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `asignatura_id` int(11) DEFAULT NULL,
  `criterio_dominio` tinyint(4) NOT NULL DEFAULT '5',
  `criterio_metodologia` tinyint(4) NOT NULL DEFAULT '5',
  `criterio_respeto` tinyint(4) NOT NULL DEFAULT '5',
  `criterio_evaluacion` tinyint(4) NOT NULL DEFAULT '5',
  `criterio_motivacion` tinyint(4) NOT NULL DEFAULT '5',
  `puntaje_promedio` decimal(3,2) NOT NULL DEFAULT '5.00',
  `comentarios` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_eval_est_doc_per` (`periodo_id`,`estudiante_id`,`docente_id`),
  KEY `idx_eval_doc` (`docente_id`),
  KEY `idx_eval_per` (`periodo_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluaciones_docentes`
--

LOCK TABLES `evaluaciones_docentes` WRITE;
/*!40000 ALTER TABLE `evaluaciones_docentes` DISABLE KEYS */;
INSERT INTO `evaluaciones_docentes` (`id`, `periodo_id`, `estudiante_id`, `docente_id`, `curso_id`, `asignatura_id`, `criterio_dominio`, `criterio_metodologia`, `criterio_respeto`, `criterio_evaluacion`, `criterio_motivacion`, `puntaje_promedio`, `comentarios`, `created_at`, `creado_en`) VALUES 
('1','1','5','3','1',NULL,'5','5','5','4','5','4.70','Prueba de evaluación docente sin errores de columnas','2026-10-01 16:02:33','2026-10-01 16:02:33'),
('2','1','191','3','1',NULL,'4','5','4','4','4','4.20','Utiliza muy buenas dinámicas y herramientas virtuales. Clases muy entretenidas.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('3','1','192','3','1',NULL,'4','4','4','5','4','4.20','Siempre llega puntual y atiende todas las dudas con mucho respeto.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('4','1','193','3','1',NULL,'5','4','5','5','5','4.80','Sus evaluaciones son muy coherentes con lo enseñado en el período.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('5','1','5','7','1',NULL,'5','4','4','5','4','4.40','Excelente profesor, explica con claridad y paciencia en cada clase.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('6','1','191','7','1',NULL,'4','5','4','4','5','4.40','Utiliza muy buenas dinámicas y herramientas virtuales. Clases muy entretenidas.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('7','1','192','7','1',NULL,'5','5','4','4','4','4.40','Siempre llega puntual y atiende todas las dudas con mucho respeto.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('8','1','193','7','1',NULL,'5','4','5','4','4','4.40','Sus evaluaciones son muy coherentes con lo enseñado en el período.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('9','1','5','168','1',NULL,'4','4','4','4','4','4.00','Excelente profesor, explica con claridad y paciencia en cada clase.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('10','1','191','168','1',NULL,'5','4','4','4','4','4.20','Utiliza muy buenas dinámicas y herramientas virtuales. Clases muy entretenidas.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('11','1','192','168','1',NULL,'4','5','5','5','4','4.60','Siempre llega puntual y atiende todas las dudas con mucho respeto.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('12','1','193','168','1',NULL,'4','5','4','4','5','4.40','Sus evaluaciones son muy coherentes con lo enseñado en el período.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('13','1','5','169','1',NULL,'4','5','4','4','5','4.40','Excelente profesor, explica con claridad y paciencia en cada clase.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('14','1','191','169','1',NULL,'5','4','5','5','4','4.60','Utiliza muy buenas dinámicas y herramientas virtuales. Clases muy entretenidas.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('15','1','192','169','1',NULL,'5','4','5','4','4','4.40','Siempre llega puntual y atiende todas las dudas con mucho respeto.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('16','1','193','169','1',NULL,'4','5','5','5','5','4.80','Sus evaluaciones son muy coherentes con lo enseñado en el período.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('17','1','5','170','1',NULL,'5','4','4','5','4','4.40','Excelente profesor, explica con claridad y paciencia en cada clase.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('18','1','191','170','1',NULL,'4','5','5','5','4','4.60','Utiliza muy buenas dinámicas y herramientas virtuales. Clases muy entretenidas.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('19','1','192','170','1',NULL,'5','4','5','4','4','4.40','Siempre llega puntual y atiende todas las dudas con mucho respeto.','2026-10-01 13:14:26','2026-10-01 15:59:10'),
('20','1','193','170','1',NULL,'4','4','5','5','4','4.40','Sus evaluaciones son muy coherentes con lo enseñado en el período.','2026-10-01 13:14:26','2026-10-01 15:59:10');
/*!40000 ALTER TABLE `evaluaciones_docentes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_migracion_legacy`
--

DROP TABLE IF EXISTS `log_migracion_legacy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_migracion_legacy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_ejecucion` datetime DEFAULT CURRENT_TIMESTAMP,
  `registros_exitosos` int(11) DEFAULT '0',
  `registros_omitidos` int(11) DEFAULT '0',
  `detalles_errores` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_migracion_legacy`
--

LOCK TABLES `log_migracion_legacy` WRITE;
/*!40000 ALTER TABLE `log_migracion_legacy` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_migracion_legacy` ENABLE KEYS */;
UNLOCK TABLES;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
-- Dump completed on 2026-10-01 21:02:55
