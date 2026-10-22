<?php
$content = file_get_contents(__DIR__ . '/../views/admin/index.php');
$lines = explode("\n", $content);
foreach ($lines as $num => $line) {
    if (stripos($line, 'cursos') !== false || stripos($line, 'listaCursosDisponibles') !== false || stripos($line, 'cursosDB') !== false) {
        echo ($num + 1) . ": " . trim($line) . "\n";
    }
}
