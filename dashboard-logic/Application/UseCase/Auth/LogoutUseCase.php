<?php

declare(strict_types=1);

namespace Dashboard\Application\UseCase\Auth;

/**
 * Caso de uso: cerrar la sesión del usuario.
 *
 * En Clean Architecture, el logout es una notificación de dominio.
 * La implementación concreta de limpiar la cookie/sesión/JWT pertenece
 * a la capa de Infrastructure o Presentation (el controller).
 *
 * Este caso de uso existe para:
 *   1. Mantener el modelo de aplicación completo y consistente
 *   2. Permitir hooks de dominio futuros (ej: registrar auditoría de logout)
 *   3. Ser un punto de extensión si se necesita lógica de negocio al cerrar sesión
 */
final class LogoutUseCase
{
    /**
     * Ejecuta el cierre de sesión.
     *
     * Actualmente es un no-op porque la limpieza de la cookie/sesión se maneja
     * en la capa de presentación. Si en el futuro se necesita registrar auditoría
     * o invalidar tokens, este es el lugar.
     */
    public function execute(): void
    {
        // La limpieza de cookie/sesión la maneja el controller de presentación.
        // Este método existe como punto de extensión para futura lógica de dominio.
    }
}
