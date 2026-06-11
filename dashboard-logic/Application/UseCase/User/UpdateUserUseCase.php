<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\User;

use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Domain\Entity\User;
use Dashboard\Domain\ValueObject\Email;

/**
 * Caso de uso: actualizar datos de un usuario existente.
 *
 * Permite modificar email, nombre y contraseña de un usuario.
 * Verifica que el email nuevo no esté en uso por otro usuario
 * (excluyendo al usuario actual). Si la contraseña está vacía,
 * se mantiene la existente.
 *
 * Dependencias:
 *   - UserRepositoryInterface: para buscar, verificar unicidad y persistir
 */
final class UpdateUserUseCase
{
    /**
     * @param UserRepositoryInterface $userRepository Repositorio de usuarios
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Ejecuta la actualización del usuario.
     *
     * @param string      $userId         UUID del usuario a actualizar
     * @param string      $email          Nuevo email (se valida)
     * @param string|null $name           Nuevo nombre (null para limpiar)
     * @param string      $plainPassword  Nueva contraseña en texto plano (vacío = mantener actual)
     * @param string|null $levelId        Nuevo nivel (null = mantener actual)
     * @return User                       El usuario actualizado
     * @throws \DomainException Si el usuario no existe, el email es inválido o ya está en uso
     */
    public function execute(
        string $userId,
        string $email,
        ?string $name,
        string $plainPassword = '',
        ?string $levelId = null,
    ): User {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new \DomainException('User not found');
        }

        $emailVo = new Email($email);

        // Verificar email único excluyendo al usuario actual
        if ($this->userRepository->emailExists($emailVo->value(), $userId)) {
            throw new \DomainException('Email is already in use by another user');
        }

        $user->changeEmail($emailVo);
        $user->changeName($name);

        if ($plainPassword !== '') {
            $user->changePassword($plainPassword);
        }

        if ($levelId !== null) {
            $user->changeLevel($levelId);
        }

        $this->userRepository->save($user);

        return $user;
    }
}
