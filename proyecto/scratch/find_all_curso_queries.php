<?php
$dir = __DIR__ . '/..';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$results = [];

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    if ($file->getExtension() !== 'php') continue;
    if (strpos($file->getPathname(), 'scratch') !== false) continue;
    if (strpos($file->getPathname(), 'vendor') !== false) continue;
    
    $relPath = str_replace(__DIR__ . '/../', '', $file->getPathname());
    $content = file_get_contents($file->getPathname());
    
    // Check for queries to cursos
    preg_match_all('/SELECT.*?FROM\s+cursos[^\'";]*/i', $content, $mQueries);
    if (!empty($mQueries[0])) {
        foreach ($mQueries[0] as $q) {
            $results[] = "$relPath: " . trim($q);
        }
    }
}

foreach ($results as $r) {
    echo $r . PHP_EOL;
}
