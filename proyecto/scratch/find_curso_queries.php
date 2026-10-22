<?php
$dir = __DIR__ . '/../views';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$results = [];

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    if ($file->getExtension() !== 'php') continue;
    $relPath = str_replace(__DIR__ . '/../', '', $file->getPathname());
    $content = file_get_contents($file->getPathname());
    
    // Check for queries to cursos
    preg_match_all('/SELECT.*?FROM\s+cursos[^\'";]*/i', $content, $mQueries);
    if (!empty($mQueries[0])) {
        foreach ($mQueries[0] as $q) {
            $results[] = "$relPath query: " . trim($q);
        }
    }
}

foreach ($results as $r) {
    echo $r . PHP_EOL;
}
