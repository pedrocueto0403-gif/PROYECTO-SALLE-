<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();
$teachers = $pdo->query("
    SELECT u.id, u.correo, p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
           p.tipo_documento, p.documento_identidad, p.telefono, p.direccion
    FROM usuarios u
    LEFT JOIN perfiles_detalles p ON u.id = p.usuario_id
    WHERE u.rol_id = 3
    ORDER BY p.primer_apellido, p.primer_nombre
")->fetchAll(PDO::FETCH_ASSOC);

echo "Total teachers: " . count($teachers) . "\n";
foreach (array_slice($teachers, 0, 5) as $t) {
    echo "ID: {$t['id']}, Name: {$t['primer_nombre']} {$t['primer_apellido']}, Doc: {$t['tipo_documento']} {$t['documento_identidad']}, Email: {$t['correo']}\n";
}
