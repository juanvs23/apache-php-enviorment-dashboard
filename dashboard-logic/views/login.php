<?php
/**
 * Formulario de login.
 * Variables: $error, $script_name, $redirect_param
 */
?>
<?php if ($error): ?>
<div style="position: fixed; top: 0; left: 0; right: 0; z-index: 9999;">
    <div class="alert alert-danger text-center rounded-0 mb-0 py-2 small">
        <?= htmlspecialchars($error) ?>
    </div>
</div>
<?php endif; ?>

<section class="d-flex align-items-center justify-content-center" style="min-height: 100vh; background: linear-gradient(135deg, #16181d 0%, #1a1d23 50%, #1e2130 100%);">
    <style>
    .login-input::placeholder { color: #484f58 !important; opacity: 1; }
    .login-input:focus { border-color: #58a6ff !important; box-shadow: 0 0 0 2px rgba(88,166,255,0.2) !important; outline: none; }
    </style>
    <div class="col-11 col-md-5 col-lg-3">
        <div class="card border-0 shadow-lg" style="background: #1e2130; border-radius: 12px; overflow: hidden;">
            <div class="px-4 py-4 text-center"
                 style="background: linear-gradient(135deg, #23283a 0%, #1a1f30 100%); border-bottom: 1px solid #2d323e;">
                <div style="font-size: 2rem; margin-bottom: 4px;">⚡</div>
                <h5 style="color: #e4e6eb; font-weight: 600; margin-bottom: 2px;">Dev Dashboard</h5>
                <small style="color: #3fb950; font-size: 0.7rem;">● En línea</small>
            </div>
            <div class="card-body p-4">
                <form action="<?= $script_name ?>" method="post">
                    <?php if ($redirect_param && str_starts_with($redirect_param, '/')): ?>
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect_param) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label small" style="color: #8b949e;">Email</label>
                        <input type="email" name="email" class="form-control login-input"
                               style="background: #16181d; border: 1px solid #30363d; color: #e4e6eb;"
                               placeholder="admin@admin.com" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small" style="color: #8b949e;">Contraseña</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="loginPassword" class="form-control pe-5 login-input"
                                   style="background: #16181d; border: 1px solid #30363d; color: #e4e6eb;"
                                   placeholder="••••••••" required>
                            <button type="button"
                                    class="btn btn-link btn-sm position-absolute end-0 top-50 translate-middle-y me-1 text-decoration-none"
                                    style="color: #8b949e;" onclick="togglePassword('loginPassword', this)" tabindex="-1">
                                Mostrar
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn w-100 py-2" style="background: #238636; color: #fff; border: none; font-weight: 500;">
                        Ingresar
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
