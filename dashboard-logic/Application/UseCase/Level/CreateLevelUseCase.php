<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\Level;

use Dashboard\Application\Repository\LevelRepositoryInterface;
use Dashboard\Application\Repository\PermissionRepositoryInterface;
use Dashboard\Domain\Entity\Level;
use Dashboard\Domain\ValueObject\LevelType;

/**
 * Caso de uso: crear un nuevo nivel de acceso con sus permisos.
 *
 * Crea un nivel (admin o client) y opcionalmente le asigna permisos.
 * La validación del tipo se delega en LevelType Value Object.
 *
 * Dependencias:
 *   - LevelRepositoryInterface: para persistir el nivel
 *   - PermissionRepositoryInterface: para asignar permisos iniciales
 */
final class CreateLevelUseCase
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
     * Ejecuta la creación del nivel.
     *
     * @param string $levelId        UUID para el nuevo nivel
     * @param string $name           Nombre del nivel
     * @param int    $type           Tipo numérico (0=admin, 1=client)
     * @param int[]  $permissionIds  IDs de permisos a asignar (opcional)
     * @return Level                  El nivel creado
     * @throws \DomainException Si el nombre está vacío o el type es inválido
     */
    public function execute(
        string $levelId,
        string $name,
        int $type,
        array $permissionIds = [],
    ): Level {
        if ($name === '') {
            throw new \DomainException('Level name is required');
        }

        $levelType = new LevelType($type);
        $level = new Level($levelId, $name, $levelType);

        $this->levelRepository->save($level);

        if (!empty($permissionIds)) {
            $this->permissionRepository->syncLevelPermissions($levelId, $permissionIds);
        }

        return $level;
    }
}
