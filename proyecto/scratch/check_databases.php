<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();
if ($pdo) {
    foreach (['aulavirtual_salle', 'aulavir7_aulavirtualsalle'] as $db) {
        try {
            $cols = $pdo->query("DESCRIBE `{$db}`.`solicitudes_admision`")->fetchAll(PDO::FETCH_ASSOC);
            echo "DB `{$db}` has `solicitudes_admision` with " . count($cols) . " columns.\n";
        } catch (Exception $e) {
            echo "DB `{$db}`: " . $e->getMessage() . "\n";
        }
    }
}
