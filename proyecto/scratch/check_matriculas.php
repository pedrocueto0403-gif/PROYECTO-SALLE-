<?php
$content = file_get_contents(__DIR__ . '/../../aulavir7_Aulavirtualsalle.sql');
preg_match('/INSERT INTO `?matriculas`?[^;]+;/is', $content, $m);
if (!empty($m)) {
    echo $m[0] . "\n";
} else {
    echo "No matriculas inserts found\n";
}
