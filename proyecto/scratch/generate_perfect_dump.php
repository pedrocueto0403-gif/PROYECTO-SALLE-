<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

echo "=== GENERANDO DUMP PERFECTO DE LA BASE DE DATOS ===\n";

$tables = [
    'roles',
    'usuarios',
    'perfiles_detalles',
    'cursos',
    'asignaturas',
    'curso_asignatura_docente',
    'matriculas',
    'asistencias',
    'calificaciones_logros',
    'banco_logros',
    'citaciones_acudientes',
    'contabilidad_caja',
    'contabilidad_egresos',
    'contratos_prestacion',
    'planes_clase',
    'observador_estudiante',
    'observador_psicologia',
    'solicitudes_admision',
    'actas_convivencia',
    'notificaciones_correo',
    'evaluacion_docente_periodos',
    'evaluaciones_docentes',
    'log_migracion_legacy'
];

$sql = "-- MariaDB / MySQL dump 10.19\n";
$sql .= "-- Base de datos: `aulavir7_Aulavirtualsalle` / `aulavirtual_salle`\n";
$sql .= "-- Instituto La Salle Ciénaga Magdalena\n";
$sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- ------------------------------------------------------\n\n";
$sql .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
$sql .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
$sql .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
$sql .= "/*!50503 SET NAMES utf8mb4 */;\n";
$sql .= "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n";
$sql .= "/*!40103 SET TIME_ZONE='+00:00' */;\n";
$sql .= "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n";
$sql .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
$sql .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n";
$sql .= "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n\n";

foreach ($tables as $t) {
    // Check if table exists in DB
    $chk = $pdo->query("SHOW TABLES LIKE '$t'")->fetch();
    if (!$chk) continue;

    $sql .= "--\n-- Table structure for table `$t`\n--\n\n";
    $sql .= "DROP TABLE IF EXISTS `$t`;\n";
    $sql .= "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n";
    $sql .= "/*!50503 SET character_set_client = utf8mb4 */;\n";

    $createRow = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC);
    $sql .= $createRow['Create Table'] . ";\n";
    $sql .= "/*!40101 SET character_set_client = @saved_cs_client */;\n\n";

    // Data dumping
    $sql .= "--\n-- Dumping data for table `$t`\n--\n\n";
    $sql .= "LOCK TABLES `$t` WRITE;\n";
    $sql .= "/*!40000 ALTER TABLE `$t` DISABLE KEYS */;\n";

    // Fetch column names
    $colStmt = $pdo->query("SHOW COLUMNS FROM `$t`");
    $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
    $colListStr = "`" . implode("`, `", $columns) . "`";

    $rows = $pdo->query("SELECT * FROM `$t`")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $insertValues = [];
        foreach ($rows as $row) {
            $vals = [];
            foreach ($columns as $col) {
                $val = $row[$col];
                if (is_null($val)) {
                    $vals[] = "NULL";
                } elseif (is_numeric($val) && !is_string($val)) {
                    $vals[] = $val;
                } else {
                    $vals[] = $pdo->quote($val);
                }
            }
            $insertValues[] = "(" . implode(",", $vals) . ")";
        }

        // Chunk inserts by 100 rows for readability and execution speed
        $chunks = array_chunk($insertValues, 100);
        foreach ($chunks as $chunk) {
            $sql .= "INSERT INTO `$t` ($colListStr) VALUES \n" . implode(",\n", $chunk) . ";\n";
        }
    }

    $sql .= "/*!40000 ALTER TABLE `$t` ENABLE KEYS */;\n";
    $sql .= "UNLOCK TABLES;\n\n";
}

$sql .= "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n";
$sql .= "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n";
$sql .= "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n";
$sql .= "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n";
$sql .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
$sql .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
$sql .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";
$sql .= "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n";
$sql .= "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";

$targetPaths = [
    __DIR__ . '/../../aulavir7_Aulavirtualsalle.sql',
    __DIR__ . '/../database/database.sql',
    'C:/AppServ/www/practicas antigrabiti/database/database.sql'
];

foreach ($targetPaths as $tp) {
    file_put_contents($tp, $sql);
    echo "Saved " . strlen($sql) . " bytes to $tp\n";
}

echo "=== DUMP GENERADO CON ÉXITO ===\n";
