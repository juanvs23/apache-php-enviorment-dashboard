<?php

declare(strict_types=1);

namespace Dashboard\Domain\Entity;

use Dashboard\Domain\ValueObject\Email;

/**
 * Usuario del sistema.
 *
 * Entidad pura del dominio que representa un usuario registrado en el dashboard.
 * Encapsula el estado del usuario y las reglas de negocio relacionadas con
 * autenticación y actualización de datos.
 *
 * No tiene conocimiento de persistencia, HTTP, ni infraestructura.
 * Las contraseñas siempre se almacenan como hash bcrypt, nunca en texto plano.
 *
 * @property-read string     $userId        UUID del usuario
 * @property-read Email      $email         Email con validación de dominio
 * @property-read string|null $name         Nombre visible (opcional)
 * @property-read string     $passwordHash  Hash bcrypt de la contraseña
 * @property-read string     $levelId       UUID del nivel/rol asignado
 */
final class User
{
    /**
     * @param string      $userId        UUID único del usuario
     * @param Email       $email         Email validado
     * @param string|null $name          Nombre visible (puede ser null)
     * @param string      $passwordHash  Hash bcrypt de la contraseña
     * @param string      $levelId       UUID del nivel asignado
     */
    public function __construct(
        private readonly string $userId,
        private Email $email,
        private ?string $name,
        private string $passwordHash,
        private string $levelId,
    ) {}

    /**
     * Factory method: crea un User con contraseña en texto plano automáticamente hasheada.
     *
     * @param string $userId         UUID del usuario
     * @param Email  $email          Email validado
     * @param string|null $name      Nombre visible (opcional)
     * @param string $plainPassword  Contraseña en texto plano (se hashea con bcrypt)
     * @param string $levelId        UUID del nivel asignado
     * @return self                  Nuevo usuario con contraseña hasheada
     */
    public static function create(
        string $userId,
        Email $email,
        ?string $name,
        string $plainPassword,
        string $levelId,
    ): self {
        return new self(
            $userId,
            $email,
            $name,
            password_hash($plainPassword, PASSWORD_BCRYPT),
            $levelId,
        );
    }

    /**
     * Retorna el UUID del usuario.
     *
     * @return string
     */
    public function userId(): string
    {
        return $this->userId;
    }

    /**
     * Retorna el email del usuario.
     *
     * @return Email
     */
    public function email(): Email
    {
        return $this->email;
    }

    /**
     * Retorna el nombre visible del usuario.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Retorna el hash bcrypt de la contraseña.
     *
     * @return string
     */
    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * Retorna el UUID del nivel/rol asignado.
     *
     * @return string
     */
    public function levelId(): string
    {
        return $this->levelId;
    }

    /**
     * Verifica si una contraseña en texto plano coincide con el hash almacenado.
     *
     * @param string $plainPassword Contraseña en texto plano a verificar
     * @return bool True si la contraseña es correcta
     */
    public function authenticate(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    /**
     * Cambia el email del usuario.
     *
     * @param Email $email Nuevo email validado
     */
    public function changeEmail(Email $email): void
    {
        $this->email = $email;
    }

    /**
     * Cambia el nombre visible del usuario.
     *
     * @param string|null $name Nuevo nombre (null para limpiar)
     */
    public function changeName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Cambia la contraseña del usuario.
     *
     * @param string $newPlainPassword Nueva contraseña en texto plano (se hashea automáticamente)
     */
    public function changePassword(string $newPlainPassword): void
    {
        $this->passwordHash = password_hash($newPlainPassword, PASSWORD_BCRYPT);
    }

    /**
     * Cambia el nivel/rol del usuario.
     *
     * @param string $levelId UUID del nuevo nivel
     */
    public function changeLevel(string $levelId): void
    {
        $this->levelId = $levelId;
    }
}
