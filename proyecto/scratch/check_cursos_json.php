<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

$cursosDB = $pdo->query("
    SELECT id, grado, grupo, jornada, sede, ano_lectivo 
    FROM cursos 
    ORDER BY FIELD(jornada, 'Mañana', 'Tarde', 'Única'), 
             FIELD(grado, 'Transición', '1°', '2°', '3°', '4°', '5°', '6°', '7°', '8°', '9°', '10°', '11°'), 
             grupo ASC
")->fetchAll(PDO::FETCH_ASSOC);

$json = json_encode($cursosDB);
$decoded = json_decode($json, true);

$manana = 0;
$tarde = 0;
$other = 0;
foreach ($decoded as $c) {
    if (strtolower($c['jornada'] ?? '') === 'tarde') $tarde++;
    elseif (strtolower($c['jornada'] ?? '') === 'mañana') $manana++;
    else {
        $other++;
        echo "Other: " . json_encode($c) . PHP_EOL;
    }
}
echo "Total: " . count($decoded) . " | Mañana: $manana | Tarde: $tarde | Other: $other" . PHP_EOL;
