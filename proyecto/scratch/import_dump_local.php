<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=aulavirtual_salle;charset=utf8mb4', 'root', '123456789');
$sql = file_get_contents(__DIR__ . '/../../aulavir7_Aulavirtualsalle.sql');
echo "Importing SQL dump (size: " . strlen($sql) . " bytes)...\n";
$pdo->exec($sql);
echo "Import complete!\n";

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables imported: " . count($tables) . "\n";
print_r($tables);
