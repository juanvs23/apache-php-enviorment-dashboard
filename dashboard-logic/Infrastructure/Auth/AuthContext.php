<?php

declare(strict_types=1);

namespace Dashboard\Infrastructure\Auth;

use Dashboard\Database\Connection;
use PDO;

/**
 * Contexto de autenticación del dashboard.
 *
 * Servicio inyectable que centraliza el acceso al estado de autenticación:
 * lookup del usuario actual via cookie, verificación de permisos RBAC,
 * y refresco de cookie de sesión.
 *
 * Reemplaza las funciones globales de auth.php:
 *   get_auth_user()     → currentUser()
 *   check_auth()        → isAuthenticated()
 *   refresh_auth_cookie() → refreshCookie()
 *   can()               → can()
 *
 * Dependencias:
 *   - Dashboard\Database\Connection (PDO)
 *   - Cookie 'project_user'
 */
final class AuthContext
{
    /**
     * Cache de permisos por usuario.
     *
     * @var array<string, string[]>
     */
    private array $permissionCache = [];

    /**
     * Verifica si hay un usuario autenticado.
     */
    public function isAuthenticated(): bool
    {
        return $this->currentUser() !== null;
    }

    /**
     * Retorna el usuario autenticado actual (desde cookie).
     *
     * @return array{userID: string, email: string, name: string|null, level: string, level_name: string, level_type: int}|null
     */
    public function currentUser(): ?array
    {
        $cookie = $_COOKIE['project_user'] ?? '';
        if ($cookie === '') {
            return null;
        }

        $userID = \base64_decode($cookie, true);
        if ($userID === false || $userID === '') {
            return null;
        }

        try {
            $pdo  = Connection::get();
            $stmt = $pdo->prepare('
                SELECT u.userID, u.email, u.name, u.level, l.level_name, l.level_type
                FROM USERS u
                JOIN levels l ON l.levelsID = u.level
                WHERE u.userID = :userID
                LIMIT 1
            ');
            $stmt->execute([':userID' => $userID]);
            $user = $stmt->fetch();
        } catch (\Throwable) {
            return null;
        }

        if (!$user) {
            return null;
        }

        return [
            'userID'     => $user['userID'],
            'email'      => $user['email'],
            'name'       => $user['name'],
            'level'      => $user['level'],
            'level_name' => $user['level_name'],
            'level_type' => (int) $user['level_type'],
        ];
    }

    /**
     * Refresca la cookie de autenticación (extiende 7 días).
     */
    public function refreshCookie(): void
    {
        $user = $this->currentUser();
        if ($user) {
            \setcookie(
                'project_user',
                \base64_encode($user['userID']),
                \time() + \COOKIE_EXPIRY,
                \COOKIE_PATH,
            );
        }
    }

    /**
     * Verifica si el usuario tiene un permiso específico.
     *
     * Admin (level_type=0) siempre tiene todos los permisos.
     * Los permisos se cachean por usuario para evitar consultas repetidas.
     *
     * @param string       $permKey Clave del permiso (ej: 'users.manage')
     * @param array|null   $user    Usuario a verificar (null = usuario actual)
     */
    public function can(string $permKey, ?array $user = null): bool
    {
        $user ??= $this->currentUser();
        if (!$user) {
            return false;
        }

        // Admin tiene todos los permisos
        if ($user['level_type'] === 0) {
            return true;
        }

        $uid = $user['userID'];
        if (!isset($this->permissionCache[$uid])) {
            try {
                $pdo = Connection::get();
                $stmt = $pdo->prepare('
                    SELECT p.perm_key
                    FROM permissions p
                    JOIN level_permissions lp ON lp.perm_id = p.id
                    JOIN levels l ON l.levelsID = lp.levelID
                    WHERE l.levelsID = (SELECT level FROM USERS WHERE userID = :uid)
                ');
                $stmt->execute([':uid' => $uid]);
                $this->permissionCache[$uid] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } catch (\Throwable) {
                $this->permissionCache[$uid] = [];
            }
        }

        return \in_array($permKey, $this->permissionCache[$uid] ?? []);
    }

    /**
     * Extrae el parámetro de redirect de GET o POST.
     */
    public function redirectParam(): string
    {
        return $_POST['redirect'] ?? $_GET['redirect'] ?? '';
    }

    /**
     * Determina el target del redirect post-login.
     *
     * @param string $scriptName $_SERVER['SCRIPT_NAME']
     */
    public function redirectTarget(string $scriptName): string
    {
        $param = $this->redirectParam();
        return ($param !== '' && \str_starts_with($param, '/')) ? $param : $scriptName;
    }
}
