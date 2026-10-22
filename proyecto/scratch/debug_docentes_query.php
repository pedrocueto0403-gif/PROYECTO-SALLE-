<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();

try {
    $docentes = $pdo->query("
        SELECT u.id, u.correo,
               p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
               p.tipo_documento, p.documento_identidad, p.telefono, p.direccion
        FROM usuarios u
        INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
        WHERE u.rol_id = 3
        ORDER BY p.primer_apellido, p.primer_nombre
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo "SUCCESS! Docentes found: " . count($docentes) . "\n";
    foreach ($docentes as $d) {
        echo "- {$d['primer_apellido']} {$d['primer_nombre']} ({$d['tipo_documento']} {$d['documento_identidad']})\n";
    }
} catch (Exception $e) {
    echo "QUERY FAILED: " . $e->getMessage() . "\n";
}
