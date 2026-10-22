<?php
$url = 'http://aulavirtualsalle.com/views/auth/inscripcion.php';
$postData = ['action' => 'consultar_estado', 'termino_busqueda' => '1084221199'];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);
$res = curl_exec($ch);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);
echo "Verbose log:\n" . $verboseLog . "\n";
