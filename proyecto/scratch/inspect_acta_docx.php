<?php
$docxPath = 'c:/Users/SANTIAGO RODRIGUEZ/Desktop/Aula virtual/ACTA DE REPORTE DE SISTUACIONES.docx';
$zip = new ZipArchive();
if ($zip->open($docxPath) === true) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    
    // Strip tags to get readable text
    $text = strip_tags(str_replace(['</w:p>', '</w:tr>'], ["\n", "\n"], $xml));
    echo "=== TEXT CONTENT OF DOCX ===\n";
    echo $text;
} else {
    echo "Could not open $docxPath\n";
}
