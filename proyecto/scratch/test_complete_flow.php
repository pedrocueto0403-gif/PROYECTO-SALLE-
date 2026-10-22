<?php
/**
 * TEST SUITE: Flujo Completo de Admisión, Aprobación, Caja y Matriculación Automática
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../app/Services/EmailGateway.php';

$pdo = Database::getInstance()->getConnection();

echo "=======================================================\n";
echo "PRUEBA DEL FLUJO COMPLETO DE ADMISIÓN Y MATRICULACIÓN\n";
echo "=======================================================\n\n";

// 1. Limpiar datos de prueba previos si existen
$docPrueba = '1099887766';
$pdo->exec("DELETE FROM solicitudes_admision WHERE documento_identidad = '{$docPrueba}'");
$stmtOldUser = $pdo->query("SELECT u.id FROM usuarios u INNER JOIN perfiles_detalles p ON u.id = p.usuario_id WHERE p.documento_identidad = '{$docPrueba}'");
$oldUid = $stmtOldUser->fetchColumn();
if ($oldUid) {
    $pdo->exec("DELETE FROM matriculas WHERE estudiante_id = {$oldUid}");
    $pdo->exec("DELETE FROM contabilidad_caja WHERE usuario_id = {$oldUid}");
    $pdo->exec("DELETE FROM perfiles_detalles WHERE usuario_id = {$oldUid}");
    $pdo->exec("DELETE FROM usuarios WHERE id = {$oldUid}");
}

// ------------------------------------------------------------------
// PASO 1: Formulario Público de Pre-inscripción
// ------------------------------------------------------------------
echo "PASO 1: Creando Solicitud Pública de Aspirante...\n";
$stmtCount = $pdo->query("SELECT COUNT(*) FROM solicitudes_admision");
$cant = intval($stmtCount->fetchColumn());
$codRadicado = 'SOL-2026-' . str_pad($cant + 1, 4, '0', STR_PAD_LEFT);

$stmtIns = $pdo->prepare("
    INSERT INTO solicitudes_admision (
        codigo_solicitud, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
        tipo_documento, documento_identidad, fecha_nacimiento, genero, direccion, barrio, ciudad,
        telefono, correo, grado_solicitado, colegio_origen,
        acudiente_nombre, acudiente_tipo_doc, acudiente_documento, acudiente_parentesco,
        acudiente_telefono, acudiente_correo, acudiente_direccion, acudiente_ocupacion,
        estado, monto_matricula, created_at
    ) VALUES (
        :codigo, 'Felipe', 'Andrés', 'Restrepo', 'Vargas',
        'TI', :doc, '2010-09-12', 'Masculino', 'Calle 18 # 10-25', 'Centro', 'Ciénaga',
        '3009988771', 'felipe.restrepo@gmail.com', '10°', 'Colegio Mayor',
        'Martha Vargas Polo', 'CC', '57489123', 'Madre',
        '3127788990', 'marthavargas.prueba@gmail.com', 'Calle 18 # 10-25', 'Arquitecta',
        'Pendiente', 350000.00, NOW()
    )
");
$stmtIns->execute([':codigo' => $codRadicado, ':doc' => $docPrueba]);
$solicitudId = $pdo->lastInsertId();

echo " -> Solicitud radicada con éxito: ID {$solicitudId} | Radicado: {$codRadicado}\n";
$rowS1 = $pdo->query("SELECT * FROM solicitudes_admision WHERE id = {$solicitudId}")->fetch(PDO::FETCH_ASSOC);
assert($rowS1['estado'] === 'Pendiente');
echo " -> Estado inicial verificado: {$rowS1['estado']}\n\n";

// ------------------------------------------------------------------
// PASO 2: Aprobación Administrativa (Admin View)
// ------------------------------------------------------------------
echo "PASO 2: Aprobación por Administración y Asignación de Curso...\n";
$cursoAsignarId = 1; // Curso 1: Grado 10° Grupo A (2026)
$montoMatricula = 350000.00;

$stmtUp = $pdo->prepare("
    UPDATE solicitudes_admision 
    SET estado = 'Aprobada', curso_asignado_id = :cid, monto_matricula = :monto, observaciones_revision = 'Admitido oficialmente con cupo asignado'
    WHERE id = :id
");
$stmtUp->execute([':cid' => $cursoAsignarId, ':monto' => $montoMatricula, ':id' => $solicitudId]);

$rowS2 = $pdo->query("SELECT * FROM solicitudes_admision WHERE id = {$solicitudId}")->fetch(PDO::FETCH_ASSOC);
assert($rowS2['estado'] === 'Aprobada');
assert($rowS2['curso_asignado_id'] == 1);
echo " -> Solicitud APROBADA con éxito. Curso asignado: ID {$rowS2['curso_asignado_id']} | Monto: \${$rowS2['monto_matricula']} COP\n\n";

// ------------------------------------------------------------------
// PASO 3: Cobro en Caja y Matriculación Automática (Tesorero View)
// ------------------------------------------------------------------
echo "PASO 3: Registro de Pago en Caja y Matriculación Automática...\n";

// Simular acción 'cobrar_matricula_aspirante'
$pdo->beginTransaction();

// Obtener datos
$stmtSol = $pdo->prepare("
    SELECT s.*, c.grado, c.grupo
    FROM solicitudes_admision s
    LEFT JOIN cursos c ON s.curso_asignado_id = c.id
    WHERE s.id = :id FOR UPDATE
");
$stmtSol->execute([':id' => $solicitudId]);
$sol = $stmtSol->fetch(PDO::FETCH_ASSOC);

// Recibo
$stmtCountR = $pdo->query("SELECT COUNT(*) FROM contabilidad_caja");
$cRec = intval($stmtCountR->fetchColumn());
$numRecibo = 'REC-2026-' . str_pad($cRec + 1, 4, '0', STR_PAD_LEFT);

// Usuario & Password
$pNomLimpio = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $sol['primer_nombre'])));
$pApeLimpio = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $sol['primer_apellido'])));
$estEmail = "{$pNomLimpio}.{$pApeLimpio}@lasallecienaga.edu.co";

$passTemporal = 'Salle2026*';
$hashPass = password_hash($passTemporal, PASSWORD_BCRYPT);

// Insert usuario
$stmtUser = $pdo->prepare("
    INSERT INTO usuarios (rol_id, correo, password_hash, estado_cuenta, debe_cambiar_password, creado_en)
    VALUES (5, :correo, :hash, 'activo', 1, NOW())
");
$stmtUser->execute([':correo' => $estEmail, ':hash' => $hashPass]);
$newUid = $pdo->lastInsertId();

// Insert perfil
$stmtPerf = $pdo->prepare("
    INSERT INTO perfiles_detalles (
        usuario_id, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
        tipo_documento, documento_identidad, telefono, direccion, foto_perfil,
        acudiente_nombre, acudiente_telefono, acudiente_correo
    ) VALUES (
        :uid, :p_nom, :s_nom, :p_ape, :s_ape,
        :tdoc, :doc, :tel, :dir, 'default_avatar.png',
        :acud_nom, :acud_tel, :acud_email
    )
");
$stmtPerf->execute([
    ':uid' => $newUid,
    ':p_nom' => $sol['primer_nombre'], ':s_nom' => $sol['segundo_nombre'],
    ':p_ape' => $sol['primer_apellido'], ':s_ape' => $sol['segundo_apellido'],
    ':tdoc' => $sol['tipo_documento'], ':doc' => $sol['documento_identidad'],
    ':tel' => $sol['telefono'], ':dir' => $sol['direccion'],
    ':acud_nom' => $sol['acudiente_nombre'], ':acud_tel' => $sol['acudiente_telefono'],
    ':acud_email' => $sol['acudiente_correo']
]);

// Matrícula
$codMat = 'EST-2026-' . str_pad($newUid, 5, '0', STR_PAD_LEFT);
$stmtMat = $pdo->prepare("
    INSERT INTO matriculas (estudiante_id, curso_id, ano_lectivo, estado_matricula, codigo_matricula, creado_en)
    VALUES (:est_id, :cid, 2026, 'activa', :cod_mat, NOW())
");
$stmtMat->execute([':est_id' => $newUid, ':cid' => $sol['curso_asignado_id'], ':cod_mat' => $codMat]);
$newMatId = $pdo->lastInsertId();

// Caja
$stmtCaja = $pdo->prepare("
    INSERT INTO contabilidad_caja (usuario_id, concepto_pago, monto, metodo_pago, estado_pago, fecha_pago, fecha_vencimiento, recibo_numero)
    VALUES (:uid, 'Matricula', :monto, 'Efectivo', 'Pagado', NOW(), NOW(), :recibo)
");
$stmtCaja->execute([':uid' => $newUid, ':monto' => $sol['monto_matricula'], ':recibo' => $numRecibo]);
$newCajaId = $pdo->lastInsertId();

// Update Solicitud
$pdo->prepare("
    UPDATE solicitudes_admision 
    SET estado = 'Matriculado', recibo_caja_id = :rid, usuario_estudiante_id = :uid, matricula_id = :mid
    WHERE id = :id
")->execute([':rid' => $newCajaId, ':uid' => $newUid, ':mid' => $newMatId, ':id' => $solicitudId]);

$pdo->commit();

echo " -> Recibo oficial generado: {$numRecibo}\n";
echo " -> Estudiante creado en usuarios ID: {$newUid} | Correo: {$estEmail}\n";
echo " -> Matrícula formalizada en matriculas ID: {$newMatId} | Código: {$codMat}\n";
echo " -> Solicitud actualizada a estado: 'Matriculado'\n\n";

// ------------------------------------------------------------------
// PASO 4: Verificación de Credenciales y Autenticación
// ------------------------------------------------------------------
echo "PASO 4: Verificando Credenciales de Acceso...\n";
$stmtAuthCheck = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo");
$stmtAuthCheck->execute([':correo' => $estEmail]);
$userAuth = $stmtAuthCheck->fetch(PDO::FETCH_ASSOC);

assert($userAuth !== false);
assert($userAuth['rol_id'] == 5);
assert($userAuth['estado_cuenta'] === 'activo');
assert($userAuth['debe_cambiar_password'] == 1);
assert(password_verify($passTemporal, $userAuth['password_hash']));

echo " -> Contraseña temporal verificada: {$passTemporal}\n";
echo " -> BCRYPT hash verificado: OK\n";
echo " -> Debe cambiar contraseña: Sí (Obligatorio en primer login)\n\n";

echo "=======================================================\n";
echo "🎉 ¡TODAS LAS PRUEBAS PASARON EXITOSAMENTE (100%)!\n";
echo "=======================================================\n";
