<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\Level;

use Dashboard\Application\Repository\LevelRepositoryInterface;
use Dashboard\Application\Repository\PermissionRepositoryInterface;
use Dashboard\Domain\Entity\Level;

/**
 * Caso de uso: actualizar un nivel existente.
 *
 * Modifica el nombre y los permisos asignados a un nivel.
 * El nivel admin (level_type=0) está protegido: no se permite modificarlo.
 * Los permisos se reemplazan completamente (sync).
 *
 * Dependencias:
 *   - LevelRepositoryInterface: para buscar, verificar y persistir
 *   - PermissionRepositoryInterface: para sincronizar permisos
 */
final class UpdateLevelUseCase
{
    /**
     * @param LevelRepositoryInterface       $levelRepository       Repositorio de niveles
     * @param PermissionRepositoryInterface  $permissionRepository  Repositorio de permisos
     */
    public function __construct(
        private readonly LevelRepositoryInterface $levelRepository,
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    /**
     * Ejecuta la actualización del nivel.
     *
     * @param string $levelId        UUID del nivel a actualizar
     * @param string $newName        Nuevo nombre del nivel
     * @param int[]  $permissionIds  IDs de permisos a asignar (reemplaza los existentes)
     * @return Level                  El nivel actualizado
     * @throws \DomainException Si el nivel no existe, es admin, o el nombre está vacío
     */
    public function execute(
        string $levelId,
        string $newName,
        array $permissionIds = [],
    ): Level {
        $level = $this->levelRepository->findById($levelId);
        if ($level === null) {
            throw new \DomainException('Level not found');
        }

        if ($level->isAdmin()) {
            throw new \DomainException('The admin level cannot be modified');
        }

        if ($newName === '') {
            throw new \DomainException('Level name is required');
        }

        $level->rename($newName);
        $this->levelRepository->save($level);
        $this->permissionRepository->syncLevelPermissions($levelId, $permissionIds);

        return $level;
    }
}
