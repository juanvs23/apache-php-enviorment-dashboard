<?php

declare(strict_types=1);

namespace Dashboard\Infrastructure\System;

/**
 * Detector de estado de servicios del servidor.
 *
 * Centraliza la lógica de detección de PostgreSQL, MySQL, pgAdmin4 y phpMyAdmin
 * que antes estaba duplicada en server-info.php y el endpoint /service_status.
 *
 * Uso:
 *   $detector = new ServiceDetector();
 *   $services = $detector->all();  // ['postgresql' => true, 'mysql' => true, ...]
 */
final class ServiceDetector
{
    /**
     * Retorna el estado de todos los servicios.
     *
     * @return array{postgresql: bool, mysql: bool, pgadmin4: bool, phpmyadmin: bool, apache: bool}
     */
    public function all(): array
    {
        return [
            'postgresql' => $this->isPostgreSQLAlive(),
            'mysql'      => $this->isMySQLAlive(),
            'pgadmin4'   => $this->isPgAdmin4Available(),
            'phpmyadmin' => $this->isPhpMyAdminAvailable(),
            'apache'     => true, // Si llegamos hasta acá, Apache está corriendo
        ];
    }

    public function isPostgreSQLAlive(): bool
    {
        exec('pg_isready -q 2>/dev/null', $_, $exit);
        return $exit === 0;
    }

    public function isMySQLAlive(): bool
    {
        exec('pgrep mysqld 2>/dev/null', $_, $exit);
        return $exit === 0;
    }

    public function isPgAdmin4Available(): bool
    {
        $conf = file_exists('/etc/apache2/conf-enabled/pgadmin4.conf')
             || file_exists('/etc/apache2/conf-available/pgadmin4.conf');
        if (!$conf) {
            return false;
        }
        $ctx = stream_context_create(['http' => ['timeout' => 2, 'method' => 'HEAD']]);
        $headers = @get_headers('http://localhost/pgadmin4/', true, $ctx);
        return $headers && isset($headers[0]) && str_contains($headers[0], '302');
    }

    public function isPhpMyAdminAvailable(): bool
    {
        $pmaEnv = $_ENV['PMA_URL'] ?? '';
        if ($pmaEnv !== '') {
            return true;
        }
        $conf = file_exists('/etc/phpmyadmin/apache.conf')
             || file_exists('/etc/apache2/conf-enabled/phpmyadmin.conf')
             || file_exists('/etc/apache2/conf-available/phpmyadmin.conf');
        if (!$conf) {
            return false;
        }
        $ctx = stream_context_create(['http' => ['timeout' => 2, 'method' => 'HEAD']]);
        $headers = @get_headers('http://localhost/phpmyadmin/', true, $ctx);
        return $headers && isset($headers[0])
            && (str_contains($headers[0], '200') || str_contains($headers[0], '302'));
    }
}
