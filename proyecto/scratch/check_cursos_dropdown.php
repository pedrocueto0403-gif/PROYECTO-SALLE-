<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

$cursosDB = $pdo->query("SELECT id, grado, grupo, jornada, sede, ano_lectivo FROM cursos ORDER BY FIELD(jornada, 'Mañana', 'Tarde', 'Única'), grado ASC, grupo ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "Total cursos in DB: " . count($cursosDB) . PHP_EOL;

$cursosPorJornada = [
    'Mañana' => [],
    'Tarde'  => []
];
foreach ($cursosDB as $cItem) {
    $jor = (!empty($cItem['jornada']) && strtolower($cItem['jornada']) === 'tarde') ? 'Tarde' : 'Mañana';
    $cursosPorJornada[$jor][] = $cItem;
}

echo "Cursos Mañana count: " . count($cursosPorJornada['Mañana']) . PHP_EOL;
echo "Cursos Tarde count: " . count($cursosPorJornada['Tarde']) . PHP_EOL;

foreach ($cursosPorJornada['Tarde'] as $ct) {
    echo "  Tarde: {$ct['id']} - {$ct['grado']} {$ct['grupo']} ({$ct['jornada']})\n";
}
