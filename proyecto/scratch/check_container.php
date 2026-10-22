<?php
$url = 'http://aulavirtualsalle.com/views/auth/inscripcion.php';
$postData = ['action' => 'consultar_estado', 'termino_busqueda' => '1084221199'];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);

$pos = strpos($res, '<div class="container my-5">');
echo substr($res, $pos, 1000);
