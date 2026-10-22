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

$cursosPorJornada = [
    'Mañana' => [],
    'Tarde'  => []
];
foreach ($cursosDB as $cItem) {
    $jor = (!empty($cItem['jornada']) && strtolower($cItem['jornada']) === 'tarde') ? 'Tarde' : 'Mañana';
    $cursosPorJornada[$jor][] = $cItem;
}

function testRender($cursosPorJornada) {
    $html = '';
    if (!empty($cursosPorJornada['Mañana'])) {
        $html .= '<optgroup label="☀️ JORNADA MAÑANA (' . count($cursosPorJornada['Mañana']) . ' Cursos)">' . PHP_EOL;
        foreach ($cursosPorJornada['Mañana'] as $c) {
            $html .= '  <option value="' . $c['id'] . '">☀️ Mañana — ' . $c['grado'] . ' ' . $c['grupo'] . ' (' . $c['ano_lectivo'] . ')</option>' . PHP_EOL;
        }
        $html .= '</optgroup>' . PHP_EOL;
    }
    if (!empty($cursosPorJornada['Tarde'])) {
        $html .= '<optgroup label="⛅ JORNADA TARDE (' . count($cursosPorJornada['Tarde']) . ' Cursos)">' . PHP_EOL;
        foreach ($cursosPorJornada['Tarde'] as $c) {
            $html .= '  <option value="' . $c['id'] . '">⛅ Tarde — ' . $c['grado'] . ' ' . $c['grupo'] . ' (' . $c['ano_lectivo'] . ')</option>' . PHP_EOL;
        }
        $html .= '</optgroup>' . PHP_EOL;
    }
    return $html;
}

$rendered = testRender($cursosPorJornada);
echo "Primeras 5 líneas de Mañana:\n";
$lines = explode(PHP_EOL, $rendered);
for ($i = 0; $i < 6; $i++) echo $lines[$i] . PHP_EOL;

echo "\nPrimeras 5 líneas de Tarde:\n";
$tardeStart = false;
$tCount = 0;
foreach ($lines as $l) {
    if (strpos($l, 'JORNADA TARDE') !== false) $tardeStart = true;
    if ($tardeStart && $tCount < 6) {
        echo $l . PHP_EOL;
        $tCount++;
    }
}
