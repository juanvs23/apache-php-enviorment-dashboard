<?php

declare(strict_types=1);

namespace Dashboard\Application\Repository;

use Dashboard\Domain\Entity\Level;

/**
 * Puerto de repositorio para la entidad Level.
 *
 * Define el contrato para persistir y recuperar niveles de acceso.
 * Incluye métodos para listar niveles con sus permisos asociados
 * y verificar unicidad de nombre.
 *
 * Métodos disponibles:
 *   findById()                → Buscar por UUID
 *   findAll()                 → Listar todos
 *   findAllWithPermissions()  → Listar con permisos incluidos
 *   save()                    → Crear o actualizar
 *   delete()                  → Eliminar
 *   nameExists()              → Verificar unicidad de nombre
 */
interface LevelRepositoryInterface
{
    /**
     * Busca un nivel por su UUID.
     *
     * @param string $levelId UUID del nivel
     * @return Level|null El nivel si existe, null si no
     */
    public function findById(string $levelId): ?Level;

    /**
     * Retorna todos los niveles del sistema.
     *
     * @return Level[] Array de niveles
     */
    public function findAll(): array;

    /**
     * Retorna todos los niveles con los permisos asociados a cada uno.
     *
     * @return Level[] Array de niveles (los permisos se cargan por separado vía PermissionRepository)
     */
    public function findAllWithPermissions(): array;

    /**
     * Persiste un nivel (crea o actualiza según exista el UUID).
     *
     * @param Level $level El nivel a guardar
     */
    public function save(Level $level): void;

    /**
     * Elimina un nivel por su UUID.
     *
     * @param string $levelId UUID del nivel a eliminar
     */
    public function delete(string $levelId): void;

    /**
     * Verifica si un nombre de nivel ya existe.
     *
     * @param string $name Nombre a verificar
     * @return bool True si el nombre ya está en uso
     */
    public function nameExists(string $name): bool;
}
