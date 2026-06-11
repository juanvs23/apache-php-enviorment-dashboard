<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\Project;

use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Domain\Entity\Project;

/**
 * Caso de uso: crear o actualizar un proyecto.
 *
 * Dos modos de operación:
 *   - create(): crea un proyecto nuevo sin asignación
 *   - update(): actualiza nombre, usuario asignado y flag de login
 *
 * Dependencias:
 *   - ProjectRepositoryInterface: para persistir el proyecto
 */
final class SaveProjectUseCase
{
    /**
     * @param ProjectRepositoryInterface $projectRepository Repositorio de proyectos
     */
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    /**
     * Crea un proyecto nuevo.
     *
     * @param string $projectId   UUID para el nuevo proyecto
     * @param string $projectName Nombre del proyecto
     * @return Project             El proyecto creado (sin asignación, login deshabilitado)
     * @throws \DomainException Si el nombre está vacío
     */
    public function create(
        string $projectId,
        string $projectName,
    ): Project {
        if ($projectName === '') {
            throw new \DomainException('Project name is required');
        }

        $project = Project::create($projectId, $projectName);
        $this->projectRepository->save($project);

        return $project;
    }

    /**
     * Actualiza un proyecto existente.
     *
     * @param string      $projectId   UUID del proyecto a actualizar
     * @param string      $projectName Nuevo nombre
     * @param string|null $userOwnId   UUID del usuario asignado (null para desasignar)
     * @param bool        $aceptLogin  Estado del flag de acceso directo
     * @return Project                  El proyecto actualizado
     * @throws \DomainException Si el proyecto no existe
     */
    public function update(
        string $projectId,
        string $projectName,
        ?string $userOwnId,
        bool $aceptLogin,
    ): Project {
        $project = $this->projectRepository->findById($projectId);
        if ($project === null) {
            throw new \DomainException('Project not found');
        }

        $project->rename($projectName);

        if ($userOwnId !== null) {
            $project->assignToUser($userOwnId);
        } else {
            $project->unassignUser();
        }

        if ($aceptLogin) {
            $project->enableLogin();
        } else {
            $project->disableLogin();
        }

        $this->projectRepository->save($project);

        return $project;
    }
}
