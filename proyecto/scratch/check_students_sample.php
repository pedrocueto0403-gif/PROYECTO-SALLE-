<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();
$totalMatr = $pdo->query("SELECT COUNT(*) FROM matriculas WHERE ano_lectivo = 2026")->fetchColumn();
echo "TOTAL MATRICULADOS 2026: $totalMatr\n";

$sample = $pdo->query("
    SELECT m.id AS matricula_id, m.codigo_matricula, c.grado, c.grupo,
           p.primer_nombre, p.primer_apellido, p.documento_identidad, p.tipo_documento,
           p.acudiente_nombre, p.acudiente_documento, p.acudiente_telefono
    FROM matriculas m
    INNER JOIN usuarios u ON m.estudiante_id = u.id
    INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
    INNER JOIN cursos c ON m.curso_id = c.id
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
print_r($sample);
