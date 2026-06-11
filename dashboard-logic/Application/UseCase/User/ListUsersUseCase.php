<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\User;

use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Domain\Entity\User;

/**
 * Caso de uso: listar usuarios del sistema.
 *
 * Retorna todos los usuarios o filtra por nivel específico.
 * Operación de solo lectura, no modifica estado.
 *
 * Dependencias:
 *   - UserRepositoryInterface: para recuperar los usuarios
 */
final class ListUsersUseCase
{
    /**
     * @param UserRepositoryInterface $userRepository Repositorio de usuarios
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Retorna todos los usuarios del sistema.
     *
     * @return User[] Array de todos los usuarios
     */
    public function execute(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * Retorna los usuarios que pertenecen a un nivel específico.
     *
     * @param string $levelId UUID del nivel
     * @return User[] Array de usuarios del nivel
     */
    public function findByLevel(string $levelId): array
    {
        return $this->userRepository->findByLevel($levelId);
    }
}
