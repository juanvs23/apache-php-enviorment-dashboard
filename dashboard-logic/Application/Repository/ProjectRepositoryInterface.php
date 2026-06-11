<?php

declare(strict_types=1);

namespace Dashboard\Application\Repository;

use Dashboard\Domain\Entity\Project;

/**
 * Puerto de repositorio para la entidad Project.
 *
 * Define el contrato para persistir y recuperar proyectos.
 * Incluye filtrado por usuario asignado y verificación de unicidad de nombre.
 *
 * Métodos disponibles:
 *   findById()     → Buscar por UUID
 *   findAll()      → Listar todos
 *   findByUser()   → Listar proyectos de un usuario
 *   save()         → Crear o actualizar
 *   delete()       → Eliminar
 *   nameExists()   → Verificar unicidad de nombre
 */
interface ProjectRepositoryInterface
{
    /**
     * Busca un proyecto por su UUID.
     *
     * @param string $projectId UUID del proyecto
     * @return Project|null El proyecto si existe, null si no
     */
    public function findById(string $projectId): ?Project;

    /**
     * Retorna todos los proyectos del sistema.
     *
     * @return Project[] Array de proyectos
     */
    public function findAll(): array;

    /**
     * Retorna los proyectos asignados a un usuario específico.
     *
     * @param string $userId UUID del usuario
     * @return Project[] Array de proyectos del usuario
     */
    public function findByUser(string $userId): array;

    /**
     * Persiste un proyecto (crea o actualiza según exista el UUID).
     *
     * @param Project $project El proyecto a guardar
     */
    public function save(Project $project): void;

    /**
     * Elimina un proyecto por su UUID.
     *
     * @param string $projectId UUID del proyecto a eliminar
     */
    public function delete(string $projectId): void;

    /**
     * Verifica si un nombre de proyecto ya existe en la base de datos.
     *
     * @param string $projectName Nombre a verificar
     * @return bool True si el nombre ya existe
     */
    public function nameExists(string $projectName): bool;

    /**
     * Desasigna todos los proyectos vinculados a un usuario.
     *
     * @param string $userId UUID del usuario
     */
    public function unassignProjectsByUserId(string $userId): void;
}
