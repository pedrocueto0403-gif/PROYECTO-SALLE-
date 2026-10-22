<?php
$lines = explode("\n", file_get_contents(__DIR__ . '/../views/administrativo/index.php'));
foreach ($lines as $i => $l) {
    if (strpos($l, 'aspirantesAprobadosParaCaja') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($l) . "\n";
    }
}
