<?php
/**
 * Formulario de login.
 * Variables disponibles:
 *   $error           — string (mensaje de error vacío si no hay)
 *   $script_name     — string
 *   $redirect_param  — string
 */
?>
<?php if ($error): ?>
<div style="position: fixed; top: 5px; left: 0; right: 0; z-index: 9999;">
    <div class="alert alert-danger text-center rounded-0 mb-0">
        <h4 class="alert-heading mb-0"><?= htmlspecialchars($error) ?></h4>
    </div>
</div>
<?php endif; ?>

<section class="container-fluid d-flex align-items-center" style="min-height: 100vh; background-color: var(--bs-gray-600);">
    <div class="row justify-content-center w-100">
        <div class="col-10 col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h1 class="mb-0 display-6 text-dark">Dev Dashboard</h1>
                    <small class="text-muted"><?= gethostname() ?></small>
                </div>
                <div class="card-body p-4">
                    <form action="<?= $script_name ?>" method="post">

                        <?php if ($redirect_param && str_starts_with($redirect_param, '/')): ?>
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect_param) ?>">
                        <?php endif; ?>

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="floatingEmail"
                                   name="email" placeholder="Email" required autocomplete="email" autofocus>
                            <label for="floatingEmail">Email</label>
                        </div>

                        <div class="form-floating mb-3 position-relative">
                            <input type="password" class="form-control" id="floatingPassword"
                                   name="password" placeholder="Contraseña" required autocomplete="current-password">
                            <label for="floatingPassword">Contraseña</label>
                            <button type="button"
                                    class="btn btn-link btn-sm position-absolute end-0 top-50 translate-middle-y me-1 text-decoration-none"
                                    onclick="togglePassword('floatingPassword', this)" tabindex="-1"
                                    style="z-index: 5;" aria-label="Mostrar u ocultar contraseña">
                                Mostrar
                            </button>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
