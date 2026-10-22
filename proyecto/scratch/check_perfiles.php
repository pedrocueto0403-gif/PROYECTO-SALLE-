<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();

echo "=== COLUMNS IN perfiles_detalles ===\n";
print_r($pdo->query("DESCRIBE perfiles_detalles")->fetchAll(PDO::FETCH_COLUMN));

echo "\n=== SAMPLE FROM perfiles_detalles ===\n";
print_r($pdo->query("SELECT * FROM perfiles_detalles LIMIT 3")->fetchAll(PDO::FETCH_ASSOC));
