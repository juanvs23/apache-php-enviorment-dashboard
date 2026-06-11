<?php

declare(strict_types=1);

namespace Dashboard\Infrastructure\Persistence;

use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Domain\Entity\User;
use Dashboard\Domain\ValueObject\Email;
use PDO;

/**
 * Repositorio de usuarios implementado con MySQL (vía PDO).
 *
 * Implementa UserRepositoryInterface usando la tabla `USERS` del schema actual.
 * Mapea las filas de la base de datos a entidades User del dominio.
 *
 * Dependencias:
 *   - PDO: conexión a MySQL obtenida via Dashboard\Database\Connection::get()
 *
 * @see UserRepositoryInterface
 */
final class MySQLUserRepository implements UserRepositoryInterface
{
    /**
     * Conexión PDO compartida.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * @param PDO $pdo Conexión PDO a MySQL
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $userId): ?User
    {
        $stmt = $this->pdo->prepare('
            SELECT u.userID, u.email, u.name, u.pass, u.level
            FROM USERS u
            WHERE u.userID = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToUser($row) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('
            SELECT u.userID, u.email, u.name, u.pass, u.level
            FROM USERS u
            WHERE u.email = :email
            LIMIT 1
        ');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        return $row ? $this->mapRowToUser($row) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(): array
    {
        $rows = $this->pdo->query('
            SELECT u.userID, u.email, u.name, u.pass, u.level
            FROM USERS u
            ORDER BY u.email
        ')->fetchAll();

        return array_map([$this, 'mapRowToUser'], $rows);
    }

    /**
     * {@inheritDoc}
     */
    public function findByLevel(string $levelId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT u.userID, u.email, u.name, u.pass, u.level
            FROM USERS u
            WHERE u.level = :levelId
            ORDER BY u.email
        ');
        $stmt->execute([':levelId' => $levelId]);
        $rows = $stmt->fetchAll();

        return array_map([$this, 'mapRowToUser'], $rows);
    }

    /**
     * {@inheritDoc}
     */
    public function save(User $user): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO USERS (userID, email, name, pass, level)
            VALUES (:id, :email, :name, :pass, :level)
            ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                name  = VALUES(name),
                pass  = VALUES(pass),
                level = VALUES(level)
        ');
        $stmt->execute([
            ':id'    => $user->userId(),
            ':email' => $user->email()->value(),
            ':name'  => $user->name(),
            ':pass'  => $user->passwordHash(),
            ':level' => $user->levelId(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM USERS WHERE userID = :id');
        $stmt->execute([':id' => $userId]);
    }

    /**
     * {@inheritDoc}
     */
    public function emailExists(string $email, ?string $excludeUserId = null): bool
    {
        if ($excludeUserId !== null) {
            $stmt = $this->pdo->prepare('SELECT 1 FROM USERS WHERE email = :email AND userID != :exclude LIMIT 1');
            $stmt->execute([':email' => $email, ':exclude' => $excludeUserId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT 1 FROM USERS WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
        }

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Mapea una fila de la base de datos a una entidad User.
     *
     * @param array<string, mixed> $row Fila de la tabla USERS
     * @return User                     Entidad User mapeada
     */
    private function mapRowToUser(array $row): User
    {
        return new User(
            $row['userID'],
            new Email($row['email']),
            $row['name'] ?: null,
            $row['pass'],
            $row['level'],
        );
    }
}
