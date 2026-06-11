<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\Auth;

use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Domain\Entity\User;

/**
 * Caso de uso: autenticar un usuario en el sistema.
 *
 * Valida las credenciales (email + contraseña) contra el repositorio
 * de usuarios. Si las credenciales son correctas, retorna el User.
 * Si fallan, lanza DomainException sin revelar si el email existe o no
 * (seguridad: mismo mensaje para "no existe" y "contraseña incorrecta").
 *
 * Dependencias:
 *   - UserRepositoryInterface: para buscar el usuario por email
 */
final class LoginUseCase
{
    /**
     * @param UserRepositoryInterface $userRepository Repositorio de usuarios
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Ejecuta la autenticación.
     *
     * @param string $email    Email del usuario
     * @param string $password Contraseña en texto plano
     * @return User            Usuario autenticado
     * @throws \DomainException Si el email está vacío, la contraseña está vacía,
     *                          o las credenciales son inválidas
     */
    public function execute(string $email, string $password): User
    {
        if ($email === '' || $password === '') {
            throw new \DomainException('Email and password are required');
        }

        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            throw new \DomainException('Invalid email or password');
        }

        if (!$user->authenticate($password)) {
            throw new \DomainException('Invalid email or password');
        }

        return $user;
    }
}
