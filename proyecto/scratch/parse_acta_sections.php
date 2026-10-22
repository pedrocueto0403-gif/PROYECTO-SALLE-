<?php
$docxPath = 'c:/Users/SANTIAGO RODRIGUEZ/Desktop/Aula virtual/ACTA DE REPORTE DE SISTUACIONES.docx';
$zip = new ZipArchive();
if ($zip->open($docxPath) === true) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    
    // Find all headings, tables, or sections
    preg_match_all('/ACTA\s+(?:N°|NO\.?|NUMERO)?\s*[0-9]+/i', $xml, $matches);
    echo "Found Actas:\n";
    print_r(array_unique($matches[0]));

    // Let's print paragraphs with "ACTA" or titles
    $pXml = explode('</w:p>', $xml);
    foreach ($pXml as $p) {
        $clean = trim(strip_tags($p));
        if (stripos($clean, 'ACTA') !== false || stripos($clean, 'SITUACIÓN') !== false || stripos($clean, 'SITUACION') !== false || stripos($clean, 'TIPO') !== false) {
            echo "TITLE LINE: " . $clean . "\n";
        }
    }
}
