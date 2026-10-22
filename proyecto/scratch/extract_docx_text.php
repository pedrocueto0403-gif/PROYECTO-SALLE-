<?php
$dir = __DIR__ . '/../certificados';
$files = glob($dir . '/*.docx');

foreach ($files as $file) {
    echo "========================================\n";
    echo "FILE: " . basename($file) . "\n";
    echo "========================================\n";
    $zip = new ZipArchive();
    if ($zip->open($file) === TRUE) {
        $xml = $zip->getFromName('word/document.xml');
        // strip tags but preserve paragraphs
        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
        $text = strip_tags($xml);
        $text = preg_replace("/\n\s*\n+/", "\n", $text);
        echo substr(trim($text), 0, 1500) . "\n\n";
        $zip->close();
    } else {
        echo "Failed to open zip\n";
    }
}
