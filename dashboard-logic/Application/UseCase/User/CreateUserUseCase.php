<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\User;

use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Domain\Entity\User;
use Dashboard\Domain\ValueObject\Email;

/**
 * Caso de uso: crear un nuevo usuario en el sistema.
 *
 * Valida los datos de entrada, verifica que el email no esté duplicado,
 * hashea la contraseña con bcrypt y persiste el nuevo usuario.
 *
 * Flujo:
 *   1. Validar email (formato vía Email VO)
 *   2. Validar contraseña no vacía
 *   3. Verificar unicidad de email
 *   4. Crear entidad User con contraseña hasheada
 *   5. Persistir vía repositorio
 *   6. Retornar el User creado
 *
 * Dependencias:
 *   - UserRepositoryInterface: para persistir y verificar unicidad
 */
final class CreateUserUseCase
{
    /**
     * @param UserRepositoryInterface $userRepository Repositorio de usuarios
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Ejecuta la creación del usuario.
     *
     * @param string      $userId         UUID para el nuevo usuario
     * @param string      $email          Email del usuario (se valida y normaliza)
     * @param string|null $name           Nombre visible (opcional)
     * @param string      $plainPassword  Contraseña en texto plano (se hashea con bcrypt)
     * @param string      $levelId        UUID del nivel a asignar
     * @return User                       El usuario creado
     * @throws \DomainException Si el email es inválido, ya existe, o falta la contraseña
     */
    public function execute(
        string $userId,
        string $email,
        ?string $name,
        string $plainPassword,
        string $levelId,
    ): User {
        $emailVo = new Email($email);

        if ($plainPassword === '') {
            throw new \DomainException('Password is required');
        }

        if ($this->userRepository->emailExists($emailVo->value())) {
            throw new \DomainException('Email already exists');
        }

        $user = User::create($userId, $emailVo, $name, $plainPassword, $levelId);
        $this->userRepository->save($user);

        return $user;
    }
}
