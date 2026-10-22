<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();
$cols = $pdo->query("SHOW COLUMNS FROM solicitudes_admision")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('jornada_solicitada', $cols)) {
    $pdo->exec("ALTER TABLE solicitudes_admision ADD COLUMN `jornada_solicitada` VARCHAR(30) NOT NULL DEFAULT 'Mañana' AFTER `grado_solicitado`");
    echo "✓ Columna jornada_solicitada agregada a solicitudes_admision.\n";
} else {
    echo "✓ Columna jornada_solicitada ya existe.\n";
}
