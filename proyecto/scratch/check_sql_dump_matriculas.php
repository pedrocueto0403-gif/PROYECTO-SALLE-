<?php
$lines = file('database/database.sql');
foreach ($lines as $i => $line) {
    if (strpos($line, "INSERT INTO `matriculas`") !== false) {
        for ($j = $i; $j < $i + 30; $j++) {
            if (isset($lines[$j])) echo $lines[$j];
        }
        break;
    }
}
