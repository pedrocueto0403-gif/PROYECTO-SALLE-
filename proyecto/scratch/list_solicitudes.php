<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();
if ($pdo) {
    echo "--- aulavirtual_salle ---\n";
    try {
        $rows = $pdo->query("SELECT id, codigo_solicitud, primer_nombre, primer_apellido, documento_identidad, estado, created_at FROM aulavirtual_salle.solicitudes_admision")->fetchAll(PDO::FETCH_ASSOC);
        echo "Count: " . count($rows) . "\n";
        foreach ($rows as $r) {
            echo "ID: {$r['id']} | Radicado: {$r['codigo_solicitud']} | Doc: {$r['documento_identidad']} | Nombre: {$r['primer_nombre']} {$r['primer_apellido']} | Estado: {$r['estado']}\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    echo "\n--- aulavir7_aulavirtualsalle ---\n";
    try {
        $rows2 = $pdo->query("SELECT id, codigo_solicitud, primer_nombre, primer_apellido, documento_identidad, estado, created_at FROM `aulavir7_aulavirtualsalle`.solicitudes_admision")->fetchAll(PDO::FETCH_ASSOC);
        echo "Count: " . count($rows2) . "\n";
        foreach ($rows2 as $r) {
            echo "ID: {$r['id']} | Radicado: {$r['codigo_solicitud']} | Doc: {$r['documento_identidad']} | Nombre: {$r['primer_nombre']} {$r['primer_apellido']} | Estado: {$r['estado']}\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
