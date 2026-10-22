<?php
$xml = file_get_contents(__DIR__ . '/acta_doc.xml');

// Function to extract text between two markers
function getBetween($content, $start, $end) {
    $p1 = stripos($content, $start);
    if ($p1 === false) return '';
    $p1 += strlen($start);
    $p2 = stripos($content, $end, $p1);
    if ($p2 === false) return substr($content, $p1);
    return substr($content, $p1, $p2 - $p1);
}

// Let's extract items from each part
$parts = preg_split('/ACTA\s+DE\s+REPORTE/i', $xml);

$actasData = [];

for ($i = 1; $i <= 3; $i++) {
    $part = $parts[$i] ?? '';
    // Extract paragraphs
    $ps = explode('</w:p>', $part);
    $cleanLines = [];
    foreach ($ps as $p) {
        $t = trim(strip_tags($p));
        // Replace multiple spaces
        $t = preg_replace('/\s+/', ' ', $t);
        if (!empty($t) && strlen($t) > 3) {
            $cleanLines[] = $t;
        }
    }
    $actasData[$i] = $cleanLines;
}

// Let's filter out the actual fault lists
// In each part, faults start after "Seleccione la falta o faltas que ha cometido el alumno:"
// and end before "Descargos del estudiante" or "ESTA ACTA DEBE REMITIRSE"
$faltas = [1 => [], 2 => [], 3 => []];

foreach ($actasData as $tipo => $lines) {
    $recording = false;
    foreach ($lines as $line) {
        if (stripos($line, 'Seleccione la falta') !== false || stripos($line, 'FALTAS LEVES') !== false) {
            $recording = true;
            continue;
        }
        if (stripos($line, 'Descargos del estudiante') !== false || stripos($line, 'ESTA ACTA DEBE REMITIRSE') !== false) {
            $recording = false;
            break;
        }
        if ($recording) {
            // Exclude headers like INSTITUTO LA SALLE, DANE, etc.
            if (stripos($line, 'INSTITUTO LA SALLE') !== false ||
                stripos($line, 'Resolución No') !== false ||
                stripos($line, 'Preescolar, Básica') !== false ||
                stripos($line, 'DANE N°') !== false ||
                stripos($line, 'Cienaga') !== false ||
                stripos($line, 'Magdalena') !== false ||
                stripos($line, 'Nombre:') !== false ||
                stripos($line, 'Grado:') !== false ||
                stripos($line, 'FECHA:') !== false) {
                continue;
            }
            if (strlen($line) > 10) {
                $faltas[$tipo][] = $line;
            }
        }
    }
}

file_put_contents(__DIR__ . '/extracted_faltas.json', json_encode($faltas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Extracted:\n";
echo "Tipo I: " . count($faltas[1]) . " faltas\n";
echo "Tipo II: " . count($faltas[2]) . " faltas\n";
echo "Tipo III: " . count($faltas[3]) . " faltas\n";
