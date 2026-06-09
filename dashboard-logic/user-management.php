<?php
/**
 * Gestión de usuarios y asignación de proyectos.
 *
 * Responsabilidad única: CRUD de usuarios en USERS y
 * asociación usuario-proyecto en Project.
 */

use Dashboard\Database\Connection;

// ─── CRUD Usuarios ─────────────────────────────────────────────────────

function handle_user_create(): array {
    $email    = trim($_POST['email'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $level    = trim($_POST['level'] ?? '');

    if ($email === '' || $pass === '' || $level === '') {
        return ['success' => false, 'error' => 'Email, contraseña y nivel son requeridos'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Email inválido'];
    }

    try {
        $pdo  = Connection::get();

        // Verificar email único
        $check = $pdo->prepare('SELECT 1 FROM USERS WHERE email = :email LIMIT 1');
        $check->execute([':email' => $email]);
        if ($check->fetchColumn()) {
            return ['success' => false, 'error' => 'El email ya está registrado'];
        }

        // Verificar que el nivel existe
        $lv = $pdo->prepare('SELECT 1 FROM levels WHERE levelsID = :id LIMIT 1');
        $lv->execute([':id' => $level]);
        if (!$lv->fetchColumn()) {
            return ['success' => false, 'error' => 'Nivel inválido'];
        }

        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        $hash = password_hash($pass, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare('INSERT INTO USERS (userID, email, name, pass, level) VALUES (:id, :email, :name, :pass, :level)');
        $stmt->execute([
            ':id'    => $uuid,
            ':email' => $email,
            ':name'  => $name ?: null,
            ':pass'  => $hash,
            ':level' => $level,
        ]);

        return ['success' => true, 'userID' => $uuid];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error al crear usuario: ' . $e->getMessage()];
    }
}

function handle_user_update(): array {
    $userID = trim($_POST['userID'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $name   = trim($_POST['name'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $level  = trim($_POST['level'] ?? '');

    if ($userID === '' || $email === '' || $level === '') {
        return ['success' => false, 'error' => 'Faltan campos requeridos'];
    }

    try {
        $pdo = Connection::get();

        // Verificar email único (excluyendo este usuario)
        $check = $pdo->prepare('SELECT 1 FROM USERS WHERE email = :email AND userID != :id LIMIT 1');
        $check->execute([':email' => $email, ':id' => $userID]);
        if ($check->fetchColumn()) {
            return ['success' => false, 'error' => 'El email ya está en uso por otro usuario'];
        }

        if ($pass !== '') {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE USERS SET email = :email, name = :name, pass = :pass, level = :level WHERE userID = :id');
            $stmt->execute([
                ':email' => $email,
                ':name'  => $name ?: null,
                ':pass'  => $hash,
                ':level' => $level,
                ':id'    => $userID,
            ]);
        } else {
            $stmt = $pdo->prepare('UPDATE USERS SET email = :email, name = :name, level = :level WHERE userID = :id');
            $stmt->execute([
                ':email' => $email,
                ':name'  => $name ?: null,
                ':level' => $level,
                ':id'    => $userID,
            ]);
        }

        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error al actualizar: ' . $e->getMessage()];
    }
}

function handle_user_delete(): array {
    $userID = trim($_POST['userID'] ?? '');

    if ($userID === '') {
        return ['success' => false, 'error' => 'ID de usuario requerido'];
    }

    try {
        $pdo = Connection::get();

        // Desvincular proyectos asociados
        $pdo->prepare('UPDATE Project SET user_own = NULL WHERE user_own = :id')
            ->execute([':id' => $userID]);

        $pdo->prepare('DELETE FROM USERS WHERE userID = :id')
            ->execute([':id' => $userID]);

        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()];
    }
}

// ─── Asociar proyectos ─────────────────────────────────────────────────

function handle_project_assign(): array {
    $projectID = trim($_POST['projectID'] ?? '');
    $userID    = trim($_POST['userID'] ?? '');
    $acept     = (int) ($_POST['acept_login'] ?? 0);

    if ($projectID === '') {
        return ['success' => false, 'error' => 'ID de proyecto requerido'];
    }

    try {
        $pdo = Connection::get();
        $stmt = $pdo->prepare('UPDATE Project SET user_own = :user, acept_login = :acept WHERE id = :id');
        $stmt->execute([
            ':user'  => $userID ?: null,
            ':acept' => $acept,
            ':id'    => $projectID,
        ]);
        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error al asignar: ' . $e->getMessage()];
    }
}

// ─── Listados ──────────────────────────────────────────────────────────

function get_all_users(): array {
    try {
        $pdo = Connection::get();
        $stmt = $pdo->query('
            SELECT u.userID, u.email, u.name, u.level, l.level_name, l.level_type
            FROM USERS u
            JOIN levels l ON l.levelsID = u.level
            ORDER BY l.level_type, u.email
        ');
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function get_all_levels(): array {
    try {
        $pdo = Connection::get();
        return $pdo->query('SELECT levelsID, level_name, level_type FROM levels ORDER BY level_type')->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function get_client_users(): array {
    try {
        $pdo = Connection::get();
        $level = $pdo->query("SELECT levelsID FROM levels WHERE level_type = 1 LIMIT 1")->fetchColumn();
        if (!$level) return [];
        $stmt = $pdo->prepare('SELECT userID, email, name FROM USERS WHERE level = :level ORDER BY email');
        $stmt->execute([':level' => $level]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function handle_project_create(): array {
    $name  = trim($_POST['project_name'] ?? '');
    $userID = trim($_POST['userID'] ?? '');
    $acept = (int) ($_POST['acept_login'] ?? 0);

    if ($name === '') {
        return ['success' => false, 'error' => 'Nombre del proyecto requerido'];
    }

    try {
        $pdo  = Connection::get();
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        $stmt = $pdo->prepare('INSERT INTO Project (id, project_name, user_own, acept_login) VALUES (:id, :name, :user, :acept)');
        $stmt->execute([
            ':id'    => $uuid,
            ':name'  => $name,
            ':user'  => $userID ?: null,
            ':acept' => $acept,
        ]);
        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error al crear proyecto: ' . $e->getMessage()];
    }
}

function handle_project_delete(): array {
    $projectID = trim($_POST['projectID'] ?? '');
    if ($projectID === '') {
        return ['success' => false, 'error' => 'ID de proyecto requerido'];
    }
    try {
        $pdo  = Connection::get();
        $stmt = $pdo->prepare('DELETE FROM Project WHERE id = :id');
        $stmt->execute([':id' => $projectID]);
        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()];
    }
}

function handle_project_update(): array {
    $projectID = trim($_POST['projectID'] ?? '');
    $name      = trim($_POST['project_name'] ?? '');
    $userID    = trim($_POST['userID'] ?? '');
    $acept     = (int) ($_POST['acept_login'] ?? 0);

    if ($projectID === '' || $name === '') {
        return ['success' => false, 'error' => 'ID y nombre del proyecto requeridos'];
    }

    try {
        $pdo  = Connection::get();
        $stmt = $pdo->prepare('UPDATE Project SET project_name = :name, user_own = :user, acept_login = :acept WHERE id = :id');
        $stmt->execute([
            ':name'  => $name,
            ':user'  => $userID ?: null,
            ':acept' => $acept,
            ':id'    => $projectID,
        ]);
        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Error al actualizar: ' . $e->getMessage()];
    }
}

function get_all_projects(): array {
    try {
        $pdo = Connection::get();
        $stmt = $pdo->query('
            SELECT p.id, p.project_name, p.user_own, p.acept_login, u.email AS user_email, u.name AS user_name
            FROM Project p
            LEFT JOIN USERS u ON u.userID = p.user_own
            ORDER BY p.project_name
        ');
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

// ─── Procesar acciones ─────────────────────────────────────────────────

function process_user_action(): ?array {
    $action = $_POST['action'] ?? '';
    return match ($action) {
        'create_user' => handle_user_create(),
        'update_user' => handle_user_update(),
        'delete_user' => handle_user_delete(),
        'assign_project' => handle_project_assign(),
        'create_project' => handle_project_create(),
        'delete_project' => handle_project_delete(),
        'update_project' => handle_project_update(),
        default => null,
    };
}
