<?php
/**
 * Tarjeta de usuario con modal de edición.
 * Variables: $u (array), $levels (array)
 */
?>
<div class="col-12 col-md-6 col-lg-4 col-xl-3">
    <div class="card border-0 shadow-sm h-100" style="background: #1e2130; border-radius: 10px; overflow: hidden;">
        <!-- Header -->
        <div class="px-3 py-2 d-flex align-items-center gap-2"
             style="background: linear-gradient(135deg, #23283a 0%, #1a1f30 100%); border-bottom: 1px solid #2d323e;">
            <span style="font-size: 1.2rem;">👤</span>
            <h6 class="mb-0 text-truncate flex-grow-1" style="color: #e4e6eb; font-weight: 600; font-size: 0.9rem;">
                <?= htmlspecialchars($u['name'] ?: $u['email']) ?>
            </h6>
            <span class="badge rounded-pill flex-shrink-0"
                  style="background: <?= $u['is_admin_badge'] ? 'rgba(248,81,73,0.15)' : 'rgba(88,166,255,0.15)' ?>;
                         color: <?= $u['is_admin_badge'] ? '#f85149' : '#58a6ff' ?>; font-size: 0.7rem; padding: 4px 10px;">
                <?= htmlspecialchars($u['level_name']) ?>
            </span>
        </div>
        <!-- Body -->
        <div class="card-body p-3 d-flex flex-column" style="gap: 8px;">
            <div class="d-flex align-items-center gap-2">
                <span style="color: #8b949e; font-size: 0.75rem;">✉️</span>
                <code class="small" style="color: #c9d1d9;"><?= htmlspecialchars($u['email']) ?></code>
            </div>
            <div class="d-flex gap-2 mt-auto">
                <button class="btn btn-sm flex-fill py-1" data-bs-toggle="modal"
                        style="background: rgba(255,255,255,0.05); color: #8b949e; border: 1px solid #30363d; font-size: 0.72rem;"
                        data-bs-target="#modalEditUser-<?= htmlspecialchars($u['userID']) ?>">✏️ Editar</button>
                <form method="post" class="flex-fill"
                      onsubmit="return confirm('¿Eliminar usuario <?= htmlspecialchars($u['email']) ?>?')">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="userID" value="<?= htmlspecialchars($u['userID']) ?>">
                    <button type="submit" class="btn btn-sm w-100 py-1"
                            style="background: rgba(248,81,73,0.1); color: #f85149; border: 1px solid rgba(248,81,73,0.2); font-size: 0.72rem;">🗑 Eliminar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="modalEditUser-<?= htmlspecialchars($u['userID']) ?>" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-dark border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-light">Editar Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="userID" value="<?= htmlspecialchars($u['userID']) ?>">
                        <?php $user = $u; $prefix = 'edit-' . htmlspecialchars($u['userID']) . '-'; $showPass = true;
                              require __DIR__ . '/_user-form-fields.php'; ?>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
