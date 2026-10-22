<?php
$lines = file(__DIR__ . '/../views/administrativo/index.php');
foreach ($lines as $i => $l) {
    if (stripos($l, 'modalReceipt') !== false || stripos($l, 'modalEgreso') !== false) {
        echo ($i + 1) . ": " . substr(trim($l), 0, 100) . "\n";
    }
}
