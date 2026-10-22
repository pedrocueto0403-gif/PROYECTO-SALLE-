<?php
$lines = explode("\n", file_get_contents(__DIR__ . '/../views/administrativo/index.php'));
foreach ($lines as $i => $l) {
    if (stripos($l, 'segunda') !== false) {
        echo "Line " . ($i + 1) . ": " . trim(substr($l, 0, 120)) . "\n";
    }
}
