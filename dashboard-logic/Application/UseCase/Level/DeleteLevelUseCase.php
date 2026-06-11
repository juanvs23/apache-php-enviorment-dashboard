<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\Level;

use Dashboard\Application\Repository\LevelRepositoryInterface;
use Dashboard\Application\Repository\UserRepositoryInterface;

/**
 * Caso de uso: eliminar un nivel del sistema.
 *
 * Reglas de protección:
 *   1. El nivel admin NO se puede eliminar
 *   2. Un nivel con usuarios asignados NO se puede eliminar
 *
 * Dependencias:
 *   - LevelRepositoryInterface: para buscar y eliminar
 *   - UserRepositoryInterface: para verificar usuarios asignados al nivel
 */
final class DeleteLevelUseCase
{
    /**
     * @param LevelRepositoryInterface  $levelRepository  Repositorio de niveles
     * @param UserRepositoryInterface   $userRepository   Repositorio de usuarios
     */
    public function __construct(
        private readonly LevelRepositoryInterface $levelRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Ejecuta la eliminación del nivel.
     *
     * @param string $levelId UUID del nivel a eliminar
     * @throws \DomainException Si el nivel no existe, es admin, o tiene usuarios asignados
     */
    public function execute(string $levelId): void
    {
        $level = $this->levelRepository->findById($levelId);
        if ($level === null) {
            throw new \DomainException('Level not found');
        }

        if ($level->isAdmin()) {
            throw new \DomainException('The admin level cannot be deleted');
        }

        $usersInLevel = $this->userRepository->findByLevel($levelId);
        if (count($usersInLevel) > 0) {
            throw new \DomainException(
                sprintf('Cannot delete level: %d user(s) are assigned to it', count($usersInLevel))
            );
        }

        $this->levelRepository->delete($levelId);
    }
}
