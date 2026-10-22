<?php
$content = file_get_contents(__DIR__ . '/../../aulavir7_Aulavirtualsalle.sql');
preg_match('/INSERT INTO `?curso_asignatura_docente`?[^;]+;/is', $content, $m);
if (!empty($m)) {
    echo $m[0] . "\n";
} else {
    echo "No curso_asignatura_docente inserts found\n";
}
