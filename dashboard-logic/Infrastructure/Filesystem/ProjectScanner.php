<?php

declare(strict_types=1);

namespace Dashboard\Infrastructure\Filesystem;

/**
 * Escáner de proyectos en el sistema de archivos.
 *
 * Responsabilidad única: detectar proyectos gestionados por el dashboard
 * escaneando subdirectorios en busca del archivo `user-data.txt`.
 *
 * Cada proyecto se identifica por la presencia de user-data.txt en su directorio.
 * El archivo contiene metadatos en formato clave:valor (user, password, type).
 *
 * Uso típico:
 *   $scanner = new ProjectScanner('/var/www');
 *   $projects = $scanner->scan(); // array de arrays con metadatos
 */
final class ProjectScanner
{
    /**
     * Ruta raíz donde buscar proyectos.
     *
     * @var string
     */
    private string $rootPath;

    /**
     * Nombre del archivo de metadatos que identifica un proyecto.
     */
    private const DATA_FILE = 'user-data.txt';

    /**
     * @param string $rootPath Ruta absoluta al directorio raíz donde escanear proyectos
     */
    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/');
    }

    /**
     * Escanea el directorio raíz y retorna los proyectos encontrados.
     *
     * Cada proyecto es un array con:
     *   - dir:       nombre del directorio
     *   - slug:      nombre normalizado para URLs
     *   - name:      nombre legible
     *   - type:      tipo detectado (wordpress, laravel, static, etc.)
     *   - has_wp:    bool si tiene wp-config.php
     *   - user:      usuario de base de datos (desde user-data.txt)
     *   - password:  contraseña de base de datos (desde user-data.txt)
     *
     * @return array<int, array<string, mixed>> Array de proyectos
     */
    public function scan(): array
    {
        $items = array_diff(scandir($this->rootPath) ?: [], ['.', '..', 'assets', 'dashboard-logic']);
        $projects = [];

        foreach ($items as $entry) {
            $entryPath = $this->rootPath . '/' . $entry;

            if (!is_dir($entryPath)) {
                continue;
            }

            $dataFile = $entryPath . '/' . self::DATA_FILE;
            if (!file_exists($dataFile)) {
                continue;
            }

            $raw = file_get_contents($dataFile);
            if ($raw === false) {
                continue;
            }

            $projects[] = $this->parseProject($entry, explode("\n", $raw));
        }

        return $projects;
    }

    /**
     * Verifica si un directorio específico es un proyecto válido.
     *
     * @param string $directoryName Nombre del directorio a verificar
     * @return bool True si el directorio tiene user-data.txt
     */
    public function isProject(string $directoryName): bool
    {
        $dataFile = $this->rootPath . '/' . $directoryName . '/' . self::DATA_FILE;
        return file_exists($dataFile);
    }

    /**
     * Parsea las líneas de user-data.txt en un array estructurado.
     *
     * @param string   $dir   Nombre del directorio del proyecto
     * @param string[] $lines Líneas del archivo user-data.txt
     * @return array<string, mixed> Proyecto parseado
     */
    private function parseProject(string $dir, array $lines): array
    {
        $fields = ['user' => '', 'password' => '', 'type' => ''];

        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (!empty($parts[0]) && isset($parts[1])) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);
                if (isset($fields[$key])) {
                    $fields[$key] = $val;
                }
            }
        }

        $hasWp = file_exists($this->rootPath . '/' . $dir . '/wp-config.php');
        $hasNode = file_exists($this->rootPath . '/' . $dir . '/package.json');
        $hasPid  = file_exists($this->rootPath . '/' . $dir . '/.pid') && $this->isProcessRunning($this->rootPath . '/' . $dir . '/.pid');

        return [
            'dir'       => $dir,
            'slug'      => strtolower(str_replace(' ', '-', $dir)),
            'name'      => ucfirst(str_replace('-', ' ', $dir)),
            'type'      => $fields['type'],
            'has_wp'    => $hasWp,
            'has_node'  => $hasNode,
            'has_pid'   => $hasPid,
            'card_style'=> $hasWp ? 'border-start border-primary border-4' : '',
            'user'      => htmlspecialchars($fields['user']),
            'password'  => htmlspecialchars($fields['password']),
        ];
    }

    /**
     * Verifica si un proceso sigue corriendo a partir de un archivo .pid.
     */
    private function isProcessRunning(string $pidFile): bool
    {
        if (!file_exists($pidFile)) {
            return false;
        }
        $pid = (int) file_get_contents($pidFile);
        if ($pid <= 0) {
            return false;
        }
        return file_exists("/proc/{$pid}");
    }
}
