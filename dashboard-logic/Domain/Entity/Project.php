<?php

declare(strict_types=1);

namespace Dashboard\Domain\Entity;

/**
 * Proyecto alojado en el servidor.
 *
 * Entidad pura del dominio que representa un proyecto web gestionado
 * por el dashboard (WordPress, Laravel, sitio estático, etc.).
 *
 * Cada proyecto puede estar asignado a un usuario y tener habilitado
 * o deshabilitado el acceso directo (login buttons).
 *
 * @property-read string      $projectId   UUID del proyecto
 * @property-read string      $projectName Nombre visible del proyecto
 * @property-read string|null $userOwnId   UUID del usuario asignado (null = sin asignar)
 * @property-read bool        $aceptLogin  Si los botones de acceso están habilitados
 */
final class Project
{
    /**
     * @param string      $projectId   UUID único del proyecto
     * @param string      $projectName Nombre del proyecto
     * @param string|null $userOwnId   UUID del usuario propietario (opcional)
     * @param bool        $aceptLogin  Si permite acceso directo
     */
    public function __construct(
        private readonly string $projectId,
        private string $projectName,
        private ?string $userOwnId,
        private bool $aceptLogin,
    ) {}

    /**
     * Factory method: crea un Project sin asignación y con login deshabilitado.
     *
     * @param string $projectId   UUID del proyecto
     * @param string $projectName Nombre del proyecto
     * @return self               Nuevo proyecto sin asignar
     */
    public static function create(string $projectId, string $projectName): self
    {
        return new self($projectId, $projectName, null, false);
    }

    /**
     * Retorna el UUID del proyecto.
     *
     * @return string
     */
    public function projectId(): string
    {
        return $this->projectId;
    }

    /**
     * Retorna el nombre del proyecto.
     *
     * @return string
     */
    public function projectName(): string
    {
        return $this->projectName;
    }

    /**
     * Retorna el UUID del usuario asignado al proyecto.
     *
     * @return string|null UUID del usuario o null si no está asignado
     */
    public function userOwnId(): ?string
    {
        return $this->userOwnId;
    }

    /**
     * Verifica si el proyecto tiene los botones de acceso habilitados.
     *
     * @return bool True si el acceso directo está habilitado
     */
    public function aceptLogin(): bool
    {
        return $this->aceptLogin;
    }

    /**
     * Asigna el proyecto a un usuario.
     *
     * @param string $userId UUID del usuario a asignar
     */
    public function assignToUser(string $userId): void
    {
        $this->userOwnId = $userId;
    }

    /**
     * Desasigna el proyecto del usuario actual.
     */
    public function unassignUser(): void
    {
        $this->userOwnId = null;
    }

    /**
     * Habilita los botones de acceso directo.
     */
    public function enableLogin(): void
    {
        $this->aceptLogin = true;
    }

    /**
     * Deshabilita los botones de acceso directo.
     */
    public function disableLogin(): void
    {
        $this->aceptLogin = false;
    }

    /**
     * Renombra el proyecto.
     *
     * @param string $newName Nuevo nombre del proyecto
     */
    public function rename(string $newName): void
    {
        $this->projectName = $newName;
    }
}
