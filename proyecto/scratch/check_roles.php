<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();

echo "=== ROLES IN DATABASE ===\n";
$roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
print_r($roles);

echo "\n=== DEMO USERS OR USERS PER ROLE ===\n";
$users = $pdo->query("SELECT u.id, u.correo, u.rol_id, r.nombre_rol, p.primer_nombre, p.primer_apellido 
                      FROM usuarios u 
                      JOIN roles r ON u.rol_id = r.id 
                      LEFT JOIN perfiles_detalles p ON u.id = p.usuario_id 
                      GROUP BY u.rol_id")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
