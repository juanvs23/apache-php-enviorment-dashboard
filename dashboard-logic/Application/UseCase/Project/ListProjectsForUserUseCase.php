<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\Project;

use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Domain\Entity\Project;

/**
 * Caso de uso: listar proyectos del sistema.
 *
 * Retorna todos los proyectos o solo los asignados a un usuario específico.
 * Operación de solo lectura, no modifica estado.
 *
 * El filtrado por permisos (admin ve todos, cliente solo los propios)
 * se maneja en la capa de presentación o vía CheckPermissionUseCase.
 *
 * Dependencias:
 *   - ProjectRepositoryInterface: para recuperar los proyectos
 */
final class ListProjectsForUserUseCase
{
    /**
     * @param ProjectRepositoryInterface $projectRepository Repositorio de proyectos
     */
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    /**
     * Retorna los proyectos asignados a un usuario específico.
     *
     * @param string $userId UUID del usuario
     * @return Project[] Array de proyectos del usuario
     */
    public function execute(string $userId): array
    {
        return $this->projectRepository->findByUser($userId);
    }

    /**
     * Retorna todos los proyectos del sistema.
     *
     * @return Project[] Array de todos los proyectos
     */
    public function all(): array
    {
        return $this->projectRepository->findAll();
    }
}
