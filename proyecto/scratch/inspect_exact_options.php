<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'administrador';
$_SESSION['rol_id'] = 1;

ob_start();
include __DIR__ . '/../views/admin/index.php';
$html = ob_get_clean();

$pos = strpos($html, 'id="regCursoSelect"');
$endPos = strpos($html, '</select>', $pos);
$selectHtml = substr($html, $pos, $endPos - $pos + 9);

echo "Length of select HTML: " . strlen($selectHtml) . "\n";
echo "Contains JORNADA MAÑANA: " . (strpos($selectHtml, 'JORNADA MAÑANA') !== false ? 'YES' : 'NO') . "\n";
echo "Contains JORNADA TARDE: " . (strpos($selectHtml, 'JORNADA TARDE') !== false ? 'YES' : 'NO') . "\n";
preg_match_all('/<option\s+value="([^"]+)"\s+data-jornada="([^"]+)"/', $selectHtml, $matches);
echo "Options with data-jornada count: " . count($matches[0]) . "\n";
$mCount = 0; $tCount = 0;
foreach ($matches[2] as $j) {
    if ($j === 'Mañana') $mCount++;
    if ($j === 'Tarde') $tCount++;
}
echo "Options Mañana: $mCount | Options Tarde: $tCount\n";
