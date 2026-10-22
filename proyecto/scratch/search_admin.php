<?php
$lines = file(__DIR__ . '/../views/admin/index.php');
foreach ($lines as $i => $l) {
    if (stripos($l, 'admisiones') !== false || stripos($l, 'solicitudes_admision') !== false) {
        echo ($i + 1) . ': ' . trim($l) . "\n";
    }
}
