<?php
$zip = new ZipArchive();
if ($zip->open('practicas antigrabiti/ACTA DE REPORTE DE SISTUACIONES.docx') === true) {
    echo "=== document.xml.rels ===\n";
    echo $zip->getFromName('word/_rels/document.xml.rels') . "\n";
    
    // Find where image1's rId is used in document.xml
    $doc = $zip->getFromName('word/document.xml');
    preg_match_all('/<w:drawing>.*?<\/w:drawing>/s', $doc, $matches);
    echo "Found " . count($matches[0]) . " drawings in document.xml\n";
    if (!empty($matches[0])) {
        echo "Drawing 1:\n" . substr($matches[0][0], 0, 1500) . "\n";
    }
    $zip->close();
}
