<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\Project;

use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Domain\Entity\Project;

/**
 * Caso de uso: asignar o desasignar un proyecto a un usuario.
 *
 * Permite:
 *   - assign():   asignar un proyecto a un usuario con control de flag de login
 *   - unassign(): desasignar el proyecto (limpia userOwnId y deshabilita login)
 *
 * Dependencias:
 *   - ProjectRepositoryInterface: para buscar y persistir el proyecto
 */
final class AssignProjectUseCase
{
    /**
     * @param ProjectRepositoryInterface $projectRepository Repositorio de proyectos
     */
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    /**
     * Asigna un proyecto a un usuario y configura el flag de acceso.
     *
     * @param string $projectId  UUID del proyecto
     * @param string $userId     UUID del usuario a asignar
     * @param bool   $aceptLogin Si los botones de acceso están habilitados
     * @return Project            El proyecto actualizado
     * @throws \DomainException Si el proyecto no existe
     */
    public function assign(string $projectId, string $userId, bool $aceptLogin): Project
    {
        $project = $this->projectRepository->findById($projectId);
        if ($project === null) {
            throw new \DomainException('Project not found');
        }

        $project->addUser($userId, $aceptLogin);

        $this->projectRepository->save($project);

        return $project;
    }

    /**
     * Desasigna un proyecto (limpia usuario y deshabilita login).
     *
     * @param string $projectId UUID del proyecto
     * @return Project           El proyecto actualizado
     * @throws \DomainException Si el proyecto no existe
     */
    public function unassign(string $projectId): Project
    {
        $project = $this->projectRepository->findById($projectId);
        if ($project === null) {
            throw new \DomainException('Project not found');
        }

        $project->unassignUser();
        $project->disableLogin();

        $this->projectRepository->save($project);

        return $project;
    }
}
