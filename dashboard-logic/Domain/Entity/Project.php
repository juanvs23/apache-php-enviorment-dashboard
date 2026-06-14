<?php

declare(strict_types=1);

namespace Dashboard\Domain\Entity;

/**
 * Proyecto alojado en el servidor.
 *
 * Soporta asignación multi-usuario vía JSON en user_own:
 *   [{"userID": "uuid", "is_logeable": true}, ...]
 *
 * @property-read string $projectId   UUID del proyecto
 * @property-read string $projectName Nombre visible del proyecto
 */
final class Project
{
    /** @var list<array{userID: string, user_name: string, is_logeable: bool}> */
    private array $assignedUsers;

    /**
     * @param string                            $projectId     UUID único del proyecto
     * @param string                            $projectName   Nombre del proyecto
     * @param string|null                       $userOwnRaw    JSON con usuarios asignados
     * @param bool                              $aceptLogin    DEPRECATED — mantenido por compatibilidad
     */
    public function __construct(
        private readonly string $projectId,
        private string $projectName,
        ?string $userOwnRaw = null,
    ) {
        if ($userOwnRaw === null || $userOwnRaw === '') {
            $this->assignedUsers = [];
        } else {
            $decoded = json_decode($userOwnRaw, true);
            if (is_array($decoded)) {
                $this->assignedUsers = $decoded;
            } else {
                $this->assignedUsers = [['userID' => $userOwnRaw, 'user_name' => $userOwnRaw, 'is_logeable' => false]];
            }
        }
    }

    public static function create(string $projectId, string $projectName): self
    {
        return new self($projectId, $projectName);
    }

    public function projectId(): string
    {
        return $this->projectId;
    }

    public function projectName(): string
    {
        return $this->projectName;
    }

    // ─── Multi-usuario ────────────────────────────────────────────

    /**
     * @return list<array{userID: string, is_logeable: bool}>
     */
    public function getUsers(): array
    {
        return $this->assignedUsers;
    }

    public function addUser(string $userId, bool $isLogeable = false, string $userName = ''): void
    {
        $this->removeUser($userId);
        $this->assignedUsers[] = ['userID' => $userId, 'user_name' => $userName, 'is_logeable' => $isLogeable];
    }

    public function removeUser(string $userId): void
    {
        $this->assignedUsers = array_values(array_filter(
            $this->assignedUsers,
            fn(array $u) => $u['userID'] !== $userId,
        ));
    }

    public function hasUser(string $userId): bool
    {
        foreach ($this->assignedUsers as $u) {
            if ($u['userID'] === $userId) return true;
        }
        return false;
    }

    public function isLogeableForUser(string $userId): bool
    {
        foreach ($this->assignedUsers as $u) {
            if ($u['userID'] === $userId) return $u['is_logeable'];
        }
        return false;
    }

    /**
     * Retorna el JSON para persistir en user_own.
     */
    public function userOwnJson(): ?string
    {
        return $this->assignedUsers ? json_encode($this->assignedUsers) : null;
    }

    // ─── Backward compat (DEPRECATED) ────────────────────────────

    /** @deprecated Usar getUsers() */
    public function userOwnId(): ?string
    {
        return $this->assignedUsers ? $this->assignedUsers[0]['userID'] : null;
    }

    /** @deprecated Usar addUser() */
    public function assignToUser(string $userId): void
    {
        $this->assignedUsers = [['userID' => $userId, 'user_name' => $userId, 'is_logeable' => false]];
    }

    /** @deprecated Usar removeUser() */
    public function unassignUser(): void
    {
        $this->assignedUsers = [];
    }

    /** @deprecated Sin efecto — el login es por usuario en user_own */
    public function enableLogin(): void {}

    /** @deprecated Sin efecto — el login es por usuario en user_own */
    public function disableLogin(): void {}

    /** @deprecated Usar isLogeableForUser() — retorna si algún usuario tiene login */
    public function aceptLogin(): bool
    {
        foreach ($this->assignedUsers as $u) {
            if ($u['is_logeable']) return true;
        }
        return false;
    }

    public function rename(string $newName): void
    {
        $this->projectName = $newName;
    }
}
