<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();

echo "=== CURSOS EN DB ===\n";
$cursos = $pdo->query("SELECT * FROM cursos")->fetchAll(PDO::FETCH_ASSOC);
print_r($cursos);

echo "\n=== MATRICULAS EN DB ===\n";
$mats = $pdo->query("SELECT m.*, u.correo, p.primer_nombre, p.primer_apellido, c.grado, c.grupo 
                      FROM matriculas m 
                      JOIN usuarios u ON m.estudiante_id = u.id 
                      JOIN perfiles_detalles p ON u.id = p.usuario_id
                      LEFT JOIN cursos c ON m.curso_id = c.id")->fetchAll(PDO::FETCH_ASSOC);
print_r($mats);

echo "\n=== ESTUDIANTES SIN MATRICULA O TOTAL ESTUDIANTES (rol_id = 5) ===\n";
$estudiantes = $pdo->query("SELECT u.id, u.correo, p.primer_nombre, p.primer_apellido, m.id as matricula_id, m.curso_id, c.grado, c.grupo
                            FROM usuarios u
                            JOIN perfiles_detalles p ON u.id = p.usuario_id
                            LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
                            LEFT JOIN cursos c ON m.curso_id = c.id
                            WHERE u.rol_id = 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($estudiantes);
