<?php

declare(strict_types=1);

/**
 * fix-admin.php — CLI tool to ensure the admin user exists.
 *
 * Behaviour:
 *   1. If user with email admin@admin.com or admin@admin exists → UPDATE
 *      email to admin@admin.com, set password to Admin123, assign admin level.
 *   2. If no such user exists → CREATE a new admin user.
 *
 * Usage:  php fix-admin.php
 * Safe:   idempotent — running it twice only updates the password.
 *
 * ⚠️ ARCHITECTURE NOTE: This tool uses PDO directly. This is INTENTIONAL —
 * fix-admin.php is an emergency maintenance script that must work even when
 * the application (Use Cases, ServiceContainer, AuthContext) is broken or
 * misconfigured. It bootstraps only the autoloader and PDO connection.
 */

// ─── Bootstrap (minimal — no session for CLI) ──────────────────────────
require_once __DIR__ . '/dashboard-logic/env-loader.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'Dashboard\\';
    $baseDir = __DIR__ . '/dashboard-logic/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// ─── Resolve PDO (auto-migrates if needed) ────────────────────────────
$pdo = Dashboard\Database\Connection::get();

// ─── Find (or create) admin level ─────────────────────────────────────
$adminLevel = $pdo->query(
    "SELECT levelsID FROM levels WHERE level_name = 'admin' AND level_type = 0 LIMIT 1"
)->fetchColumn();

if (!$adminLevel) {
    // Create admin level if it doesn't exist (extremely unlikely)
    $adminLevel = sprintf(
        '%s-%s-%s-%s-%s',
        substr(bin2hex(random_bytes(4)), 0, 8),
        substr(bin2hex(random_bytes(2)), 0, 4),
        substr(bin2hex(random_bytes(2)), 0, 4),
        substr(bin2hex(random_bytes(2)), 0, 4),
        substr(bin2hex(random_bytes(6)), 0, 12)
    );
    $pdo->prepare('INSERT INTO levels (levelsID, level_name, level_type) VALUES (?, ?, ?)')
        ->execute([$adminLevel, 'admin', 0]);
    echo "ℹ️  Admin level created.\n";
}

// ─── Look for existing admin user ─────────────────────────────────────
$existing = $pdo->prepare(
    'SELECT userID, email, name FROM USERS WHERE email IN (?, ?) LIMIT 1'
);
$existing->execute(['admin@admin.com', 'admin@admin']);
$row = $existing->fetch();

$newHash = password_hash('Admin123', PASSWORD_BCRYPT);

if ($row) {
    // ── UPDATE existing user ───────────────────────────────────────────
    $pdo->prepare(
        'UPDATE USERS SET email = :email, pass = :pass, level = :level WHERE userID = :id'
    )->execute([
        ':email' => 'admin@admin.com',
        ':pass'  => $newHash,
        ':level' => $adminLevel,
        ':id'    => $row['userID'],
    ]);

    echo "✅ Admin user UPDATED:\n";
    echo "   Email:    admin@admin.com  (was: {$row['email']})\n";
    echo "   Name:     {$row['name']}\n";
    echo "   Password: ********  (reset to default)\n";
} else {
    // ── CREATE new admin user ──────────────────────────────────────────
    $userId = sprintf(
        '%s-%s-%s-%s-%s',
        substr(bin2hex(random_bytes(4)), 0, 8),
        substr(bin2hex(random_bytes(2)), 0, 4),
        substr(bin2hex(random_bytes(2)), 0, 4),
        substr(bin2hex(random_bytes(2)), 0, 4),
        substr(bin2hex(random_bytes(6)), 0, 12)
    );

    $pdo->prepare(
        'INSERT INTO USERS (userID, email, name, pass, level) VALUES (?, ?, ?, ?, ?)'
    )->execute([$userId, 'admin@admin.com', 'Admin', $newHash, $adminLevel]);

    echo "✅ Admin user CREATED:\n";
    echo "   Email:    admin@admin.com\n";
    echo "   Password: ********  (default password set)\n";
}

echo "   Level:    admin (type 0, full permissions)\n";
echo "\nYou can now log in at http://localhost/\n";
