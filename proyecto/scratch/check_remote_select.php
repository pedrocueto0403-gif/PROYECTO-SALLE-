<?php
// Check if the remote server has Database.php with auto-table creation or what error it gives
$url = 'http://aulavirtualsalle.com/views/auth/inscripcion.php';
$html = file_get_contents($url);

// Check if any indicator of our changes exists
echo "Checking remote HTML for recent updates...\n";
if (strpos($html, 'modalAprobarAdmision') !== false) {
    echo "Found modalAprobarAdmision\n";
}
// Check courses dropdown options in remote HTML
preg_match_all('/<option value="([^"]+)">/i', $html, $opts);
echo "Grades count in select: " . count($opts[1]) . "\n";
print_r(array_slice($opts[1], 0, 5));
