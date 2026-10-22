<?php
$docxPath = 'c:/Users/SANTIAGO RODRIGUEZ/Desktop/Aula virtual/ACTA DE REPORTE DE SISTUACIONES.docx';
$zip = new ZipArchive();
if ($zip->open($docxPath) === true) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    
    // Save readable XML to scratch to view
    file_put_contents(__DIR__ . '/acta_doc.xml', $xml);
    echo "Saved acta_doc.xml. Size: " . strlen($xml) . " bytes\n";
}
