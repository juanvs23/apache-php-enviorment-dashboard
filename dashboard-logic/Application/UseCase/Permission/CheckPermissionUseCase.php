<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\Permission;

use Dashboard\Application\Repository\PermissionRepositoryInterface;

/**
 * Caso de uso: verificar si un usuario tiene un permiso específico.
 *
 * Delega en el repositorio que conoce la cadena completa:
 * usuario → nivel → level_permissions → permissions.
 * Si el nivel del usuario es admin (type=0), retorna true automáticamente
 * sin consultar la tabla de permisos.
 *
 * Dependencias:
 *   - PermissionRepositoryInterface: para verificar el permiso
 */
final class CheckPermissionUseCase
{
    /**
     * @param PermissionRepositoryInterface $permissionRepository Repositorio de permisos
     */
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    /**
     * Verifica si un usuario tiene un permiso específico.
     *
     * @param string $userId  UUID del usuario
     * @param string $permKey Clave del permiso (ej: "users.manage")
     * @return bool True si el usuario tiene el permiso (admin siempre true)
     */
    public function execute(string $userId, string $permKey): bool
    {
        return $this->permissionRepository->userHasPermission($userId, $permKey);
    }
}
