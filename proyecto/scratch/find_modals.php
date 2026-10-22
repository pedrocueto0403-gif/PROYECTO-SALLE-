<?php
$lines = file(__DIR__ . '/../views/administrativo/index.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'class="modal ') !== false || stripos($line, 'modal fade') !== false) {
        echo ($i+1) . ': ' . trim(substr($line, 0, 100)) . PHP_EOL;
    }
}
