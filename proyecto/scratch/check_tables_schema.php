<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();

echo "=== COLUMNS IN usuarios ===\n";
print_r($pdo->query("DESCRIBE usuarios")->fetchAll(PDO::FETCH_COLUMN));

echo "\n=== ROLES ===\n";
print_r($pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== MATRICULAS COLUMNS ===\n";
print_r($pdo->query("DESCRIBE matriculas")->fetchAll(PDO::FETCH_COLUMN));

echo "\n=== CURSOS COLUMNS ===\n";
print_r($pdo->query("DESCRIBE cursos")->fetchAll(PDO::FETCH_COLUMN));

echo "\n=== CONTRATOS PRESTACION ===\n";
print_r($pdo->query("DESCRIBE contratos_prestacion")->fetchAll(PDO::FETCH_COLUMN));
