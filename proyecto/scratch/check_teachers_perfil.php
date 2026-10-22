<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$teachersAll = $pdo->query("SELECT u.id, u.correo, u.rol_id, p.id as perfil_id, p.primer_nombre, p.primer_apellido FROM usuarios u LEFT JOIN perfiles_detalles p ON u.id = p.usuario_id WHERE u.rol_id = 3")->fetchAll(PDO::FETCH_ASSOC);

echo "Total teachers in usuarios (rol_id = 3): " . count($teachersAll) . "\n";
$withPerfil = 0;
$withoutPerfil = 0;
foreach ($teachersAll as $t) {
    if ($t['perfil_id']) {
        $withPerfil++;
    } else {
        $withoutPerfil++;
        echo "Teacher WITHOUT perfil: ID {$t['id']}, email: {$t['correo']}\n";
    }
}
echo "With perfil: $withPerfil, Without perfil: $withoutPerfil\n";
