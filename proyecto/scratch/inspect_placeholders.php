<?php
$templates = [
    'CERTIFICADO ESTUDIO 2025.docx' => ['BRITO AVILA ALEJANDRA', '1.121.971.552', 'QUINTO (5°)'],
    'CONSTANCIA DE ESTUDIOS 2025.docx' => ['DANIEL JULIÁN FAJARDO HERNÁNDEZ', 'FAJARDO POLO ISABELLA', '1.080.439.936'],
    'CERTIFICADO DE CONDUCTAS 2023.docx' => ['NIÑO CAMACHO ISABELLA', '1.176.969.545', 'TERCERO (3°)'],
    'CERTIFICADO COSTO EDUCATIVO.docx' => ['HERNANDEZ CAÑAR NOHELIA CAMILA', '1.080.432.978', 'DECIMO (10°)'],
    'CERTIFICADO PAZ Y SALVO.docx' => ['GRANADOS RAMIREZ MARIANA SOFIA', '1.221.986.725', 'JARDIN'],
    'CERTIFICADO LABORAL DOCENTE 2024.docx' => ['LOZANO BOLAÑO LILIANA PATRICIA', '2.6719.710', 'LICENCIADA EN LENGUA CASTELLANA'],
    'Observador estudiante rectoria.docx' => ['NIÑO CAMACHO ISABELLA', '3°']
];

foreach ($templates as $file => $strings) {
    $zip = new ZipArchive();
    $path = __DIR__ . '/../certificados/' . $file;
    if ($zip->open($path) === TRUE) {
        $xml = $zip->getFromName('word/document.xml');
        echo "FILE: $file\n";
        foreach ($strings as $s) {
            $pos = mb_strpos($xml, $s);
            echo "  Looking for '$s': " . ($pos !== false ? "FOUND at $pos" : "NOT FOUND (might be split across tags)") . "\n";
        }
        $zip->close();
    }
}
