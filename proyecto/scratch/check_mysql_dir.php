<?php
$paths = [
    'C:/AppServ/MySQL/data',
    'C:/xampp/mysql/data',
    'C:/ProgramData/MySQL/MySQL Server 8.0/Data'
];
foreach ($paths as $p) {
    if (file_exists($p)) {
        echo "Found: $p\n";
        $dirs = scandir($p);
        print_r($dirs);
    }
}
