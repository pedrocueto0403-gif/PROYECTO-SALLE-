<?php
$zip = new ZipArchive();
if ($zip->open('practicas antigrabiti/ACTA DE REPORTE DE SISTUACIONES.docx') === true) {
    echo "=== header1.xml ===\n";
    $hdr = $zip->getFromName('word/header1.xml');
    echo substr($hdr, 0, 1000) . "\n\n";

    echo "=== header1.xml.rels ===\n";
    $rels = $zip->getFromName('word/_rels/header1.xml.rels');
    echo $rels . "\n";
    $zip->close();
}
