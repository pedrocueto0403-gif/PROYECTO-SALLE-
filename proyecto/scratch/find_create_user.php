<?php
$lines = explode("\n", file_get_contents(__DIR__ . '/../views/admin/index.php'));
foreach ($lines as $idx => $line) {
    if (strpos($line, "create_user") !== false) {
        echo "Line " . ($idx + 1) . ": " . trim($line) . "\n";
    }
}
