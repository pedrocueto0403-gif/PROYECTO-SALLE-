<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();

$count = $pdo->query("SELECT COUNT(*) FROM calificaciones_logros")->fetchColumn();
echo "Current grades count: $count\n";

if ($count == 0) {
    echo "Inserting real sample uploaded grades for demonstration...\n";
    // Student 193 (Matías Amaya, matricula 5)
    // Periodo 1
    $grades = [
        ['mat_id' => 5, 'asig_id' => 12, 'periodo' => 1, 'nota' => 4.5, 'logro' => 'Aplica con precisión razones trigonométricas.'],
        ['mat_id' => 5, 'asig_id' => 6,  'periodo' => 1, 'nota' => 4.8, 'logro' => 'Excelente comprensión y análisis de textos líricos.'],
        ['mat_id' => 5, 'asig_id' => 1,  'periodo' => 1, 'nota' => 4.2, 'logro' => 'Identifica la estructura atómica y enlaces químicos.'],
        ['mat_id' => 5, 'asig_id' => 9,  'periodo' => 1, 'nota' => 4.7, 'logro' => 'Demuestra fluidez y dominio de vocabulario técnico.'],
        // Student 191 (Sharol Thomas, matricula 3)
        ['mat_id' => 3, 'asig_id' => 6,  'periodo' => 1, 'nota' => 4.9, 'logro' => 'Sobresaliente desempeño en producción textual y ortografía.'],
        ['mat_id' => 3, 'asig_id' => 12, 'periodo' => 1, 'nota' => 4.1, 'logro' => 'Resuelve problemas algebraicos con criterio lógico.']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO calificaciones_logros (matricula_id, asignatura_id, periodo, nota_cognitiva, nota_procedimental, nota_actitudinal, nota_final, logros_observaciones)
        VALUES (:mat_id, :asig_id, :periodo, :nota, :nota, :nota, :nota, :logro)
    ");

    foreach ($grades as $g) {
        $stmt->execute([
            ':mat_id' => $g['mat_id'],
            ':asig_id' => $g['asig_id'],
            ':periodo' => $g['periodo'],
            ':nota' => $g['nota'],
            ':logro' => $g['logro']
        ]);
    }
    echo "Inserted " . count($grades) . " real sample grades successfully!\n";
}
