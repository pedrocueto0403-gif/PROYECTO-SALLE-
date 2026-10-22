<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '123456789');
echo "DATABASES:\n";
print_r($pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN));

$dbs = ['aulavirtual_salle', 'aulavir7_aulavirtualsalle'];
foreach ($dbs as $db) {
    try {
        $p = new PDO("mysql:host=127.0.0.1;port=3306;dbname=$db", 'root', '123456789');
        echo "\n=== IN DATABASE: $db ===\n";
        $cursos = $p->query("SELECT * FROM cursos")->fetchAll(PDO::FETCH_ASSOC);
        echo "CURSOS:\n";
        print_r($cursos);
        $mats = $p->query("SELECT m.id, m.estudiante_id, m.curso_id, c.grado, c.grupo, p.primer_nombre, p.primer_apellido 
                           FROM matriculas m 
                           LEFT JOIN cursos c ON m.curso_id = c.id
                           LEFT JOIN perfiles_detalles p ON m.estudiante_id = p.usuario_id")->fetchAll(PDO::FETCH_ASSOC);
        echo "MATRICULAS:\n";
        print_r($mats);
    } catch (Exception $e) {
        echo "Error for $db: " . $e->getMessage() . "\n";
    }
}
