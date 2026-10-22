<?php
$zip = new ZipArchive();
if ($zip->open('practicas antigrabiti/ACTA DE REPORTE DE SISTUACIONES.docx') === true) {
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        $content = $zip->getFromIndex($i);
        if (strpos($content, 'image1') !== false) {
            echo "image1 is in $name\n";
        }
        if (strpos($content, 'image2') !== false) {
            echo "image2 is in $name\n";
        }
    }
    $zip->close();
}
