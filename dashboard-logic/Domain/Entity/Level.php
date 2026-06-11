<?php

declare(strict_types=1);

namespace Dashboard\Domain\Entity;

use Dashboard\Domain\ValueObject\LevelType;

/**
 * Nivel de acceso (rol) del sistema.
 *
 * Entidad pura del dominio que representa un nivel o rol de usuario.
 * Cada nivel tiene un nombre y un tipo que determina si es administrativo (type=0)
 * o de cliente (type=1). El tipo admin otorga todos los permisos automáticamente.
 *
 * Ejemplos del sistema actual:
 *   - admin:    type=0 (acceso total, todos los permisos)
 *   - operator: type=0 (acceso administrativo parcial)
 *   - client:   type=1 (acceso restringido, solo proyectos asignados)
 *   - revisor:  type=1 (solo lectura)
 *
 * @property-read string    $levelId   UUID del nivel
 * @property-read string    $levelName Nombre único del nivel
 * @property-read LevelType $type      Tipo numérico (0=admin, 1=client)
 */
final class Level
{
    /**
     * @param string    $levelId   UUID único del nivel
     * @param string    $levelName Nombre del nivel (ej: "admin", "client")
     * @param LevelType $type      Tipo numérico del nivel
     */
    public function __construct(
        private readonly string $levelId,
        private string $levelName,
        private LevelType $type,
    ) {}

    /**
     * Retorna el UUID del nivel.
     *
     * @return string
     */
    public function levelId(): string
    {
        return $this->levelId;
    }

    /**
     * Retorna el nombre del nivel.
     *
     * @return string
     */
    public function levelName(): string
    {
        return $this->levelName;
    }

    /**
     * Retorna el tipo numérico del nivel.
     *
     * @return LevelType
     */
    public function type(): LevelType
    {
        return $this->type;
    }

    /**
     * Verifica si el nivel es de tipo admin (type=0).
     *
     * Los niveles admin tienen todos los permisos automáticamente,
     * sin necesidad de consultar la tabla level_permissions.
     *
     * @return bool True si es nivel administrativo
     */
    public function isAdmin(): bool
    {
        return $this->type->isAdmin();
    }

    /**
     * Renombra el nivel.
     *
     * @param string $newName Nuevo nombre del nivel
     */
    public function rename(string $newName): void
    {
        $this->levelName = $newName;
    }
}
