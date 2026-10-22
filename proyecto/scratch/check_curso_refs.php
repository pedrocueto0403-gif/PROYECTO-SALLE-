<?php
$content = file_get_contents(__DIR__ . '/../../aulavir7_Aulavirtualsalle.sql');
$tables = ['matriculas', 'curso_asignatura_docente', 'asistencias', 'calificaciones_logros', 'solicitudes_admision', 'planes_clase'];

foreach ($tables as $t) {
    if (preg_match('/INSERT INTO `?' . $t . '`?[^;]+;/is', $content, $m)) {
        preg_match_all('/curso_id|curso_asignado_id/i', $content, $mc);
        echo "Table $t has records.\n";
    }
}
