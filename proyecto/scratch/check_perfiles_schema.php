<?php
$content = file_get_contents(__DIR__ . '/../../aulavir7_Aulavirtualsalle.sql');
preg_match('/CREATE TABLE `?perfiles_detalles`?[^;]+;/is', $content, $m);
if (!empty($m)) {
    echo $m[0] . "\n";
}
