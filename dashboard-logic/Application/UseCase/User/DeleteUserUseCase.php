<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\User;

use Dashboard\Application\Repository\ProjectRepositoryInterface;
use Dashboard\Application\Repository\UserRepositoryInterface;

/**
 * Caso de uso: eliminar un usuario del sistema.
 *
 * Busca el usuario por UUID y lo elimina si existe.
 * Si se provee un ProjectRepositoryInterface, desvincula
 * todos los proyectos asignados al usuario antes de eliminarlo.
 *
 * La responsabilidad de verificar protecciones adicionales
 * (ej: no eliminar el último admin) puede agregarse aquí.
 *
 * Dependencias:
 *   - UserRepositoryInterface: para buscar y eliminar
 *   - ProjectRepositoryInterface (opcional): para desvincular proyectos
 */
final class DeleteUserUseCase
{
    /**
     * @param UserRepositoryInterface            $userRepository    Repositorio de usuarios
     * @param ProjectRepositoryInterface|null    $projectRepository Repositorio de proyectos (opcional, para desvinculación)
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly ?ProjectRepositoryInterface $projectRepository = null,
    ) {}

    /**
     * Ejecuta la eliminación del usuario.
     *
     * Desvincula proyectos asignados (si hay repositorio de proyectos)
     * y luego elimina al usuario.
     *
     * @param string $userId UUID del usuario a eliminar
     * @throws \DomainException Si el usuario no existe
     */
    public function execute(string $userId): void
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \DomainException('User not found');
        }

        // Desvincular proyectos antes de eliminar el usuario
        if ($this->projectRepository !== null) {
            $this->projectRepository->unassignProjectsByUserId($userId);
        }

        $this->userRepository->delete($userId);
    }
}
