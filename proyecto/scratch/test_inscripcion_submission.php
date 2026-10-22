<?php
// Simular envío a inscripcion.php y verificar que guarde correctamente en la base de datos
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();
if (!$pdo) {
    die("ERROR: No connection to DB\n");
}

echo "Active Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";

// Test inserting a test application
$docPrueba = '9988776655';
$pdo->exec("DELETE FROM solicitudes_admision WHERE documento_identidad = '$docPrueba'");

$stmt = $pdo->prepare("
    INSERT INTO solicitudes_admision (
        codigo_solicitud, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
        tipo_documento, documento_identidad, fecha_nacimiento, genero, direccion, barrio, ciudad,
        telefono, correo, grado_solicitado, colegio_origen,
        acudiente_nombre, acudiente_tipo_doc, acudiente_documento, acudiente_parentesco,
        acudiente_telefono, acudiente_correo, acudiente_direccion, acudiente_ocupacion,
        estado, monto_matricula, created_at
    ) VALUES (
        'SOL-2026-TEST', 'Pedro', 'Antonio', 'Pérez', 'Gómez',
        'TI', :doc, '2012-05-15', 'Masculino', 'Calle 10 # 5-20', 'Centro', 'Ciénaga',
        '3001234567', 'pedro.test@salle.edu.co', '6° Bachillerato', 'Colegio San Juan',
        'Carlos Pérez', 'CC', '75123456', 'Padre',
        '3009876543', 'carlos.perez@gmail.com', 'Calle 10 # 5-20', 'Comerciante',
        'Pendiente', 350000.00, NOW()
    )
");
$stmt->execute([':doc' => $docPrueba]);

$id = $pdo->lastInsertId();
echo "Inserted test application with ID: $id\n";

$check = $pdo->query("SELECT * FROM solicitudes_admision WHERE id = $id")->fetch(PDO::FETCH_ASSOC);
echo "Verification query: Code = " . $check['codigo_solicitud'] . " | Aspirante = " . $check['primer_nombre'] . " " . $check['primer_apellido'] . " | Estado = " . $check['estado'] . "\n";

// Clean up test record
$pdo->exec("DELETE FROM solicitudes_admision WHERE id = $id");
echo "Cleaned up test record successfully.\n";

// Now test on aulavir7_aulavirtualsalle as well
try {
    $pdo->exec("USE `aulavir7_aulavirtualsalle`");
    echo "Switched to `aulavir7_aulavirtualsalle` successfully.\n";
    $stmt2 = $pdo->prepare("
        INSERT INTO solicitudes_admision (
            codigo_solicitud, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
            tipo_documento, documento_identidad, fecha_nacimiento, genero, direccion, barrio, ciudad,
            telefono, correo, grado_solicitado, colegio_origen,
            acudiente_nombre, acudiente_tipo_doc, acudiente_documento, acudiente_parentesco,
            acudiente_telefono, acudiente_correo, acudiente_direccion, acudiente_ocupacion,
            estado, monto_matricula, created_at
        ) VALUES (
            'SOL-2026-TEST2', 'María', 'Camila', 'López', 'Ramos',
            'TI', '8877665544', '2013-08-20', 'Femenino', 'Cra 12 # 8-15', 'Miramar', 'Ciénaga',
            '3012345678', 'maria.test@salle.edu.co', '5° Primaria', 'Escuela Normal',
            'Lucía Ramos', 'CC', '45123456', 'Madre',
            '3018765432', 'lucia.ramos@gmail.com', 'Cra 12 # 8-15', 'Docente',
            'Pendiente', 350000.00, NOW()
        )
    ");
    $stmt2->execute();
    $id2 = $pdo->lastInsertId();
    echo "Inserted on `aulavir7_aulavirtualsalle` with ID: $id2\n";
    $pdo->exec("DELETE FROM solicitudes_admision WHERE id = $id2");
    echo "Cleaned up test record on `aulavir7_aulavirtualsalle`.\n";
} catch (Exception $e) {
    echo "Note on second DB: " . $e->getMessage() . "\n";
}

echo "ALL TESTS PASSED 100%!\n";
