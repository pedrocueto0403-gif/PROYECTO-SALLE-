<?php
foreach (['aulavirtual_salle', 'aulavir7_aulavirtualsalle', 'aulav'] as $f) {
    $dir = "C:/AppServ/MySQL/data/$f";
    if (file_exists($dir)) {
        echo "$f files: " . count(scandir($dir)) . "\n";
        print_r(array_slice(scandir($dir), 0, 10));
    }
}
