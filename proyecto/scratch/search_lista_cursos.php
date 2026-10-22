<?php
$lines = file(__DIR__ . '/../views/admin/index.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'listaCursosDisponibles') !== false) {
        echo ($i+1) . ': ' . trim(substr($line, 0, 110)) . PHP_EOL;
    }
}
