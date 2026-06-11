<?php

declare(strict_types=1);

namespace Dashboard\Domain\Entity;

/**
 * Permiso del sistema.
 *
 * Entidad pura e inmutable del dominio que representa una acción o capacidad
 * que puede ser asignada a un nivel de acceso. Los permisos son el building block
 * del sistema RBAC (Role-Based Access Control).
 *
 * Ejemplos del sistema actual:
 *   - users.manage       → CRUD de usuarios
 *   - projects.view_all  → Ver todos los proyectos
 *   - server.view        → Ver información del servidor
 *   - badge.admin        → Mostrar badge de admin en la UI
 *
 * @property-read int    $id    ID auto-incremental del permiso
 * @property-read string $key   Clave única del permiso (ej: "users.manage")
 * @property-read string $label Etiqueta legible del permiso
 */
final class Permission
{
    /**
     * @param int    $id    ID numérico del permiso
     * @param string $key   Clave única (ej: "users.manage")
     * @param string $label Descripción legible del permiso
     */
    public function __construct(
        private readonly int $id,
        private readonly string $key,
        private readonly string $label,
    ) {}

    /**
     * Retorna el ID numérico del permiso.
     *
     * @return int
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Retorna la clave única del permiso.
     *
     * @return string Ej: "users.manage", "server.view"
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * Retorna la etiqueta legible del permiso.
     *
     * @return string Ej: "Gestionar usuarios (CRUD)"
     */
    public function label(): string
    {
        return $this->label;
    }
}
