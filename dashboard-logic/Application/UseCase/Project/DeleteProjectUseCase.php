<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\Project;

use Dashboard\Application\Repository\ProjectRepositoryInterface;

/**
 * Caso de uso: eliminar un proyecto del sistema.
 *
 * Busca el proyecto por UUID y lo elimina si existe.
 *
 * Dependencias:
 *   - ProjectRepositoryInterface: para buscar y eliminar
 */
final class DeleteProjectUseCase
{
    /**
     * @param ProjectRepositoryInterface $projectRepository Repositorio de proyectos
     */
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    /**
     * Ejecuta la eliminación del proyecto.
     *
     * @param string $projectId UUID del proyecto a eliminar
     * @throws \DomainException Si el proyecto no existe
     */
    public function execute(string $projectId): void
    {
        $project = $this->projectRepository->findById($projectId);
        if ($project === null) {
            throw new \DomainException('Project not found');
        }

        $this->projectRepository->delete($projectId);
    }
}
