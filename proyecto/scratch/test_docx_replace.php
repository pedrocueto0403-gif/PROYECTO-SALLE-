<?php
$sourceDocx = "c:/Users/SANTIAGO RODRIGUEZ/Desktop/Aula virtual/practicas antigrabiti/certificados/CERTIFICADO LABORAL DOCENTE 2024.docx";
$tempOutput = __DIR__ . '/test_output.docx';

copy($sourceDocx, $tempOutput);

$zip = new ZipArchive();
if ($zip->open($tempOutput) === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    
    // Replace text inside XML
    // Notice in original: "LOZANO BOLAÑO LILIANA PATRICIA"
    $replacedXml = str_replace('LOZANO BOLAÑO LILIANA PATRICIA', 'CARLOS ALBERTO GARCIA LOPEZ', $xml);
    $replacedXml = str_replace('2.6719.710', '1.082.987.003', $replacedXml);
    $replacedXml = str_replace('LICENCIADA EN LENGUA CASTELLANA', 'LICENCIADO EN MATEMATICAS Y FISICA', $replacedXml);
    
    $zip->deleteName('word/document.xml');
    $zip->addFromString('word/document.xml', $replacedXml);
    $zip->close();
    
    echo "SUCCESS: Created $tempOutput with replaced data!\n";
    echo "Filesize: " . filesize($tempOutput) . " bytes\n";
    
    // Verify reading it back
    $zip2 = new ZipArchive();
    $zip2->open($tempOutput);
    $xml2 = $zip2->getFromName('word/document.xml');
    $zip2->close();
    if (strpos($xml2, 'CARLOS ALBERTO GARCIA LOPEZ') !== false) {
        echo "VERIFIED: 'CARLOS ALBERTO GARCIA LOPEZ' is present in the generated docx!\n";
    }
} else {
    echo "Error opening zip.\n";
}
