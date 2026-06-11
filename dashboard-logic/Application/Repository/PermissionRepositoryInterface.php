<?php

declare(strict_types=1);

namespace Dashboard\Application\Repository;

use Dashboard\Domain\Entity\Permission;

/**
 * Puerto de repositorio para permisos y asignación nivel→permisos.
 *
 * Define el contrato para gestionar el catálogo de permisos y las
 * relaciones entre niveles y permisos (tablas permissions + level_permissions).
 *
 * Métodos disponibles:
 *   findAll()               → Listar catálogo completo de permisos
 *   findKeysByLevel()       → Obtener claves de permiso de un nivel
 *   findByLevel()           → Obtener objetos Permission de un nivel
 *   syncLevelPermissions()  → Reemplazar permisos de un nivel
 *   userHasPermission()     → Verificar permiso de un usuario
 */
interface PermissionRepositoryInterface
{
    /**
     * Retorna todos los permisos del catálogo.
     *
     * @return Permission[] Array de permisos
     */
    public function findAll(): array;

    /**
     * Retorna las claves (perm_key) de los permisos asignados a un nivel.
     *
     * @param string $levelId UUID del nivel
     * @return string[] Array de claves (ej: ["users.manage", "server.view"])
     */
    public function findKeysByLevel(string $levelId): array;

    /**
     * Retorna los objetos Permission asignados a un nivel.
     *
     * @param string $levelId UUID del nivel
     * @return Permission[] Array de permisos del nivel
     */
    public function findByLevel(string $levelId): array;

    /**
     * Reemplaza todos los permisos asignados a un nivel por los especificados.
     *
     * Elimina las asignaciones existentes e inserta las nuevas.
     * Operación atómica desde la perspectiva del llamador.
     *
     * @param string $levelId        UUID del nivel
     * @param int[]  $permissionIds  IDs de permisos a asignar
     */
    public function syncLevelPermissions(string $levelId, array $permissionIds): void;

    /**
     * Verifica si un usuario tiene un permiso específico.
     *
     * Recorre la cadena: usuario → nivel → level_permissions → permissions.
     * Si el nivel del usuario es admin (type=0), retorna true automáticamente.
     *
     * @param string $userId  UUID del usuario
     * @param string $permKey Clave del permiso (ej: "users.manage")
     * @return bool True si el usuario tiene el permiso
     */
    public function userHasPermission(string $userId, string $permKey): bool;
}
