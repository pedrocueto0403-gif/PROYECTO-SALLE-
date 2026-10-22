<?php
/**
 * MÓDULO DE MIGRACIÓN DE BASES DE DATOS HEREDADAS (ETL)
 * MigrationController.php
 * Instituto La Salle Ciénaga Magdalena
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

class MigrationController {

    /**
     * Simula o ejecuta el proceso ETL completo desde la BD plana antigua a la nueva BD normalizada
     */
    public function runMigration(array $legacyRecords = []): array {
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        $successCount = 0;
        $skippedCount = 0;
        $errorLogs = [];
        $migratedIds = [];

        // Si no se suministran registros por parámetro, se usan registros de prueba heredados desordenados
        if (empty($legacyRecords)) {
            $legacyRecords = $this->getSampleLegacyData();
        }

        // Si hay conexión a la base de datos real, ejecutamos la transacción atómica SQL
        if ($pdo !== null) {
            try {
                // Iniciar Transacción SQL para Atomicidad (Todo o Nada)
                $pdo->beginTransaction();

                $stmtUser = $pdo->prepare("
                    INSERT INTO usuarios (rol_id, correo, password_hash, estado_cuenta) 
                    VALUES (:rol_id, :correo, :password_hash, 'activo')
                ");

                $stmtPerfil = $pdo->prepare("
                    INSERT INTO perfiles_detalles 
                    (usuario_id, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, tipo_documento, documento_identidad, telefono, direccion, metadata_migracion_id) 
                    VALUES (:usuario_id, :p_nom, :s_nom, :p_ape, :s_ape, :tipo_doc, :doc_id, :tel, :dir, :legacy_id)
                ");

                foreach ($legacyRecords as $row) {
                    $legacyId = $row['old_id'] ?? 'DESCONOCIDO';

                    // 1. Lógica de Sanitización y Validación de Email
                    $emailClean = strtolower(trim($row['raw_email'] ?? ''));
                    if (!filter_var($emailClean, FILTER_VALIDATE_EMAIL)) {
                        $skippedCount++;
                        $errorLogs[] = "ID Heredado [{$legacyId}]: Email inválido '{$row['raw_email']}'. Registro omitido.";
                        continue;
                    }

                    // 2. Sanitización de Nombres y Apellidos
                    $pNombre  = $this->cleanText($row['raw_p_nombre'] ?? '');
                    $sNombre  = $this->cleanText($row['raw_s_nombre'] ?? '');
                    $pApellido = $this->cleanText($row['raw_p_apellido'] ?? '');
                    $sApellido = $this->cleanText($row['raw_s_apellido'] ?? '');
                    $docIdentidad = preg_replace('/[^0-9A-Za-z]/', '', $row['raw_documento'] ?? '');

                    if (empty($pNombre) || empty($pApellido) || empty($docIdentidad)) {
                        $skippedCount++;
                        $errorLogs[] = "ID Heredado [{$legacyId}]: Datos biográficos requeridos incompletos o inválidos. Registro omitido.";
                        continue;
                    }

                    // 3. Generación de contraseña aleatoria segura y BCRYPT Hash
                    $randomPass = bin2hex(random_bytes(4)) . 'S!'; // Ej: 4f8a12eS!
                    $passHash = password_hash($randomPass, PASSWORD_BCRYPT);

                    // Mapeo de rol antiguo a ID nuevo (Default 5 = estudiante, 3 = docente, etc)
                    $rolId = $row['old_role_code'] === 'DOC' ? 3 : 5;

                    // 4. Inserción de usuario
                    $stmtUser->execute([
                        ':rol_id' => $rolId,
                        ':correo' => $emailClean,
                        ':password_hash' => $passHash
                    ]);

                    $newUserId = $pdo->lastInsertId();

                    // 5. Inserción de perfil con validación de Unique Key en documento_identidad
                    $stmtPerfil->execute([
                        ':usuario_id' => $newUserId,
                        ':p_nom'      => $pNombre,
                        ':s_nom'      => $sNombre,
                        ':p_ape'      => $pApellido,
                        ':s_ape'      => $sApellido,
                        ':tipo_doc'   => $row['raw_tipo_doc'] ?? 'TI',
                        ':doc_id'     => $docIdentidad,
                        ':tel'        => $row['raw_telefono'] ?? NULL,
                        ':dir'        => $row['raw_direccion'] ?? NULL,
                        ':legacy_id'  => (string)$legacyId
                    ]);

                    $successCount++;
                    $migratedIds[] = $legacyId;
                }

                // Guardar Log de Auditoría en BD
                $stmtLog = $pdo->prepare("
                    INSERT INTO log_migracion_legacy (registros_exitosos, registros_omitidos, detalles_errores) 
                    VALUES (:exitosos, :omitidos, :errores)
                ");
                $stmtLog->execute([
                    ':exitosos' => $successCount,
                    ':omitidos' => $skippedCount,
                    ':errores'  => json_encode($errorLogs, JSON_UNESCAPED_UNICODE)
                ]);

                // Confirmar Transacción atómica
                $pdo->commit();

            } catch (\Exception $e) {
                // Rollback total si ocurre un fallo crítico de BD para prevenir corrupción
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errorLogs[] = "CRITICAL FAIL: Transacción abortada mediante Rollback. Razón: " . $e->getMessage();
            }
        } else {
            // Modo Simulación en vivo si no hay MySQL escuchando en local
            foreach ($legacyRecords as $row) {
                $legacyId = $row['old_id'] ?? 'DESCONOCIDO';
                $emailClean = strtolower(trim($row['raw_email'] ?? ''));
                if (!filter_var($emailClean, FILTER_VALIDATE_EMAIL)) {
                    $skippedCount++;
                    $errorLogs[] = "ID Heredado [{$legacyId}]: Email inválido '{$row['raw_email']}'. Omitido en simulación.";
                } else {
                    $successCount++;
                    $migratedIds[] = $legacyId;
                }
            }
        }

        return [
            'timestamp'          => date('Y-m-d H:i:s'),
            'registros_procesados'=> $successCount,
            'registros_omitidos'  => $skippedCount,
            'ids_exitosos'       => $migratedIds,
            'log_errores'        => $errorLogs
        ];
    }

    /**
     * Limpia caracteres extraños y normaliza texto
     */
    private function cleanText(string $text): string {
        $text = trim($text);
        return mb_convert_case($text, MB_CASE_TITLE, "UTF-8");
    }

    /**
     * Retorna un conjunto de datos desordenados antiguos para la demo ETL
     */
    private function getSampleLegacyData(): array {
        return [
            [
                'old_id' => 'LEG-1001',
                'raw_email' => 'juan.perez@gmail.com',
                'raw_p_nombre' => 'JUAN ',
                'raw_s_nombre' => 'carlos',
                'raw_p_apellido' => 'PEREZ',
                'raw_s_apellido' => 'Gomez',
                'raw_tipo_doc' => 'TI',
                'raw_documento' => '1.082.999.001',
                'raw_telefono' => '300-111-2233',
                'raw_direccion' => 'Calle 10 # 5-20 Ciénaga',
                'old_role_code' => 'EST'
            ],
            [
                'old_id' => 'LEG-1002',
                'raw_email' => 'correo_invalido_sin_arroba', // Fallará por email
                'raw_p_nombre' => 'MARIA',
                'raw_s_nombre' => '',
                'raw_p_apellido' => 'LOPEZ',
                'raw_s_apellido' => '',
                'raw_tipo_doc' => 'CC',
                'raw_documento' => '1082999002',
                'raw_telefono' => '3012223344',
                'raw_direccion' => 'Cra 4 # 12-05',
                'old_role_code' => 'DOC'
            ],
            [
                'old_id' => 'LEG-1003',
                'raw_email' => 'ana.suarez@lasalle.edu.co',
                'raw_p_nombre' => '  ana  ',
                'raw_s_nombre' => 'lucia',
                'raw_p_apellido' => 'suarez',
                'raw_s_apellido' => 'MARTINEZ',
                'raw_tipo_doc' => 'TI',
                'raw_documento' => '1.082.999.003',
                'raw_telefono' => '3159998877',
                'raw_direccion' => 'Calle 15 # 20-30 Ciénaga',
                'old_role_code' => 'EST'
            ]
        ];
    }
}
