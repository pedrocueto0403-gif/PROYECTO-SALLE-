<?php
/**
 * ENRUTADOR RBAC & CONTROL DE PERMISOS POR ROL
 * Instituto La Salle Ciénaga Magdalena
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

class Router {
    // Mapa de subdirectorios permitidos según el rol activo
    private static $roleRoutes = [
        'administrador'  => ['admin', 'docente', 'comunidad'],
        'docente'        => ['docente', 'comunidad'],
        'estudiante'     => ['comunidad'],
        'acudiente'      => ['comunidad'],
        'coordinador'    => ['admin', 'docente', 'comunidad'],
        'administrativo' => ['admin'],
        'psicologo'      => ['admin']
    ];

    /**
     * Inicia sesión de forma segura y verifica timeout
     */
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Lax');
            session_start();
        }

        // Control de inactividad
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME)) {
            session_unset();
            session_destroy();
            header("Location: " . APP_URL . "/views/auth/login.php?timeout=1");
            exit();
        }
        $_SESSION['LAST_ACTIVITY'] = time();
    }

    /**
     * Valida el acceso estricto a una sección/vista específica
     */
    public static function enforceAccess(string $requiredSection): void {
        self::initSession();

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            header("Location: " . APP_URL . "/views/auth/login.php");
            exit();
        }

        // Si el usuario tiene pendiente cambiar la contraseña provisional obligatoria en sesión
        if (!empty($_SESSION['must_change_password'])) {
            header("Location: " . APP_URL . "/views/auth/cambiar_clave_inicial.php");
            exit();
        }

        // Verificación estricta en tiempo real: verificar en base de datos si el usuario (estudiante o docente) aún debe cambiar clave
        $userRole = strtolower($_SESSION['user_role']);
        if (in_array($userRole, ['estudiante', 'docente', 'acudiente']) && isset($_SESSION['user_id'])) {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            if ($pdo) {
                try {
                    $chk = $pdo->prepare("SELECT debe_cambiar_password FROM usuarios WHERE id = :id LIMIT 1");
                    $chk->execute([':id' => $_SESSION['user_id']]);
                    $flag = $chk->fetchColumn();
                    if ($flag !== false && intval($flag) === 1) {
                        $_SESSION['must_change_password'] = true;
                        header("Location: " . APP_URL . "/views/auth/cambiar_clave_inicial.php");
                        exit();
                    }
                } catch (Exception $e) {}
            }
        }

        $userRole = strtolower($_SESSION['user_role']);
        $allowedSections = self::$roleRoutes[$userRole] ?? [];

        if (!in_array($requiredSection, $allowedSections)) {
            // Intento de violación RBAC: Registrar y enviar 403 Forbidden
            error_log("ACCESO DENEGADO 403: Usuario ID {$_SESSION['user_id']} con Rol '{$userRole}' intentó ingresar a '/views/{$requiredSection}'");
            http_response_code(403);
            header("Location: " . APP_URL . "/views/errors/403.php");
            exit();
        }
    }

    /**
     * Obtiene la ruta por defecto del Dashboard según el rol
     */
    public static function getHomeForRole(string $role): string {
        $roleMap = [
            'administrador'  => '/views/admin/index.php',
            'coordinador'    => '/views/admin/index.php',
            'administrativo' => '/views/admin/index.php',
            'docente'        => '/views/docente/index.php',
            'psicologo'      => '/views/admin/index.php',
            'estudiante'     => '/views/comunidad/index.php',
            'acudiente'      => '/views/comunidad/index.php'
        ];
        return APP_URL . ($roleMap[strtolower($role)] ?? '/views/auth/login.php');
    }
}
