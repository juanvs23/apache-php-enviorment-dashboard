<?php

declare(strict_types=1);

/**
 * Bootstrap para tests unitarios y de integración.
 *
 * Carga el autoload PSR-4 manual (sin depender de Composer vendor/)
 * y carga las variables de entorno para la conexión a MySQL.
 */

// ─── Cargar .env manualmente ────────────────────────────────────────────────
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " '\t");
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// ─── Autoload PSR-4 manual ────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $prefix = 'Dashboard\\';
    $baseDir = __DIR__ . '/../';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
