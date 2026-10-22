<?php
$sqlFile = __DIR__ . '/../../aulavir7_Aulavirtualsalle.sql';
$content = file_get_contents($sqlFile);
preg_match('/INSERT INTO `?cursos`?[^;]+;/i', $content, $mCursos);
if (!empty($mCursos)) {
    echo $mCursos[0] . "\n";
}
