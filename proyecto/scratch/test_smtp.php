<?php
require_once __DIR__ . '/../app/Services/EmailGateway.php';

$res = EmailGateway::enviarPorSMTP(
    'carlosptz14@gmail.com',
    'Carlos Acudiente',
    'Test Local SMTP Salle',
    '<h3>Prueba SMTP Local</h3>',
    'aulavirtualsalle@gmail.com',
    'Instituto La Salle'
);
echo json_encode($res, JSON_PRETTY_PRINT);
