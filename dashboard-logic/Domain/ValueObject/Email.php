<?php

declare(strict_types=1);

namespace Dashboard\Domain\ValueObject;

/**
 * Value Object de Email.
 *
 * Objeto inmutable que encapsula un email con validación estricta.
 * Garantiza que el valor almacenado sea siempre un email válido,
 * en minúsculas y sin espacios, desde el momento de la creación.
 *
 * Lanza DomainException si el formato es inválido o si excede
 * la longitud máxima de 255 caracteres.
 */
final class Email
{
    /**
     * Email normalizado y validado.
     *
     * @var string
     */
    private string $value;

    /**
     * @param string $value Email en texto libre (se normaliza y valida)
     * @throws \DomainException Si el email está vacío, tiene formato inválido
     *                          o excede 255 caracteres
     */
    public function __construct(string $value)
    {
        $normalized = trim(mb_strtolower($value));

        if ($normalized === '') {
            throw new \DomainException('Email cannot be empty');
        }

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException("Invalid email format: {$value}");
        }

        if (mb_strlen($normalized) > 255) {
            throw new \DomainException('Email exceeds maximum length of 255 characters');
        }

        $this->value = $normalized;
    }

    /**
     * Retorna el email como string.
     *
     * @return string Email normalizado en minúsculas
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Retorna la representación string del email.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Compara si este email es igual a otro Value Object.
     *
     * @param self $other Otro email a comparar
     * @return bool True si ambos emails tienen el mismo valor
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
