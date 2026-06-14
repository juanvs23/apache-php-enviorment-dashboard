<?php
/**
 * API endpoints para el dashboard.
 * /api.php?action=users_search&q=term
 * /api.php?action=project_users&id=uuid
 * /api.php?action=project_users_update&id=uuid  (POST)
 */
declare(strict_types=1);
require_once __DIR__ . '/dashboard-logic/bootstrap.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$pdo = \Dashboard\Database\Connection::get();

try {
    if ($action === 'users_search') {
        $q = trim($_GET['q'] ?? '');
        $stmt = $pdo->prepare("SELECT userID, email, name FROM USERS WHERE email LIKE ? OR name LIKE ? ORDER BY email LIMIT 20");
        $like = "%{$q}%";
        $stmt->execute([$like, $like]);
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[] = ['id' => $row['userID'], 'text' => $row['name'] ? "{$row['name']} ({$row['email']})" : $row['email']];
        }
        echo json_encode(['results' => $results]);
    } elseif ($action === 'project_users') {
        $id = trim($_GET['id'] ?? '');
        $stmt = $pdo->prepare('SELECT user_own FROM Project WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $users = $row ? json_decode($row['user_own'], true) : [];
        echo json_encode(array_map(fn($u) => ['id' => $u['userID'], 'logeable' => $u['is_logeable'] ?? false, 'user_name' => $u['user_name'] ?? ''], $users ?: []));
    } elseif ($action === 'project_users_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = trim($_GET['id'] ?? '');
        $input = json_decode(file_get_contents('php://input'), true);
        $userIds = $input['user_ids'] ?? [];
        $logeable = $input['logeable'] ?? [];
        $project = \Dashboard\Presentation\ServiceContainer::get(\Dashboard\Application\Repository\ProjectRepositoryInterface::class)->findById($id);
        if ($project) {
            $project->unassignUser();
            foreach ($userIds as $i => $uid) {
                $isLog = isset($logeable[$i]) && $logeable[$i];
                // Obtener nombre del usuario
                $stmt = $pdo->prepare('SELECT name, email FROM USERS WHERE userID = ?');
                $stmt->execute([$uid]);
                $u = $stmt->fetch();
                $userName = $u ? ($u['name'] ?: $u['email']) : $uid;
                $project->addUser($uid, $isLog, $userName);
            }
            \Dashboard\Presentation\ServiceContainer::get(\Dashboard\Application\Repository\ProjectRepositoryInterface::class)->save($project);
        }
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
