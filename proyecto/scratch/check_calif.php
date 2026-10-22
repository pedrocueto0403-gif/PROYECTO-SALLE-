<?php
$content = file_get_contents(__DIR__ . '/../../aulavir7_Aulavirtualsalle.sql');
preg_match('/INSERT INTO `?calificaciones_logros`?[^;]+;/is', $content, $m);
if (!empty($m)) {
    // Show sample lines
    $lines = explode("\n", $m[0]);
    echo "Total lines in calificaciones_logros: " . count($lines) . "\n";
    echo implode("\n", array_slice($lines, 0, 5)) . "\n";
}
