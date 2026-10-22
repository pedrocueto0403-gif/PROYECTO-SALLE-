<?php
$url = 'http://aulavirtualsalle.com/views/auth/inscripcion.php';
$postData = [
    'action' => 'consultar_estado',
    'termino_busqueda' => '1084221199'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

echo "Response length: " . strlen($response) . "\n";
if (strpos($response, 'alert-danger') !== false) {
    echo "Found alert-danger:\n";
    preg_match('/<div class="alert alert-danger[^>]*>(.*?)<\/div>/s', $response, $matches);
    echo strip_tags($matches[0] ?? 'No details') . "\n";
} elseif (strpos($response, 'Radicado Oficial') !== false) {
    echo "Found Radicado Oficial!\n";
} else {
    echo "No alert and no radicado found.\n";
    // Check if any error is printed
    if (strpos($response, 'SQLSTATE') !== false) {
        echo "Found SQLSTATE in response!\n";
        preg_match('/SQLSTATE[^<]*/', $response, $m);
        echo $m[0] . "\n";
    }
}
