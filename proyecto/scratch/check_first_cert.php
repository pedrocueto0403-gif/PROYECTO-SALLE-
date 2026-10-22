<?php
ini_set('memory_limit', '512M');
$file = __DIR__ . '/../certificados/CERTIFICADO ESTUDIO 2025.docx';
$zip = new ZipArchive();
if ($zip->open($file) === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    echo "Length of document.xml: " . strlen($xml) . " bytes\n";
    // Let's find the first page break or section break
    $posBreak = strpos($xml, '<w:br w:type="page"/>');
    if ($posBreak === false) {
        $posBreak = strpos($xml, '<w:sectPr');
    }
    echo "First break at: $posBreak\n";
    $zip->close();
}
