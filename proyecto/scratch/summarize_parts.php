<?php
$xml = file_get_contents(__DIR__ . '/acta_doc.xml');
$parts = preg_split('/ACTA\s+DE\s+REPORTE/i', $xml);

for ($i = 1; $i < count($parts); $i++) {
    echo "=========================================\n";
    echo "SUMMARY FOR ACTA PART $i\n";
    echo "=========================================\n";
    $p = $parts[$i];
    
    // First 40 lines
    $lines = array_filter(array_map('trim', explode("\n", strip_tags(str_replace('</w:p>', "\n", $p)))));
    $firstLines = array_slice($lines, 0, 30);
    echo implode("\n", $firstLines) . "\n";
    echo "--- TOTAL LINES: " . count($lines) . " ---\n\n";
}
