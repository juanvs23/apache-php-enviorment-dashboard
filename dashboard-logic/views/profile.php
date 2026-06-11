<?php
/**
 * Vista de perfil de usuario.
 * Variables disponibles:
 *   $msg      — string mensaje de resultado
 *   $msg_type — string 'success' | 'danger'
 *   $user     — array usuario autenticado
 *   $isAdmin  — bool si es admin (level_type=0)
 */
?>
<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">👤 Mi Perfil</span>
    <a href="/" class="btn btn-outline-light btn-sm">Volver al Dashboard</a>
</nav>

<div class="d-flex justify-content-center" style="min-height: calc(100vh - 56px); background: linear-gradient(135deg, #1a1d23 0%, #2d323e 100%);">
    <div class="col-12 col-md-6 col-lg-5 col-xl-4 py-5 px-3">

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="text-center mb-4">
            <div class="display-1 mb-2">👤</div>
            <h4 class="text-light mb-1"><?= htmlspecialchars($user['name'] ?? $user['email']) ?></h4>
            <span class="badge bg-<?= $isAdmin ? 'danger' : 'primary' ?> fs-6">
                <?= htmlspecialchars($user['level_name']) ?>
            </span>
        </div>

        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-3">
                        <label class="form-label text-light">Email</label>
                        <input type="email" name="email" class="form-control bg-dark text-light border-secondary"
                               value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light">Nombre</label>
                        <input type="text" name="name" class="form-control bg-dark text-light border-secondary"
                               value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Tu nombre">
                    </div>

                    <hr class="border-secondary">

                    <div class="mb-3">
                        <label class="form-label text-light">Nueva contraseña</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="profilePassword"
                                   class="form-control bg-dark text-light border-secondary pe-5"
                                   placeholder="Dejar vacío para no cambiarla">
                            <button type="button"
                                    class="btn btn-link btn-sm position-absolute end-0 top-50 translate-middle-y me-1 text-decoration-none"
                                    onclick="togglePassword('profilePassword', this)" tabindex="-1"
                                    aria-label="Mostrar u ocultar contraseña">
                                Mostrar
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>

        <p class="text-center mt-4">
            <small class="text-muted">
                ¿Necesitás cambiar tu nivel de acceso?<br>Contactá a un administrador.
            </small>
        </p>

    </div>
</div>
