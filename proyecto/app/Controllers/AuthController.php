<?php
/**
 * CONTROLADOR DE AUTENTICACIÓN Y SEGURIDAD (AuthController.php)
 * Instituto La Salle Ciénaga Magdalena
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/Router.php';

class AuthController {
    
    /**
     * Procesa la solicitud de Login por POST
     */
    public function handleLogin(): void {
        Router::initSession();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . APP_URL . "/views/auth/login.php");
            exit();
        }

        $email = trim($_POST['correo'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $_SESSION['error_message'] = 'Por favor complete todos los campos.';
            header("Location: " . APP_URL . "/views/auth/login.php");
            exit();
        }

        $dbInstance = Database::getInstance();
        $pdo = $dbInstance->getConnection();

        if ($dbInstance->isConnected() && $pdo !== null) {
            // Consulta preparada PDO para evitar inyecciones SQL (permite correo o documento de identidad)
            $stmt = $pdo->prepare("
                SELECT u.id, u.correo, u.password_hash, u.estado_cuenta, u.debe_cambiar_password, u.rol_id, r.nombre_rol, 
                       p.primer_nombre, p.primer_apellido
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
                WHERE LOWER(u.correo) = LOWER(:correo) OR p.documento_identidad = :correo
                LIMIT 1
            ");
            $stmt->execute([':correo' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['estado_cuenta'] !== 'activo') {
                    $_SESSION['error_message'] = 'Su cuenta se encuentra suspendida o inactiva.';
                    header("Location: " . APP_URL . "/views/auth/login.php");
                    exit();
                }

                // Prevenir Session Fixation regenerando el ID de sesión
                session_regenerate_id(true);

                $roleName = strtolower($user['nombre_rol']);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['correo'];
                $_SESSION['user_role'] = $roleName;
                $_SESSION['user_name'] = $user['primer_nombre'] . ' ' . $user['primer_apellido'];

                // Si es docente o estudiante y debe cambiar contraseña o usó la clave provisional por defecto 'Salle2026*'
                $reqChangeRole = (in_array(intval($user['rol_id']), [3, 5]) || in_array($roleName, ['docente', 'estudiante']));
                $mustChange = (!empty($user['debe_cambiar_password']) || $password === 'Salle2026*');
                if ($reqChangeRole && $mustChange) {
                    $_SESSION['must_change_password'] = true;
                    header("Location: " . APP_URL . "/views/auth/cambiar_clave_inicial.php");
                    exit();
                }

                $destination = Router::getHomeForRole($user['nombre_rol']);
                header("Location: " . $destination);
                exit();
            }
        }

        // Credenciales demo por defecto si la base de datos no tiene el registro exacto
        if ($this->authenticateDemoUser($email, $password)) {
            return;
        }

        $_SESSION['error_message'] = 'Correo electrónico o contraseña incorrectos.';
        header("Location: " . APP_URL . "/views/auth/login.php");
        exit();
    }

    /**
     * Inicio de sesión rápido para evaluación de roles demo
     */
    private function startDemoSession(string $role): void {
        session_regenerate_id(true);

        $demoProfiles = [
            'administrador'  => ['id' => 1, 'name' => 'Herman Rodríguez', 'email' => 'admin@lasallecienaga.edu.co'],
            'administrativo' => ['id' => 2, 'name' => 'María Fernanda Gómez', 'email' => 'tesoreria@lasallecienaga.edu.co'],
            'docente'        => ['id' => 3, 'name' => 'Carlos Alberto García', 'email' => 'docente.garcia@lasallecienaga.edu.co'],
            'psicologo'      => ['id' => 4, 'name' => 'Dra. Claudia Salazar', 'email' => 'psicologia@lasallecienaga.edu.co'],
            'coordinador'    => ['id' => 7, 'name' => 'Coordinación de Convivencia', 'email' => 'coordinacion@lasallecienaga.edu.co'],
            'estudiante'     => ['id' => 5, 'name' => 'Mateo Martínez', 'email' => 'estudiante.martinez@lasallecienaga.edu.co'],
            'acudiente'      => ['id' => 6, 'name' => 'Jorge Martínez', 'email' => 'acudiente.martinez@lasallecienaga.edu.co'],
        ];

        $roleKey = strtolower($role);
        $profile = $demoProfiles[$roleKey] ?? $demoProfiles['administrador'];

        $_SESSION['user_id'] = $profile['id'];
        $_SESSION['user_email'] = $profile['email'];
        $_SESSION['user_role'] = $roleKey;
        $_SESSION['user_name'] = $profile['name'];

        header("Location: " . Router::getHomeForRole($roleKey));
        exit();
    }

    private function authenticateDemoUser(string $email, string $password): bool {
        // Inicializar demo_users si no existe en la sesión
        if (!isset($_SESSION['demo_users']) || empty($_SESSION['demo_users'])) {
            $_SESSION['demo_users'] = [
                ['id' => 1, 'correo' => 'admin@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 1, 'nombre_rol' => 'administrador', 'primer_nombre' => 'Herman', 'segundo_nombre' => 'Gualberto', 'primer_apellido' => 'Rodríguez', 'segundo_apellido' => 'Pérez', 'tipo_documento' => 'CC', 'documento_identidad' => '1082987001', 'telefono' => '3001234567', 'direccion' => 'Calle 12 # 8-45 Ciénaga'],
                ['id' => 2, 'correo' => 'tesoreria@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 2, 'nombre_rol' => 'administrativo', 'primer_nombre' => 'María', 'segundo_nombre' => 'Fernanda', 'primer_apellido' => 'Gómez', 'segundo_apellido' => 'Ríos', 'tipo_documento' => 'CC', 'documento_identidad' => '1082987002', 'telefono' => '3019876543', 'direccion' => 'Cra 15 # 10-20 Ciénaga'],
                ['id' => 3, 'correo' => 'docente.garcia@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 3, 'nombre_rol' => 'docente', 'primer_nombre' => 'Carlos', 'segundo_nombre' => 'Alberto', 'primer_apellido' => 'García', 'segundo_apellido' => 'López', 'tipo_documento' => 'CC', 'documento_identidad' => '1082987003', 'telefono' => '3104567890', 'direccion' => 'Calle 7 # 12-30 Ciénaga', 'debe_cambiar_password' => 1],
                ['id' => 4, 'correo' => 'psicologia@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 4, 'nombre_rol' => 'psicologo', 'primer_nombre' => 'Claudia', 'segundo_nombre' => 'Patricia', 'primer_apellido' => 'Salazar', 'segundo_apellido' => 'Mendoza', 'tipo_documento' => 'CC', 'documento_identidad' => '1082987004', 'telefono' => '3157890123', 'direccion' => 'Cra 8 # 5-60 Ciénaga', 'debe_cambiar_password' => 0],
                ['id' => 5, 'correo' => 'estudiante.martinez@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 5, 'nombre_rol' => 'estudiante', 'primer_nombre' => 'Mateo', 'segundo_nombre' => 'Andrés', 'primer_apellido' => 'Martínez', 'segundo_apellido' => 'Suárez', 'tipo_documento' => 'TI', 'documento_identidad' => '1082987005', 'telefono' => '3201122334', 'direccion' => 'Calle 18 # 14-50 Ciénaga', 'debe_cambiar_password' => 1],
                ['id' => 6, 'correo' => 'acudiente.martinez@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 6, 'nombre_rol' => 'acudiente', 'primer_nombre' => 'Jorge', 'segundo_nombre' => 'Luis', 'primer_apellido' => 'Martínez', 'segundo_apellido' => 'Blanco', 'tipo_documento' => 'CC', 'documento_identidad' => '1082987006', 'telefono' => '3201122334', 'direccion' => 'Calle 18 # 14-50 Ciénaga', 'debe_cambiar_password' => 0],
                ['id' => 7, 'correo' => 'coordinacion@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 7, 'nombre_rol' => 'coordinador', 'primer_nombre' => 'Coordinación', 'segundo_nombre' => 'Académica y', 'primer_apellido' => 'Convivencia', 'segundo_apellido' => 'Escolar', 'tipo_documento' => 'CC', 'documento_identidad' => '1082987007', 'telefono' => '3007654321', 'direccion' => 'Calle 12 # 8-45 Ciénaga', 'debe_cambiar_password' => 0],
            ];
        }

        foreach ($_SESSION['demo_users'] as $usr) {
            $matchEmailOrDoc = (strtolower($usr['correo']) === strtolower($email) || ($usr['documento_identidad'] ?? '') === $email);
            if ($matchEmailOrDoc) {
                if (($usr['estado_cuenta'] ?? 'activo') !== 'activo') {
                    $_SESSION['error_message'] = 'Su cuenta se encuentra suspendida o inactiva.';
                    header("Location: " . APP_URL . "/views/auth/login.php");
                    exit();
                }
                
                // Verificar contraseña
                $passMatch = false;
                if (!empty($usr['password_hash']) && password_verify($password, $usr['password_hash'])) {
                    $passMatch = true;
                } elseif (!empty($usr['password']) && ($password === $usr['password'] || $password === 'Salle2026*')) {
                    $passMatch = true;
                }

                if ($passMatch) {
                    session_regenerate_id(true);
                    $roleKey = strtolower($usr['nombre_rol']);
                    $_SESSION['user_id'] = $usr['id'];
                    $_SESSION['user_email'] = $usr['correo'];
                    $_SESSION['user_role'] = $roleKey;
                    $_SESSION['user_name'] = trim(($usr['primer_nombre'] ?? '') . ' ' . ($usr['primer_apellido'] ?? ''));

                    // Si es docente o estudiante y debe cambiar clave o usa la clave por defecto 'Salle2026*'
                    if (in_array($roleKey, ['docente', 'estudiante']) && (!empty($usr['debe_cambiar_password']) || $password === 'Salle2026*')) {
                        $_SESSION['must_change_password'] = true;
                        header("Location: " . APP_URL . "/views/auth/cambiar_clave_inicial.php");
                        exit();
                    }

                    header("Location: " . Router::getHomeForRole($roleKey));
                    exit();
                }
            }
        }

        return false;
    }

    /**
     * Cierra la sesión activa de forma segura
     */
    public function logout(): void {
        Router::initSession();
        session_unset();
        session_destroy();
        header("Location: " . APP_URL . "/views/auth/login.php?logout=1");
        exit();
    }
}

// Procesar peticiones directas al controlador
if (basename($_SERVER['PHP_SELF']) === 'AuthController.php') {
    $auth = new AuthController();
    $action = $_GET['action'] ?? 'login';
    if ($action === 'logout') {
        $auth->logout();
    } else {
        $auth->handleLogin();
    }
}
