<?php
/**
 * SERVICIO DE IMPORTACIÓN Y CARGA MASIVA INTELIGENTE DE USUARIOS
 * Instituto La Salle Ciénaga Magdalena
 * 
 * Motor de auto-identificación agnóstica al orden de columnas,
 * detección de nombres divididos o compuestos, teléfono, documento,
 * curso/grado y vinculación automática de acudientes.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

class UserBulkImporter {
    /** @var PDO|null */
    private $pdo;

    /** @var array */
    private $cursosLookup = [];

    /** @var array */
    private $rolesMap = [
        1 => 'Administrador',
        2 => 'Administrativo',
        3 => 'Docente',
        4 => 'Psicólogo Orientador',
        5 => 'Estudiante',
        6 => 'Acudiente'
    ];

    public function __construct(?PDO $pdo = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            $db = Database::getInstance();
            $this->pdo = $db->getConnection();
        }
        $this->loadCursosLookup();
    }

    /**
     * Cargar mapa flexible de cursos para auto-emparejamiento fuzzy
     */
    private function loadCursosLookup() {
        $this->cursosLookup = [];
        if (!$this->pdo) return;

        try {
            $stmt = $this->pdo->query("SELECT id, grado, grupo, ano_lectivo FROM cursos ORDER BY ano_lectivo DESC, grado, grupo");
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cursos as $c) {
                $cId = intval($c['id']);
                $g = $this->normalizeString($c['grado']);
                $gr = $this->normalizeString($c['grupo']);

                // Llaves de emparejamiento flexible
                $this->cursosLookup[$g . ' ' . $gr] = $cId;
                $this->cursosLookup[$g . $gr] = $cId;
                $this->cursosLookup[str_replace('o', '', $g) . $gr] = $cId;
                $this->cursosLookup[str_replace('o', '', $g) . ' ' . $gr] = $cId;
                $this->cursosLookup[strval($cId)] = $cId;

                // Grado puro en palabras (ej: decimo -> grado)
                $nombresGrados = [
                    '6' => 'sexto', '7' => 'septimo', '8' => 'octavo',
                    '9' => 'noveno', '10' => 'decimo', '11' => 'once'
                ];
                $numGrado = preg_replace('/[^0-9]/', '', $g);
                if (isset($nombresGrados[$numGrado])) {
                    $palabra = $nombresGrados[$numGrado];
                    $this->cursosLookup[$palabra . ' ' . $gr] = $cId;
                    $this->cursosLookup[$palabra . $gr] = $cId;
                }
            }
        } catch (Exception $e) {
            error_log("Error cargando cursos en UserBulkImporter: " . $e->getMessage());
        }
    }

    /**
     * Limpia y normaliza texto eliminando tildes, signos y mayúsculas
     */
    public function normalizeString($str) {
        if ($str === null || $str === '') return '';
        $str = mb_strtolower(trim($str), 'UTF-8');
        $replacements = [
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u', 'ñ'=>'n',
            '°'=>'', 'º'=>'', '#' => '', '-' => ' ', '_' => ' ', '.' => ''
        ];
        $str = strtr($str, $replacements);
        return trim(preg_replace('/\s+/', ' ', $str));
    }

    /**
     * Procesa texto sin formato (pegado en textarea o extraído de CSV)
     */
    public function processRawText($rawText, $defaultRoleId = 5, $defaultCourseId = 0) {
        $rawText = preg_replace('/^\xEF\xBB\xBF/', '', trim($rawText)); // Quitar BOM UTF-8
        if (empty($rawText)) {
            return [
                'success' => false,
                'message' => 'El texto o archivo proporcionado está vacío.',
                'total' => 0, 'created' => 0, 'skipped' => 0, 'users' => []
            ];
        }

        $lines = preg_split('/\r\n|\r|\n/', $rawText);
        $rows = [];
        $delimiter = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if ($delimiter === null) {
                // Detectar delimitador analizando la primera línea
                $countComma = substr_count($line, ',');
                $countSemi  = substr_count($line, ';');
                $countTab   = substr_count($line, "\t");
                if ($countSemi >= $countComma && $countSemi >= $countTab && $countSemi > 0) {
                    $delimiter = ';';
                } elseif ($countTab >= $countComma && $countTab > 0) {
                    $delimiter = "\t";
                } else {
                    $delimiter = ',';
                }
            }

            $cols = str_getcsv($line, $delimiter, '"');
            $cols = array_map('trim', $cols);
            if (count($cols) > 0 && !empty(implode('', $cols))) {
                $rows[] = $cols;
            }
        }

        return $this->processRows($rows, $defaultRoleId, $defaultCourseId);
    }

    /**
     * Procesa un array de filas (con o sin encabezados)
     */
    public function processRows(array $rows, $defaultRoleId = 5, $defaultCourseId = 0) {
        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'No se encontraron filas con datos.',
                'total' => 0, 'created' => 0, 'skipped' => 0, 'users' => []
            ];
        }

        // Determinar si la fila 0 contiene encabezados y detectar mapa de columnas
        $columnMap = $this->detectColumnMap($rows[0]);
        $startIndex = 0;

        if ($columnMap['is_header']) {
            $startIndex = 1;
        } else {
            // Si la primera fila no tenía encabezados claros, intentar deducir columnas por inspección de contenido
            $columnMap = $this->detectColumnsByContent($rows);
        }

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $conAcudienteCount = 0;
        $processedUsers = [];
        $skippedUsers = [];

        // Obtener todos los correos y documentos ya registrados para chequeo ultra rápido
        $existingEmails = [];
        $existingDocs = [];
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->query("SELECT u.id, LOWER(u.correo) AS correo, p.documento_identidad FROM usuarios u LEFT JOIN perfiles_detalles p ON u.id = p.usuario_id");
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (!empty($r['correo'])) $existingEmails[$r['correo']] = true;
                    if (!empty($r['documento_identidad'])) $existingDocs[$r['documento_identidad']] = true;
                }
            } catch (Exception $e) {}
        }

        // Revisar también en sesión demo
        if (isset($_SESSION['demo_users']) && is_array($_SESSION['demo_users'])) {
            foreach ($_SESSION['demo_users'] as $du) {
                if (!empty($du['correo'])) $existingEmails[strtolower($du['correo'])] = true;
                if (!empty($du['documento_identidad'])) $existingDocs[$du['documento_identidad']] = true;
            }
        }

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(implode('', $row))) continue;

            $userData = $this->extractUserDataFromRow($row, $columnMap, $defaultRoleId, $defaultCourseId);

            // Validar existencia de datos mínimos
            if (empty($userData['primer_nombre']) && empty($userData['primer_apellido']) && empty($userData['documento'])) {
                continue;
            }

            // Normalizar Correo Institucional si viene vacío
            if (empty($userData['correo']) || strpos($userData['correo'], '@') === false) {
                $cleanNom = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $userData['primer_nombre']));
                $cleanApe = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $userData['primer_apellido']));
                $randomSuffix = rand(10, 99);
                $userData['correo'] = ($cleanNom ?: 'usuario') . '.' . ($cleanApe ?: 'salle') . $randomSuffix . '@lasallecienaga.edu.co';
            }

            // Documento de respaldo si no viene especificado
            if (empty($userData['documento'])) {
                $userData['documento'] = strval(rand(1080000000, 1089999999));
            }

            // Validar duplicados / usuarios ya existentes
            $lowerEmail = strtolower($userData['correo']);
            if (isset($existingEmails[$lowerEmail]) || isset($existingDocs[$userData['documento']])) {
                $wasUpdated = false;
                if ($this->pdo) {
                    try {
                        // Buscar ID del usuario existente
                        $stmtFind = $this->pdo->prepare("
                            SELECT u.id, u.rol_id, pd.acudiente_nombre, pd.acudiente_correo, pd.acudiente_telefono, pd.telefono, pd.direccion
                            FROM usuarios u
                            LEFT JOIN perfiles_detalles pd ON u.id = pd.usuario_id
                            WHERE pd.documento_identidad = :doc OR LOWER(u.correo) = :correo
                            LIMIT 1
                        ");
                        $stmtFind->execute([':doc' => $userData['documento'], ':correo' => $lowerEmail]);
                        $found = $stmtFind->fetch(PDO::FETCH_ASSOC);

                        if ($found) {
                            $exId = $found['id'];
                            // Determinar campos nuevos a actualizar
                            $updANom    = !empty($userData['acudiente_nombre']) ? $userData['acudiente_nombre'] : $found['acudiente_nombre'];
                            $updACorreo = !empty($userData['acudiente_correo']) ? $userData['acudiente_correo'] : $found['acudiente_correo'];
                            $updATel    = !empty($userData['acudiente_telefono']) ? $userData['acudiente_telefono'] : $found['acudiente_telefono'];
                            $updTel     = (!empty($userData['telefono']) && $userData['telefono'] !== '3000000000') ? $userData['telefono'] : $found['telefono'];
                            $updDir     = (!empty($userData['direccion']) && $userData['direccion'] !== 'Ciénaga, Magdalena') ? $userData['direccion'] : $found['direccion'];

                            $stmtUpdProfile = $this->pdo->prepare("
                                UPDATE perfiles_detalles 
                                SET acudiente_nombre   = :anom,
                                    acudiente_correo   = :acorreo,
                                    acudiente_telefono = :atel,
                                    telefono           = :tel,
                                    direccion          = :dir
                                WHERE usuario_id = :uid
                            ");
                            $stmtUpdProfile->execute([
                                ':anom'    => $updANom,
                                ':acorreo' => $updACorreo,
                                ':atel'    => $updATel,
                                ':tel'     => $updTel,
                                ':dir'     => $updDir,
                                ':uid'     => $exId
                            ]);

                            // Si es estudiante y se especificó curso
                            if ($found['rol_id'] == 5 && $userData['curso_id'] > 0) {
                                $anoLect = intval(date('Y'));
                                $codMat  = 'EST-' . $anoLect . '-' . str_pad($exId, 5, '0', STR_PAD_LEFT);
                                $stmtMat = $this->pdo->prepare("
                                    INSERT INTO matriculas (estudiante_id, curso_id, ano_lectivo, estado_matricula, codigo_matricula) 
                                    VALUES (:est_id, :cur_id, :ano, 'activa', :cod) 
                                    ON DUPLICATE KEY UPDATE curso_id = :cur_id, estado_matricula = 'activa'
                                ");
                                $stmtMat->execute([
                                    ':est_id' => $exId,
                                    ':cur_id' => $userData['curso_id'],
                                    ':ano'    => $anoLect,
                                    ':cod'    => $codMat
                                ]);
                            }

                            $wasUpdated = true;
                        }
                    } catch (Exception $e) {
                        error_log("Error actualizando usuario existente en UserBulkImporter: " . $e->getMessage());
                    }
                }

                // Sincronizar en sesión demo si aplica
                if (isset($_SESSION['demo_users']) && is_array($_SESSION['demo_users'])) {
                    foreach ($_SESSION['demo_users'] as &$du) {
                        if ($du['documento_identidad'] == $userData['documento'] || strtolower($du['correo']) == $lowerEmail) {
                            if (!empty($userData['acudiente_nombre'])) $du['acudiente_nombre'] = $userData['acudiente_nombre'];
                            if (!empty($userData['acudiente_correo'])) $du['acudiente_correo'] = $userData['acudiente_correo'];
                            if (!empty($userData['acudiente_telefono'])) $du['acudiente_telefono'] = $userData['acudiente_telefono'];
                            if (!empty($userData['telefono']) && $userData['telefono'] !== '3000000000') $du['telefono'] = $userData['telefono'];
                            $wasUpdated = true;
                            break;
                        }
                    }
                    unset($du);
                }

                if ($wasUpdated) {
                    $updatedCount++;
                    if (!empty($userData['acudiente_nombre']) || !empty($userData['acudiente_correo'])) {
                        $conAcudienteCount++;
                    }
                    $skippedUsers[] = [
                        'nombre' => $userData['primer_nombre'] . ' ' . $userData['primer_apellido'],
                        'correo' => $userData['correo'],
                        'documento' => $userData['documento'],
                        'motivo' => 'Usuario existente actualizado con nueva información de acudiente / contacto'
                    ];
                } else {
                    $skippedCount++;
                    $skippedUsers[] = [
                        'nombre' => $userData['primer_nombre'] . ' ' . $userData['primer_apellido'],
                        'correo' => $userData['correo'],
                        'documento' => $userData['documento'],
                        'motivo' => 'Documento o correo ya registrado previamente'
                    ];
                }
                continue;
            }

            // Guardar en Base de Datos
            $newUserId = null;
            if ($this->pdo) {
                try {
                    $passHash = password_hash($userData['password'], PASSWORD_BCRYPT);
                    $debeCambiar = (in_array($userData['rol_id'], [3, 5]) || $userData['password'] === 'Salle2026*') ? 1 : 0;
                    $stmtUser = $this->pdo->prepare("INSERT INTO usuarios (rol_id, correo, password_hash, estado_cuenta, debe_cambiar_password) VALUES (:rol_id, :correo, :pass_hash, 'activo', :debe_cambiar)");
                    $stmtUser->execute([
                        ':rol_id'       => $userData['rol_id'],
                        ':correo'       => $userData['correo'],
                        ':pass_hash'    => $passHash,
                        ':debe_cambiar' => $debeCambiar
                    ]);
                    $newUserId = $this->pdo->lastInsertId();

                    $stmtProfile = $this->pdo->prepare("
                        INSERT INTO perfiles_detalles 
                            (usuario_id, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, tipo_documento, documento_identidad, telefono, direccion, acudiente_nombre, acudiente_correo, acudiente_telefono) 
                        VALUES 
                            (:user_id, :p_nom, :s_nom, :p_ape, :s_ape, :tipo_doc, :doc, :tel, :dir, :a_nom, :a_correo, :a_tel)
                    ");
                    $stmtProfile->execute([
                        ':user_id'  => $newUserId,
                        ':p_nom'    => $userData['primer_nombre'],
                        ':s_nom'    => $userData['segundo_nombre'],
                        ':p_ape'    => $userData['primer_apellido'],
                        ':s_ape'    => $userData['segundo_apellido'],
                        ':tipo_doc' => $userData['tipo_documento'],
                        ':doc'      => $userData['documento'],
                        ':tel'      => $userData['telefono'],
                        ':dir'      => $userData['direccion'],
                        ':a_nom'    => ($userData['rol_id'] == 5 ? $userData['acudiente_nombre'] : null),
                        ':a_correo' => ($userData['rol_id'] == 5 ? $userData['acudiente_correo'] : null),
                        ':a_tel'    => ($userData['rol_id'] == 5 ? $userData['acudiente_telefono'] : null),
                    ]);

                    // Si es estudiante y tiene curso asignado, matricular
                    if ($userData['rol_id'] == 5 && $userData['curso_id'] > 0) {
                        $anoLect = intval(date('Y'));
                        $codMat  = 'EST-' . $anoLect . '-' . str_pad($newUserId, 5, '0', STR_PAD_LEFT);
                        $stmtMat = $this->pdo->prepare("
                            INSERT INTO matriculas (estudiante_id, curso_id, ano_lectivo, estado_matricula, codigo_matricula) 
                            VALUES (:est_id, :cur_id, :ano, 'activa', :cod) 
                            ON DUPLICATE KEY UPDATE curso_id = :cur_id, estado_matricula = 'activa'
                        ");
                        $stmtMat->execute([
                            ':est_id' => $newUserId,
                            ':cur_id' => $userData['curso_id'],
                            ':ano'    => $anoLect,
                            ':cod'    => $codMat
                        ]);
                    }
                } catch (Exception $e) {
                    error_log("Error guardando usuario en carga masiva: " . $e->getMessage());
                }
            }

            // Fallback de ID para demo en sesión
            if (!$newUserId) {
                $newUserId = rand(1000, 9999);
            }

            // Mantener sincrónicos los arrays de sesión
            $newUserSession = [
                'id' => $newUserId,
                'correo' => $userData['correo'],
                'password' => $userData['password'],
                'password_hash' => password_hash($userData['password'], PASSWORD_BCRYPT),
                'estado_cuenta' => 'activo',
                'rol_id' => $userData['rol_id'],
                'nombre_rol' => $this->rolesMap[$userData['rol_id']] ?? 'Estudiante',
                'primer_nombre' => $userData['primer_nombre'],
                'segundo_nombre' => $userData['segundo_nombre'],
                'primer_apellido' => $userData['primer_apellido'],
                'segundo_apellido' => $userData['segundo_apellido'],
                'tipo_documento' => $userData['tipo_documento'],
                'documento_identidad' => $userData['documento'],
                'telefono' => $userData['telefono'],
                'direccion' => $userData['direccion'],
                'acudiente_nombre' => $userData['acudiente_nombre'],
                'acudiente_correo' => $userData['acudiente_correo'],
                'acudiente_telefono' => $userData['acudiente_telefono'],
                'debe_cambiar_password' => $debeCambiar,
                'ultimo_acceso' => 'Recién importado',
                'grado' => '10°',
                'curso' => 'DÉCIMO A'
            ];

            if (!isset($_SESSION['demo_users'])) {
                $_SESSION['demo_users'] = [];
            }
            array_unshift($_SESSION['demo_users'], $newUserSession);

            $existingEmails[$lowerEmail] = true;
            $existingDocs[$userData['documento']] = true;
            $createdCount++;
            if (!empty($userData['acudiente_nombre']) || !empty($userData['acudiente_correo'])) {
                $conAcudienteCount++;
            }
            $processedUsers[] = $newUserSession;
        }

        return [
            'success' => ($createdCount > 0 || $updatedCount > 0),
            'total_rows' => count($rows) - ($columnMap['is_header'] ? 1 : 0),
            'created' => $createdCount,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'con_acudiente' => $conAcudienteCount,
            'users' => $processedUsers,
            'skipped_details' => $skippedUsers,
            'column_map' => $columnMap['fields']
        ];
    }

    /**
     * Extrae y mapea los datos de un usuario a partir de una fila y el mapa de columnas
     */
    private function extractUserDataFromRow(array $row, array $columnMap, $defaultRoleId = 5, $defaultCourseId = 0) {
        $fields = $columnMap['fields'];

        // Helpers de lectura por índice
        $getVal = function($fieldKey) use ($fields, $row) {
            if (isset($fields[$fieldKey]) && is_numeric($fields[$fieldKey])) {
                $idx = intval($fields[$fieldKey]);
                if (isset($row[$idx])) {
                    return trim($row[$idx]);
                }
            }
            return '';
        };

        // Extracción directa si vienen separados
        $pNom = $getVal('primer_nombre');
        $sNom = $getVal('segundo_nombre');
        $pApe = $getVal('primer_apellido');
        $sApe = $getVal('segundo_apellido');

        // Si viene nombre completo unificado
        $nombreCompleto = $getVal('nombre_completo');
        if (!empty($nombreCompleto) && (empty($pNom) || empty($pApe))) {
            $split = $this->splitFullName($nombreCompleto);
            if (empty($pNom)) $pNom = $split['primer_nombre'];
            if (empty($sNom)) $sNom = $split['segundo_nombre'];
            if (empty($pApe)) $pApe = $split['primer_apellido'];
            if (empty($sApe)) $sApe = $split['segundo_apellido'];
        }

        // Si viene solo columna "nombres" y columna "apellidos"
        $nombres = $getVal('nombres');
        if (!empty($nombres) && empty($pNom)) {
            $partesNom = preg_split('/\s+/', $nombres);
            $pNom = $partesNom[0] ?? 'Usuario';
            $sNom = isset($partesNom[1]) ? implode(' ', array_slice($partesNom, 1)) : '';
        }

        $apellidos = $getVal('apellidos');
        if (!empty($apellidos) && empty($pApe)) {
            $partesApe = preg_split('/\s+/', $apellidos);
            $pApe = $partesApe[0] ?? 'LaSalle';
            $sApe = isset($partesApe[1]) ? implode(' ', array_slice($partesApe, 1)) : '';
        }

        // Fallbacks si aún no hay nombres
        if (empty($pNom) && empty($pApe)) {
            $pNom = $row[0] ?? 'Usuario';
            $pApe = $row[1] ?? 'LaSalle';
        }

        $documento = $getVal('documento');
        $tipoDoc   = strtoupper($getVal('tipo_documento'));
        if (empty($tipoDoc) || !in_array($tipoDoc, ['CC','TI','CE','RC','PASAPORTE'])) {
            $tipoDoc = (strlen($documento) > 8) ? 'TI' : 'CC';
        }

        $correo = $getVal('correo');
        $rawTel = $getVal('telefono');
        $digitsTel = preg_replace('/\D/', '', $rawTel);
        if (substr($digitsTel, 0, 2) === '57' && strlen($digitsTel) === 12 && substr($digitsTel, 2, 1) === '3') {
            $digitsTel = substr($digitsTel, 2);
        }
        $telefono = !empty($digitsTel) ? $digitsTel : (!empty($rawTel) ? $rawTel : null);
        $direccion = $getVal('direccion') ?: 'Ciénaga, Magdalena';
        $clave = $getVal('password') ?: 'Salle2026*';

        // Datos del acudiente
        $aNom = $getVal('acudiente_nombre');
        $aCorreo = $getVal('acudiente_correo');
        $aTel = $getVal('acudiente_telefono');

        // Rol
        $rolRaw = $getVal('rol');
        $rolId = $this->resolveRoleId($rolRaw, $defaultRoleId);

        // Si el usuario no es estudiante (docente, administrativo, etc.) y se suministró un teléfono en columna de acudiente
        if ($rolId != 5 && empty($telefono) && !empty($aTel)) {
            $digitsATel = preg_replace('/\D/', '', $aTel);
            if (substr($digitsATel, 0, 2) === '57' && strlen($digitsATel) === 12 && substr($digitsATel, 2, 1) === '3') {
                $digitsATel = substr($digitsATel, 2);
            }
            $telefono = !empty($digitsATel) ? $digitsATel : $aTel;
            $aTel = null;
            $aNom = null;
            $aCorreo = null;
        }

        // Curso
        $cursoRaw = $getVal('curso');
        $cursoId = $this->resolveCourseId($cursoRaw, $defaultCourseId);

        return [
            'primer_nombre'     => $pNom ?: 'Usuario',
            'segundo_nombre'    => $sNom,
            'primer_apellido'   => $pApe ?: 'LaSalle',
            'segundo_apellido'  => $sApe,
            'tipo_documento'    => $tipoDoc,
            'documento'         => $documento,
            'correo'            => $correo,
            'telefono'          => $telefono,
            'direccion'         => $direccion,
            'password'          => $clave,
            'rol_id'            => $rolId,
            'curso_id'          => $cursoId,
            'acudiente_nombre'  => $aNom,
            'acudiente_correo'  => $aCorreo,
            'acudiente_telefono'=> $aTel
        ];
    }

    /**
     * Detección exhaustiva de encabezados agnóstica al orden
     */
    public function detectColumnMap(array $firstRow) {
        $fields = [];
        $matchedHeadersCount = 0;

        foreach ($firstRow as $idx => $headerText) {
            $h = $this->normalizeString($headerText);
            if (empty($h)) continue;

            // 1. Campos de Acudiente (prioridad alta para no confundir con datos del estudiante)
            if (strpos($h, 'acudiente') !== false || strpos($h, 'tutor') !== false || strpos($h, 'padre') !== false || strpos($h, 'madre') !== false || strpos($h, 'representante') !== false) {
                if (strpos($h, 'correo') !== false || strpos($h, 'email') !== false || strpos($h, 'mail') !== false) {
                    $fields['acudiente_correo'] = $idx;
                } elseif (strpos($h, 'tel') !== false || strpos($h, 'cel') !== false || strpos($h, 'whatsapp') !== false || strpos($h, 'movil') !== false) {
                    $fields['acudiente_telefono'] = $idx;
                } else {
                    $fields['acudiente_nombre'] = $idx;
                }
                $matchedHeadersCount++;
                continue;
            }

            // 2. Correo del Estudiante / Usuario
            if (strpos($h, 'correo') !== false || strpos($h, 'email') !== false || strpos($h, 'mail') !== false) {
                if (!isset($fields['correo'])) {
                    $fields['correo'] = $idx;
                    $matchedHeadersCount++;
                }
                continue;
            }

            // 3. Teléfono / Celular del Docente / Estudiante / Usuario
            if (
                strpos($h, 'tel') !== false || 
                strpos($h, 'cel') !== false || 
                strpos($h, 'movil') !== false || 
                strpos($h, 'whatsapp') !== false || 
                strpos($h, 'contacto') !== false ||
                strpos($h, 'phone') !== false ||
                strpos($h, 'llamada') !== false
            ) {
                if (!isset($fields['telefono'])) {
                    $fields['telefono'] = $idx;
                    $matchedHeadersCount++;
                }
                continue;
            }

            // 4. Tipo de Documento
            if ((strpos($h, 'tipo') !== false && (strpos($h, 'doc') !== false || strpos($h, 'id') !== false)) || $h === 'tipodoc' || $h === 'tipo') {
                $fields['tipo_documento'] = $idx;
                $matchedHeadersCount++;
                continue;
            }

            // 5. Documento / Identificación / TI / CC
            if (strpos($h, 'documento') !== false || strpos($h, 'identificacion') !== false || strpos($h, 'cedula') !== false || strpos($h, 'tarjeta') !== false || $h === 'ti' || $h === 'cc' || $h === 'dni' || $h === 'doc') {
                $fields['documento'] = $idx;
                $matchedHeadersCount++;
                continue;
            }

            // 6. Nombres separados o unificados
            if (strpos($h, 'segundo nombre') !== false || $h === 's nombre') {
                $fields['segundo_nombre'] = $idx;
                $matchedHeadersCount++;
            } elseif (strpos($h, 'primer nombre') !== false || $h === 'p nombre') {
                $fields['primer_nombre'] = $idx;
                $matchedHeadersCount++;
            } elseif (strpos($h, 'segundo apellido') !== false || $h === 's apellido') {
                $fields['segundo_apellido'] = $idx;
                $matchedHeadersCount++;
            } elseif (strpos($h, 'primer apellido') !== false || $h === 'p apellido') {
                $fields['primer_apellido'] = $idx;
                $matchedHeadersCount++;
            } elseif (strpos($h, 'nombre completo') !== false || strpos($h, 'nombres y apellidos') !== false || strpos($h, 'apellidos y nombres') !== false || strpos($h, 'nombre estudiante') !== false || strpos($h, 'alumno') !== false) {
                $fields['nombre_completo'] = $idx;
                $matchedHeadersCount++;
            } elseif (strpos($h, 'nombres') !== false || $h === 'nombre') {
                if (!isset($fields['primer_nombre']) && !isset($fields['nombres'])) {
                    $fields['nombres'] = $idx;
                    $matchedHeadersCount++;
                }
            } elseif (strpos($h, 'apellidos') !== false || $h === 'apellido') {
                if (!isset($fields['primer_apellido']) && !isset($fields['apellidos'])) {
                    $fields['apellidos'] = $idx;
                    $matchedHeadersCount++;
                }
            }

            // 7. Grado / Curso / Salón
            if (strpos($h, 'grado') !== false || strpos($h, 'curso') !== false || strpos($h, 'salon') !== false || strpos($h, 'grupo') !== false) {
                $fields['curso'] = $idx;
                $matchedHeadersCount++;
                continue;
            }

            // 8. Dirección / Residencia (incluye "direcion" con una sola c)
            if (strpos($h, 'direccion') !== false || strpos($h, 'direcion') !== false || strpos($h, 'residencia') !== false || strpos($h, 'domicilio') !== false || strpos($h, 'barrio') !== false) {
                $fields['direccion'] = $idx;
                $matchedHeadersCount++;
                continue;
            }

            // 9. Rol
            if (strpos($h, 'rol') !== false || strpos($h, 'perfil') !== false || strpos($h, 'cargo') !== false || strpos($h, 'tipo usuario') !== false) {
                $fields['rol'] = $idx;
                $matchedHeadersCount++;
                continue;
            }

            // 10. Contraseña / Password (incluye "contraseña que desea...")
            if (strpos($h, 'clave') !== false || strpos($h, 'password') !== false || strpos($h, 'contrasena') !== false || strpos($h, 'acceso') !== false) {
                $fields['password'] = $idx;
                $matchedHeadersCount++;
                continue;
            }

            // 11. Marca temporal o comentarios de Google Forms (ignorar de campos de usuario)
            if (strpos($h, 'marca temporal') !== false || strpos($h, 'timestamp') !== false || strpos($h, 'fecha') !== false || strpos($h, 'comentario') !== false || strpos($h, 'observacion') !== false) {
                $matchedHeadersCount++;
                continue;
            }
        }

        $isHeader = ($matchedHeadersCount >= 2);
        return [
            'is_header' => $isHeader,
            'fields' => $fields
        ];
    }

    /**
     * Deduce la asignación de columnas inspeccionando el contenido de las primeras filas
     */
    public function detectColumnsByContent(array $rows) {
        $fields = [];
        $sampleRows = array_slice($rows, 0, min(10, count($rows)));
        $numCols = count($rows[0]);

        for ($c = 0; $c < $numCols; $c++) {
            $isEmail = 0;
            $isPhone = 0;
            $isDoc = 0;
            $isDocType = 0;
            $isCourse = 0;

            foreach ($sampleRows as $row) {
                $val = trim($row[$c] ?? '');
                if (empty($val)) continue;

                $digits = preg_replace('/\D/', '', $val);
                if (substr($digits, 0, 2) === '57' && strlen($digits) === 12 && substr($digits, 2, 1) === '3') {
                    $digits = substr($digits, 2);
                }

                if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    $isEmail++;
                } elseif (in_array(strtoupper($val), ['TI', 'CC', 'CE', 'RC', 'PASAPORTE'])) {
                    $isDocType++;
                } elseif (preg_match('/^3\d{9}$/', $digits)) {
                    $isPhone++;
                } elseif (preg_match('/^\d{7,11}$/', $digits)) {
                    $isDoc++;
                } elseif ($this->resolveCourseId($val, 0) > 0) {
                    $isCourse++;
                }
            }

            $half = max(1, count($sampleRows) / 2);
            if ($isEmail >= $half && !isset($fields['correo'])) {
                $fields['correo'] = $c;
            } elseif ($isEmail >= $half && isset($fields['correo']) && !isset($fields['acudiente_correo'])) {
                $fields['acudiente_correo'] = $c;
            } elseif ($isDocType >= $half && !isset($fields['tipo_documento'])) {
                $fields['tipo_documento'] = $c;
            } elseif ($isPhone >= $half && !isset($fields['telefono'])) {
                $fields['telefono'] = $c;
            } elseif ($isPhone >= $half && isset($fields['telefono']) && !isset($fields['acudiente_telefono'])) {
                $fields['acudiente_telefono'] = $c;
            } elseif ($isDoc >= $half && !isset($fields['documento'])) {
                $fields['documento'] = $c;
            } elseif ($isCourse >= $half && !isset($fields['curso'])) {
                $fields['curso'] = $c;
            }
        }

        // Si no se asignó documento, buscar la primera columna numérica
        if (!isset($fields['documento'])) {
            for ($c = 0; $c < $numCols; $c++) {
                if (isset($fields['telefono']) && $fields['telefono'] == $c) continue;
                if (is_numeric(trim($rows[0][$c] ?? ''))) {
                    $fields['documento'] = $c;
                    break;
                }
            }
        }

        // Posicional estándar para nombres si no están asignados
        if (!isset($fields['primer_nombre']) && !isset($fields['nombre_completo'])) {
            $fields['primer_nombre'] = 0;
            $fields['primer_apellido'] = 1;
        }

        return [
            'is_header' => false,
            'fields' => $fields
        ];
    }

    /**
     * Descompone de forma inteligente nombres completos latinos/colombianos
     */
    public function splitFullName($fullName) {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));

        // Caso: "Apellidos, Nombres" (separados por coma)
        if (strpos($fullName, ',') !== false) {
            list($apesPart, $nomsPart) = array_map('trim', explode(',', $fullName, 2));
            $apesTokens = explode(' ', $apesPart);
            $nomsTokens = explode(' ', $nomsPart);

            return [
                'primer_nombre'     => $nomsTokens[0] ?? 'Usuario',
                'segundo_nombre'    => isset($nomsTokens[1]) ? implode(' ', array_slice($nomsTokens, 1)) : '',
                'primer_apellido'   => $apesTokens[0] ?? 'LaSalle',
                'segundo_apellido'  => isset($apesTokens[1]) ? implode(' ', array_slice($apesTokens, 1)) : '',
            ];
        }

        $rawTokens = explode(' ', $fullName);
        $total = count($rawTokens);
        if ($total <= 1) {
            return ['primer_nombre' => $rawTokens[0] ?? 'Usuario', 'segundo_nombre' => '', 'primer_apellido' => 'LaSalle', 'segundo_apellido' => ''];
        } elseif ($total == 2) {
            return ['primer_nombre' => $rawTokens[0], 'segundo_nombre' => '', 'primer_apellido' => $rawTokens[1], 'segundo_apellido' => ''];
        }

        // Agrupar partículas compuestas comunes en Colombia: 'de la', 'de los', 'del', 'de', 'san', 'santa'
        $tokens = [];
        $i = 0;
        while ($i < $total) {
            $curr = $rawTokens[$i];
            $currLower = mb_strtolower($curr, 'UTF-8');
            if (in_array($currLower, ['de', 'san', 'santa']) && $i + 1 < $total) {
                $compound = $curr;
                $nextLower = mb_strtolower($rawTokens[$i+1], 'UTF-8');
                if (in_array($nextLower, ['la', 'las', 'los', 'del']) && $i + 2 < $total) {
                    $compound .= ' ' . $rawTokens[$i+1] . ' ' . $rawTokens[$i+2];
                    $i += 3;
                } else {
                    $compound .= ' ' . $rawTokens[$i+1];
                    $i += 2;
                }
                $tokens[] = $compound;
            } elseif (in_array($currLower, ['del']) && $i + 1 < $total) {
                $tokens[] = $curr . ' ' . $rawTokens[$i+1];
                $i += 2;
            } else {
                $tokens[] = $curr;
                $i++;
            }
        }

        $tCount = count($tokens);
        if ($tCount == 2) {
            return [
                'primer_nombre'     => $tokens[0],
                'segundo_nombre'    => '',
                'primer_apellido'   => $tokens[1],
                'segundo_apellido'  => ''
            ];
        } elseif ($tCount == 3) {
            return [
                'primer_nombre'     => $tokens[0],
                'segundo_nombre'    => '',
                'primer_apellido'   => $tokens[1],
                'segundo_apellido'  => $tokens[2]
            ];
        } else {
            return [
                'primer_nombre'     => $tokens[0],
                'segundo_nombre'    => $tokens[1],
                'primer_apellido'   => $tokens[2],
                'segundo_apellido'  => implode(' ', array_slice($tokens, 3))
            ];
        }
    }

    /**
     * Resuelve el ID del rol a partir de texto
     */
    public function resolveRoleId($rawRole, $defaultRoleId = 5) {
        if (empty($rawRole)) return $defaultRoleId;
        $r = $this->normalizeString($rawRole);

        if (strpos($r, 'admin') !== false && strpos($r, 'trativo') === false) return 1;
        if (strpos($r, 'trativo') !== false || strpos($r, 'tesorer') !== false || strpos($r, 'secretar') !== false) return 2;
        if (strpos($r, 'docente') !== false || strpos($r, 'profe') !== false || strpos($r, 'maestr') !== false) return 3;
        if (strpos($r, 'psico') !== false || strpos($r, 'orienta') !== false) return 4;
        if (strpos($r, 'estudiante') !== false || strpos($r, 'alumno') !== false) return 5;
        if (strpos($r, 'acudiente') !== false || strpos($r, 'padre') !== false || strpos($r, 'tutor') !== false) return 6;

        if (is_numeric($rawRole) && intval($rawRole) >= 1 && intval($rawRole) <= 6) {
            return intval($rawRole);
        }

        return $defaultRoleId;
    }

    /**
     * Resuelve el ID de curso a matricular
     */
    public function resolveCourseId($rawCourse, $defaultCourseId = 0) {
        if (empty($rawCourse)) return $defaultCourseId;
        $c = $this->normalizeString($rawCourse);

        if (isset($this->cursosLookup[$c])) {
            return $this->cursosLookup[$c];
        }

        // Buscar coincidencia parcial (ej: "10 A", "10-A", "10A")
        $compact = str_replace(' ', '', $c);
        if (isset($this->cursosLookup[$compact])) {
            return $this->cursosLookup[$compact];
        }

        if (is_numeric($rawCourse) && intval($rawCourse) > 0) {
            return intval($rawCourse);
        }

        return $defaultCourseId;
    }
}
