<?php
require_once 'practicas antigrabiti/config/config.php';
require_once 'practicas antigrabiti/config/Database.php';

$pdo = Database::getInstance()->getConnection();

echo "Checking user 220 in DB:\n";
$u = $pdo->query("SELECT * FROM usuarios WHERE id = 220")->fetch(PDO::FETCH_ASSOC);
var_dump($u);

$p = $pdo->query("SELECT * FROM perfiles_detalles WHERE usuario_id = 220")->fetch(PDO::FETCH_ASSOC);
var_dump($p);

$m = $pdo->query("SELECT * FROM matriculas WHERE estudiante_id = 220")->fetch(PDO::FETCH_ASSOC);
var_dump($m);

// Let's check in the dump files what line has 220
$content = file_get_contents('aulavir7_Aulavirtualsalle.sql');
foreach (explode("\n", $content) as $num => $line) {
    if (strpos($line, '220') !== false) {
        echo "Line " . ($num + 1) . ": " . substr(trim($line), 0, 120) . "\n";
    }
}
