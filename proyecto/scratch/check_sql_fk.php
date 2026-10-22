<?php
$content = file_get_contents('aulavir7_Aulavirtualsalle.sql');

$usuariosIds = [];
if (preg_match("/INSERT INTO `usuarios`[^;]+;/s", $content, $uBlock)) {
    if (preg_match_all("/\((\d+),/m", $uBlock[0], $idMatches)) {
        foreach ($idMatches[1] as $uid) {
            $usuariosIds[intval($uid)] = true;
        }
    }
}

$missingStudents = [];
if (preg_match("/INSERT INTO `matriculas`[^;]+;/s", $content, $mBlock)) {
    // Structure: (id, estudiante_id, curso_id, ...)
    if (preg_match_all("/\(\d+,\s*(\d+),\s*(\d+),/m", $mBlock[0], $mRows)) {
        foreach ($mRows[1] as $idx => $estId) {
            $estId = intval($estId);
            if (!isset($usuariosIds[$estId])) {
                $missingStudents[$estId] = true;
            }
        }
    }
}

echo "Missing estudiante_ids in usuarios: " . count($missingStudents) . "\n";
echo "Missing IDs: " . implode(', ', array_keys($missingStudents)) . "\n";

// Also check database/database.sql
$content2 = file_get_contents('practicas antigrabiti/database/database.sql');
$usuariosIds2 = [];
if (preg_match("/INSERT INTO `usuarios`[^;]+;/s", $content2, $uBlock2)) {
    if (preg_match_all("/\((\d+),/m", $uBlock2[0], $idMatches2)) {
        foreach ($idMatches2[1] as $uid) {
            $usuariosIds2[intval($uid)] = true;
        }
    }
}

$missingStudents2 = [];
if (preg_match("/INSERT INTO `matriculas`[^;]+;/s", $content2, $mBlock2)) {
    if (preg_match_all("/\(\d+,\s*(\d+),\s*(\d+),/m", $mBlock2[0], $mRows2)) {
        foreach ($mRows2[1] as $idx => $estId) {
            $estId = intval($estId);
            if (!isset($usuariosIds2[$estId])) {
                $missingStudents2[$estId] = true;
            }
        }
    }
}
echo "\nIn database/database.sql:\n";
echo "Missing estudiante_ids: " . count($missingStudents2) . "\n";
echo "Missing IDs: " . implode(', ', array_keys($missingStudents2)) . "\n";
