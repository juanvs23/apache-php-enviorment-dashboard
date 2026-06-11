<?php

declare(strict_types=1);

namespace Dashboard\Domain\ValueObject;

/**
 * Value Object de LevelType.
 *
 * Objeto inmutable que representa el tipo numérico de un nivel de acceso.
 * Determina si un nivel es administrativo (type=0, acceso total con todos
 * los permisos automáticamente) o de cliente (type=1, permisos restringidos
 * que deben asignarse explícitamente en level_permissions).
 *
 * Solo admite dos valores:
 *   0 → Admin  (acceso total, todos los permisos vía can())
 *   1 → Client (permisos limitados, consulta level_permissions)
 *
 * @property-read int $value Tipo numérico: 0 (admin) o 1 (client)
 */
final class LevelType
{
    /** Tipo admin: acceso total, todos los permisos automáticos */
    public const ADMIN = 0;

    /** Tipo client: permisos restringidos, consulta level_permissions */
    public const CLIENT = 1;

    /**
     * Lista de valores permitidos para validación.
     *
     * @var array<int, string>
     */
    private const ALLOWED = [
        self::ADMIN  => 'admin',
        self::CLIENT => 'client',
    ];

    /**
     * @param int $value Tipo numérico (0=admin, 1=client)
     * @throws \DomainException Si el valor no es 0 ni 1
     */
    public function __construct(
        private readonly int $value,
    ) {
        if (!isset(self::ALLOWED[$value])) {
            throw new \DomainException(
                sprintf('Invalid level type: %d. Allowed: %d (admin) or %d (client)', $value, self::ADMIN, self::CLIENT)
            );
        }
    }

    /**
     * Retorna el valor numérico del tipo.
     *
     * @return int 0 (admin) o 1 (client)
     */
    public function value(): int
    {
        return $this->value;
    }

    /**
     * Verifica si es tipo admin (acceso total).
     *
     * @return bool True si es tipo admin (value=0)
     */
    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }

    /**
     * Verifica si es tipo client (permisos restringidos).
     *
     * @return bool True si es tipo client (value=1)
     */
    public function isClient(): bool
    {
        return $this->value === self::CLIENT;
    }

    /**
     * Compara si este tipo es igual a otro LevelType.
     *
     * @param self $other Otro LevelType a comparar
     * @return bool True si ambos tienen el mismo valor numérico
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
