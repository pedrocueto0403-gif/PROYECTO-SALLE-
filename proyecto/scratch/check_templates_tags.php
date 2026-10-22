<?php
$files = glob(__DIR__ . '/../certificados/templates/*.docx');
foreach ($files as $file) {
    echo "========================================\n";
    echo "TEMPLATE: " . basename($file) . "\n";
    echo "========================================\n";
    $zip = new ZipArchive();
    if ($zip->open($file) === TRUE) {
        $xml = $zip->getFromName('word/document.xml');
        preg_match_all('/\{\{[A-Z0-9_]+\}\}/', $xml, $matches);
        echo "Placeholders found: " . implode(', ', array_unique($matches[0])) . "\n";
        $text = strip_tags(preg_replace('/<\/w:p>/', "\n", $xml));
        echo substr(trim($text), 0, 500) . "\n\n";
        $zip->close();
    }
}
