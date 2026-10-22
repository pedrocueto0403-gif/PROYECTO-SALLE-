<?php
require_once 'practicas antigrabiti/config/config.php';
require_once 'practicas antigrabiti/config/Database.php';

$pdo = Database::getInstance()->getConnection();

$dbName = 'aulavir7_aulavirtualsalle';

echo "=== GENERANDO EXPORTADOR SQL ROBUSTO Y SIN ERRORES ===\n";

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
    'planes_clase',
    'solicitudes_admision',
    'actas_convivencia'
];

$sql = "-- phpMyAdmin SQL Dump\n";
$sql .= "-- Base de datos: `aulavir7_Aulavirtualsalle` e institucional AulaVirtual\n";
$sql .= "-- Generado automáticamente con integridad referencial completa\n";
$sql .= "-- --------------------------------------------------------\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
$sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sql .= "START TRANSACTION;\n";
$sql .= "SET time_zone = \"+00:00\";\n\n";
$sql .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
$sql .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
$sql .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
$sql .= "/*!40101 SET NAMES utf8mb4 */;\n\n";

foreach ($tables as $table) {
    // Check if table exists
    $exists = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
    if (!$exists) continue;

    echo "Exportando tabla `$table`... ";

    $sql .= "-- --------------------------------------------------------\n";
    $sql .= "-- Estructura de tabla para la tabla `$table`\n";
    $sql .= "-- --------------------------------------------------------\n\n";
    $sql .= "DROP TABLE IF EXISTS `$table`;\n";

    $createRow = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    $createTableSql = $createRow['Create Table'];
    $sql .= $createTableSql . ";\n\n";

    // Export Data
    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    $count = count($rows);
    echo "$count registros.\n";

    if ($count > 0) {
        $sql .= "-- Volcado de datos para la tabla `$table`\n\n";
        
        $colNames = array_keys($rows[0]);
        $colsList = "`" . implode("`, `", $colNames) . "`";

        $chunkSize = 50;
        $chunks = array_chunk($rows, $chunkSize);

        foreach ($chunks as $chunk) {
            $sql .= "INSERT INTO `$table` ($colsList) VALUES\n";
            $valRows = [];
            foreach ($chunk as $r) {
                $vals = [];
                foreach ($r as $val) {
                    if ($val === null) {
                        $vals[] = "NULL";
                    } elseif (is_numeric($val) && !is_string($val)) {
                        $vals[] = $val;
                    } else {
                        $vals[] = $pdo->quote($val);
                    }
                }
                $valRows[] = "(" . implode(", ", $vals) . ")";
            }
            $sql .= implode(",\n", $valRows) . ";\n\n";
        }
    }
}

$sql .= "-- --------------------------------------------------------\n";
$sql .= "COMMIT;\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=1;\n\n";
$sql .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
$sql .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
$sql .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";

// Guardar en ambos archivos
$dest1 = 'aulavir7_Aulavirtualsalle.sql';
$dest2 = 'practicas antigrabiti/database/database.sql';

file_put_contents($dest1, $sql);
file_put_contents($dest2, $sql);

echo "\nArchivos generados exitosamente:\n";
echo "1. $dest1 (" . strlen($sql) . " bytes)\n";
echo "2. $dest2 (" . strlen($sql) . " bytes)\n";
