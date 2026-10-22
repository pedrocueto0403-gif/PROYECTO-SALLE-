<?php
$lines = file(__DIR__ . '/../views/admin/index.php');
foreach ($lines as $i => $l) {
    if (strpos($l, '$_SERVER[\'REQUEST_METHOD\'] === \'POST\'') !== false || ($i > 200 && $i < 650 && strpos($l, 'elseif ($action ===') !== false)) {
        echo ($i + 1) . ": " . trim($l) . "\n";
    }
}
