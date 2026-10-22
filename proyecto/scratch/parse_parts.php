<?php
$xml = file_get_contents(__DIR__ . '/acta_doc.xml');

// Split by "ACTA DE REPORTE"
$parts = preg_split('/ACTA\s+DE\s+REPORTE/i', $xml);
echo "Total Parts: " . count($parts) . "\n";

foreach ($parts as $idx => $part) {
    if ($idx === 0) continue;
    echo "\n======================================================\n";
    echo "=== PART $idx ===\n";
    echo "======================================================\n";
    
    // Extract readable paragraphs
    $ps = explode('</w:p>', $part);
    foreach ($ps as $p) {
        $text = trim(strip_tags($p));
        if (!empty($text)) {
            echo $text . "\n";
        }
    }
}
