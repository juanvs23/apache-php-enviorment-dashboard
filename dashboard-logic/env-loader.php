<?php
/**
 * Carga variables de entorno desde .env
 * Uso: require_once __DIR__ . '/env-loader.php';
 * Luego accedé via $_ENV['DASHBOARD_KEY'], getenv('DASHBOARD_KEY'), etc.
 */
$env_file = dirname(__DIR__) . '/.env';

if (!file_exists($env_file)) {
    return;
}

$lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $line = trim($line);

    // Saltar comentarios
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    if (!str_contains($line, '=')) {
        continue;
    }

    [$key, $value] = explode('=', $line, 2);
    $key   = trim($key);
    $value = trim($value);

    // Sacar comillas simples o dobles si envuelven el valor
    if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
        || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
        $value = substr($value, 1, -1);
    }

    $_ENV[$key] = $value;
    putenv("$key=$value");
}
