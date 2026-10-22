<?php
$files = [
    __DIR__ . '/../database/database.sql',
    __DIR__ . '/../../aulavir7_Aulavirtualsalle.sql'
];

$newCursos = "INSERT INTO `cursos` (`id`, `grado`, `grupo`, `ano_lectivo`) VALUES\n" .
"(1, '10°', 'A', 2026),\n" .
"(2, '10°', 'B', 2026),\n" .
"(3, '11°', 'A', 2026),\n" .
"(4, '11°', 'B', 2026),\n" .
"(5, 'Transición', 'A', 2026),\n" .
"(6, 'Transición', 'B', 2026),\n" .
"(7, '1°', 'A', 2026),\n" .
"(8, '1°', 'B', 2026),\n" .
"(9, '2°', 'A', 2026),\n" .
"(10, '2°', 'B', 2026),\n" .
"(11, '3°', 'A', 2026),\n" .
"(12, '3°', 'B', 2026),\n" .
"(13, '4°', 'A', 2026),\n" .
"(14, '4°', 'B', 2026),\n" .
"(15, '5°', 'A', 2026),\n" .
"(16, '5°', 'B', 2026),\n" .
"(17, '6°', 'A', 2026),\n" .
"(18, '6°', 'B', 2026),\n" .
"(19, '7°', 'A', 2026),\n" .
"(20, '7°', 'B', 2026),\n" .
"(21, '8°', 'A', 2026),\n" .
"(22, '8°', 'B', 2026),\n" .
"(23, '9°', 'A', 2026),\n" .
"(24, '9°', 'B', 2026);";

$oldMatriculasEnd = "(69, 212, 1, 2026, 'activa', 'EST-2026-00212', '2026-09-08 17:14:02');";
$newMatriculasEnd = "(69, 212, 3, 2026, 'activa', 'EST-2026-00212', '2026-09-08 17:14:02'),\n" .
"(72, 220, 3, 2026, 'activa', 'EST-2026-00220', '2026-09-08 18:00:00'),\n" .
"(73, 5, 3, 2026, 'activa', 'EST-2026-00005', '2026-09-08 18:00:00'),\n" .
"(74, 8, 3, 2026, 'activa', 'EST-2026-00008', '2026-09-08 18:00:00');";

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    $content = file_get_contents($file);

    // Replace cursos
    $patternCursos = '/INSERT INTO `cursos` \(`id`, `grado`, `grupo`, `ano_lectivo`\) VALUES[\s\S]*?\(4, \'11°\', \'B\', 2026\);/';
    if (preg_match($patternCursos, $content)) {
        $content = preg_replace($patternCursos, $newCursos, $content, 1);
        echo "Updated cursos in $file\n";
    } else {
        echo "Pattern for cursos not matched in $file\n";
    }

    // Replace matricula 69 (change curso 1 to 3 and add others)
    $patternMat = '/\(69,\s*212,\s*1,\s*2026,\s*\'activa\',\s*\'EST-2026-00212\',\s*\'2026-09-08 17:14:02\'\);/';
    if (preg_match($patternMat, $content)) {
        $content = preg_replace($patternMat, $newMatriculasEnd, $content, 1);
        echo "Updated matriculas in $file\n";
    } else {
        echo "Pattern for matriculas not matched in $file\n";
    }

    file_put_contents($file, $content);
}
echo "Done!\n";
