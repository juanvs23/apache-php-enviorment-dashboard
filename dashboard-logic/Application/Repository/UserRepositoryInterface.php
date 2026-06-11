<?php

declare(strict_types=1);

namespace Dashboard\Application\Repository;

use Dashboard\Domain\Entity\User;

/**
 * Puerto de repositorio para la entidad User.
 *
 * Define el contrato que la infraestructura debe implementar para
 * persistir y recuperar usuarios. La capa de Application depende de
 * esta interfaz, nunca de implementaciones concretas (MySQL, SQLite, etc.).
 *
 * Métodos disponibles:
 *   findById()     → Buscar por UUID
 *   findByEmail()  → Buscar por email
 *   findAll()      → Listar todos
 *   findByLevel()  → Filtrar por nivel
 *   save()         → Crear o actualizar
 *   delete()       → Eliminar
 *   emailExists()  → Verificar unicidad de email
 */
interface UserRepositoryInterface
{
    /**
     * Busca un usuario por su UUID.
     *
     * @param string $userId UUID del usuario
     * @return User|null El usuario si existe, null si no
     */
    public function findById(string $userId): ?User;

    /**
     * Busca un usuario por su email.
     *
     * @param string $email Email del usuario
     * @return User|null El usuario si existe, null si no
     */
    public function findByEmail(string $email): ?User;

    /**
     * Retorna todos los usuarios del sistema.
     *
     * @return User[] Array de usuarios
     */
    public function findAll(): array;

    /**
     * Retorna los usuarios que pertenecen a un nivel específico.
     *
     * @param string $levelId UUID del nivel
     * @return User[] Array de usuarios del nivel
     */
    public function findByLevel(string $levelId): array;

    /**
     * Persiste un usuario (crea o actualiza según exista el UUID).
     *
     * @param User $user El usuario a guardar
     */
    public function save(User $user): void;

    /**
     * Elimina un usuario por su UUID.
     *
     * @param string $userId UUID del usuario a eliminar
     */
    public function delete(string $userId): void;

    /**
     * Verifica si un email ya está registrado en el sistema.
     *
     * @param string      $email           Email a verificar
     * @param string|null $excludeUserId   UUID a excluir (para ediciones, evitar falso positivo)
     * @return bool True si el email ya existe
     */
    public function emailExists(string $email, ?string $excludeUserId = null): bool;
}
