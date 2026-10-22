<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "ALL TABLES:\n" . implode(', ', $tables) . "\n\n";

foreach ($tables as $t) {
    if (preg_match('/(calif|nota|logro|boletin|eval)/i', $t)) {
        echo "=== Table: $t ===\n";
        $cols = $pdo->query("SHOW COLUMNS FROM $t")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo "  - {$c['Field']} ({$c['Type']})\n";
        }
        $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo "  Count: $count rows\n\n";
    }
}
