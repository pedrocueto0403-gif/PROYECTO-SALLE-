<?php
$lines = file(__DIR__ . '/../views/admin/index.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'jornada') !== false || stripos($line, 'renderCursosOptions') !== false || stripos($line, 'cursosList') !== false || stripos($line, 'cursosPorJornada') !== false) {
        echo ($i+1) . ': ' . trim(substr($line, 0, 110)) . PHP_EOL;
    }
}
