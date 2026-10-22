<?php
$lines = file(__DIR__ . '/../views/admin/index.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'cursosDB') !== false) {
        echo ($i+1) . ': ' . trim($line) . PHP_EOL;
    }
}
