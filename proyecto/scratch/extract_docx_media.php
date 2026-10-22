<?php
$zip = new ZipArchive();
$docxPath = 'practicas antigrabiti/ACTA DE REPORTE DE SISTUACIONES.docx';
if ($zip->open($docxPath) === true) {
    echo "Files in docx:\n";
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, 'media') !== false || strpos($name, 'image') !== false) {
            echo "Media file: $name\n";
            $imgData = $zip->getFromIndex($i);
            $target = 'practicas antigrabiti/public/assets/' . basename($name);
            file_put_contents($target, $imgData);
            echo "Extracted: $target (" . strlen($imgData) . " bytes)\n";
        }
    }
    $zip->close();
} else {
    echo "Could not open $docxPath\n";
}
