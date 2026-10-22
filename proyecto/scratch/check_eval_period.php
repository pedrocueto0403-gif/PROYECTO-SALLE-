<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->query('SELECT * FROM evaluacion_docente_periodos');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
