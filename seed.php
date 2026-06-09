<?php
/**
 * Seed runner — ejecuta la siembra inicial de datos.
 *
 * Uso:
 *   php seed.php
 */

require_once __DIR__ . '/dashboard-logic/bootstrap.php';
require_once __DIR__ . '/dashboard-logic/env-loader.php';

use Dashboard\Database\Connection;
use Dashboard\Database\Seed;

try {
    $pdo = Connection::get();
    Seed::run($pdo);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
