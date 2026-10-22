<?php
$folder = "C:/AppServ/www/certificados";
$files = glob("$folder/*.docx");
$files = array_merge($files, glob("$folder/templates/*.docx"));

foreach ($files as $file) {
    echo "========================================\n";
    echo "FILE: " . basename($file) . " (Path: $file)\n";
    echo "========================================\n";
    
    $zip = new ZipArchive();
    if ($zip->open($file) === TRUE) {
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        
        // Strip XML tags and show text
        $text = strip_tags($xml);
        // Clean multiple spaces/newlines
        $text = preg_replace('/\s+/', ' ', $text);
        echo substr($text, 0, 800) . "...\n\n";
        
        // Find any variables like ${var}, {{var}}, [var], etc.
        preg_match_all('/(\$\{.*?\})|(\{\{.*?\}\})|(\[.*?\])/', $text, $matches);
        if (!empty($matches[0])) {
            echo "PLACEHOLDERS FOUND: " . implode(', ', array_unique($matches[0])) . "\n";
        }
    } else {
        echo "Could not open as zip.\n";
    }
}
