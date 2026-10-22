<?php
$lines = file(__DIR__ . '/../views/admin/index.php');
foreach ($lines as $i => $line) {
    if (stripos($line, '<select') !== false) {
        // print next 5 lines
        echo "Line " . ($i+1) . ":\n";
        for ($j = $i; $j < min(count($lines), $i + 6); $j++) {
            echo "   " . trim($lines[$j]) . "\n";
        }
    }
}
