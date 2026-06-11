<?php

declare(strict_types=1);

namespace Dashboard\Presentation\Controller;

use Dashboard\Application\UseCase\User\UpdateUserUseCase;
use Dashboard\Infrastructure\Auth\AuthContext;

/**
 * Controlador de perfil de usuario.
 *
 * Permite al usuario autenticado modificar sus propios datos:
 * email, nombre y contraseña. El nivel/rol no se puede cambiar desde acá.
 *
 * Ruta:
 *   GET ?profile=1  → handleProfile() — muestra formulario y procesa POST
 */
final class ProfileController
{
    /**
     * @param UpdateUserUseCase $updateUser Caso de uso para actualizar usuario
     * @param AuthContext       $authContext Contexto de autenticación
     */
    public function __construct(
        private readonly UpdateUserUseCase $updateUser,
        private readonly AuthContext $authContext,
    ) {}

    /**
     * Maneja la edición del perfil.
     *
     * Si se recibe un POST con action=update_profile, procesa la
     * actualización via UpdateUserUseCase en lugar de la función
     * legacy handle_profile_update(). Prepara las variables que
     * necesita la vista `views/profile.php`:
     *   - $msg, $msg_type → resultado de la operación
     *   - $user           → usuario autenticado (get_auth_user)
     */
    public function handleProfile(): void
    {
        $msg      = '';
        $msg_type = 'success';

        // ─── Procesar POST con UpdateUserUseCase ─────────────────
        if (($_POST['action'] ?? '') === 'update_profile') {
            $authUser = $this->authContext->currentUser();
            if ($authUser) {
                try {
                    $email    = \trim($_POST['email'] ?? '');
                    $name     = \trim($_POST['name'] ?? '');
                    $password = $_POST['password'] ?? '';

                    $this->updateUser->execute(
                        $authUser['userID'],
                        $email,
                        $name !== '' ? $name : null,
                        $password,
                    );

                    $msg      = 'Perfil actualizado correctamente';
                    $msg_type = 'success';
                } catch (\DomainException $e) {
                    $msg      = $e->getMessage();
                    $msg_type = 'danger';
                }
            }
        }

        $user = $this->authContext->currentUser();
        $isAdmin = $user ? $user['level_type'] === 0 : false;
        require __DIR__ . '/../../views/profile.php';
    }
}
