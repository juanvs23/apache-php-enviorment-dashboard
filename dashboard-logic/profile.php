<?php
/**
 * Perfil de usuario autenticado.
 *
 * Responsabilidad única: permitir al usuario actual editar sus propios
 * datos (email, nombre, contraseña). El nivel NO se puede cambiar desde acá.
 */

use Dashboard\Database\Connection;

function handle_profile_update(): array
{
    $user     = get_auth_user();
    if (!$user) {
        return ['success' => false, 'error' => 'No autenticado'];
    }

    $email    = trim($_POST['email'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '') {
        return ['success' => false, 'error' => 'El email es obligatorio'];
    }

    try {
        $pdo = Connection::get();

        // Verificar que el email no lo esté usando otro usuario
        $stmt = $pdo->prepare('SELECT userID FROM USERS WHERE email = :email AND userID != :uid LIMIT 1');
        $stmt->execute([':email' => $email, ':uid' => $user['userID']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Ese email ya está en uso por otro usuario'];
        }

        // Si se ingresó nueva contraseña, hashearla; si no, mantener la actual
        if ($password !== '') {
            $pass = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE USERS SET email = :email, name = :name, pass = :pass WHERE userID = :uid');
            $stmt->execute([
                ':email' => $email,
                ':name'  => $name ?: null,
                ':pass'  => $pass,
                ':uid'   => $user['userID'],
            ]);
        } else {
            $stmt = $pdo->prepare('UPDATE USERS SET email = :email, name = :name WHERE userID = :uid');
            $stmt->execute([
                ':email' => $email,
                ':name'  => $name ?: null,
                ':uid'   => $user['userID'],
            ]);
        }

        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error al actualizar: ' . $e->getMessage()];
    }
}

function process_profile_action(): ?array
{
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        return handle_profile_update();
    }
    return null;
}
