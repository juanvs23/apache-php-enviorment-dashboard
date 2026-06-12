<?php

declare(strict_types=1);

namespace Dashboard\Infrastructure\Auth;

use Dashboard\Database\Connection;

/**
 * Logger de eventos de autenticación.
 *
 * Escribe en la tabla auth_logs cada intento de login (exitoso o fallido)
 * y cada logout. Los logs se consultan desde el panel de administración.
 *
 * ⚠️ Usa PDO directamente (no Use Cases) porque:
 *    - Es un cross-cutting concern, no lógica de negocio
 *    - Debe funcionar incluso si el login falla (sin usuario cargado)
 */
final class AuthLogger
{
    /**
     * Registra un evento de autenticación.
     *
     * @param string $email Email intentado (puede no existir en la DB)
     * @param string $action login_success | login_failed | logout
     */
    public function log(string $email, string $action): void
    {
        try {
            $pdo = Connection::get();
            $stmt = $pdo->prepare('
                INSERT INTO auth_logs (email, action, ip_address, user_agent)
                VALUES (:email, :action, :ip, :ua)
            ');
            $stmt->execute([
                ':email'  => $email,
                ':action' => $action,
                ':ip'     => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ':ua'     => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (\Throwable) {
            // El logging nunca debe romper la aplicación
        }
    }

    /**
     * Obtiene los últimos N registros de autenticación.
     *
     * @param int $limit Cantidad máxima de registros
     * @return list<array{id: int, email: string, action: string, ip_address: string, user_agent: string|null, created_at: string}>
     */
    public function recent(int $limit = 100): array
    {
        $pdo = Connection::get();
        $stmt = $pdo->query(
            "SELECT id, email, action, ip_address, user_agent, created_at
             FROM auth_logs
             ORDER BY created_at DESC
             LIMIT {$limit}"
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
