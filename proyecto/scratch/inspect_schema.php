<?php
$sql = file_get_contents(__DIR__ . '/../../aulavir7_Aulavirtualsalle.sql');
preg_match_all('/CREATE TABLE `([^`]+)` \(([\s\S]+?)\) ENGINE/i', $sql, $matches, PREG_SET_ORDER);
foreach ($matches as $m) {
    echo "TABLE: " . $m[1] . "\n";
    $lines = explode("\n", trim($m[2]));
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^`([^`]+)`\s+([a-zA-Z0-9()]+)/', $line, $col)) {
            echo "   - {$col[1]} ({$col[2]})\n";
        }
    }
}
