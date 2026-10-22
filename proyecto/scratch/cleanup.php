<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();
if ($pdo) {
    $pdo->exec("DELETE FROM solicitudes_admision WHERE documento_identidad = '1122334455'");
    echo "Cleaned up.\n";
}
