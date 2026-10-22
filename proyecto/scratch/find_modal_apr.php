<?php
$lines = explode("\n", file_get_contents(__DIR__ . '/../views/admin/index.php'));
foreach ($lines as $i => $l) {
    if (strpos($l, 'modalAprobarAdmision') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($l) . "\n";
    }
}
