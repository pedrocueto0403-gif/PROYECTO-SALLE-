<?php
require_once 'practicas antigrabiti/config/config.php';
require_once 'practicas antigrabiti/config/Database.php';

$pdo = Database::getInstance()->getConnection();

echo "=== PROBANDO IMPORTACIÓN DE DUMP EN BASE DE DATOS TEMPORAL ===\n";

try {
    $pdo->exec("DROP DATABASE IF EXISTS `test_import_validation`");
    $pdo->exec("CREATE DATABASE `test_import_validation` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $testPdo = new PDO('mysql:host=localhost;port=' . DB_PORT . ';dbname=test_import_validation;charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $sql = file_get_contents('aulavir7_Aulavirtualsalle.sql');
    
    // Execute the entire SQL script
    $testPdo->exec($sql);
    echo "✅ ÉXITO TOTAL: aulavir7_Aulavirtualsalle.sql se importó SIN ERRORES.\n";

    // Verify tables count in test DB
    $tables = $testPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Total tablas importadas: " . count($tables) . " (" . implode(', ', $tables) . ")\n";

    // Verify foreign key integrity on matriculas
    $matCount = $testPdo->query("
        SELECT COUNT(*) 
        FROM matriculas m 
        INNER JOIN usuarios u ON m.estudiante_id = u.id 
        INNER JOIN cursos c ON m.curso_id = c.id
    ")->fetchColumn();
    echo "Matrículas vinculadas correctamente con claves foráneas: $matCount / 24\n";

    // Verify coordinador user and role
    $coord = $testPdo->query("SELECT u.id, u.correo, r.nombre_rol FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id WHERE u.correo = 'coordinacion@lasallecienaga.edu.co'")->fetch(PDO::FETCH_ASSOC);
    echo "Coordinador en BD importada: " . json_encode($coord) . "\n";

    // Clean up test DB
    $pdo->exec("DROP DATABASE `test_import_validation`");
    echo "Base de datos temporal de prueba limpiada.\n";
} catch (Exception $e) {
    echo "❌ ERROR EN IMPORTACIÓN: " . $e->getMessage() . "\n";
}
