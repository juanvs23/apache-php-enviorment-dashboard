<?php

declare(strict_types=1);

namespace Dashboard\Database;

use PDO;
use PDOException;

/**
 * Conexión Singleton a MySQL vía PDO.
 *
 * Responsabilidad única: gestionar el ciclo de vida de la conexión PDO,
 * incluyendo auto-migración del schema al conectar por primera vez.
 *
 * Los parámetros de conexión se leen desde variables de entorno.
 */
final class Connection
{
    private static ?PDO $instance = null;

    /**
     * Retorna (y crea si es necesario) la conexión PDO.
     * Ejecuta auto-migrate del schema en la primera conexión.
     */
    public static function get(): PDO
    {
        if (self::$instance === null) {
            $driver = $_ENV['DB_DRIVER'] ?? 'mysql';
            $host   = $_ENV['DB_HOST']   ?? 'localhost';
            $port   = $_ENV['DB_PORT']   ?? '3306';
            $dbName = $_ENV['DB_NAME']   ?? 'apache-dashboard';
            $user   = $_ENV['DB_USER']   ?? '';
            $pass   = $_ENV['DB_PASS']   ?? '';

            $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=utf8mb4', $driver, $host, $port, $dbName);

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            // Auto-migrate: aplica el schema en la primera conexión
            Migration::apply(self::$instance);
        }

        return self::$instance;
    }

    /**
     * Reinicia la conexión (cierra la existente y fuerza una nueva).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
