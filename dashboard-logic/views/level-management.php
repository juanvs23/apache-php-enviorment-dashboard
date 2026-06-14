<?php
/**
 * Vista de gestión de niveles y permisos (solo admin).
 * Variables: $levels, $permissions, $msg/$msg_type, $tab
 */
?>
<?php require __DIR__ . '/components/management-header.php'; ?>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-light small"><?= count($levels) ?> nivel(es)</span>
    <button type="button" class="btn btn-sm py-1" data-bs-toggle="modal" data-bs-target="#modalCreateLevel"
            style="background: rgba(63,185,80,0.15); color: #3fb950; border: 1px solid rgba(63,185,80,0.2); font-size: 0.78rem;">
        + Crear Nivel
    </button>
</div>

<?php if (empty($levels)): ?>
    <p class="text-muted text-center py-4">No hay niveles creados</p>
<?php else: ?>
<div class="row g-3">
<?php foreach ($levels as $lvl): ?>
    <?php $isLocked = $lvl['level_name'] === 'admin'; ?>
    <div class="col-12 col-md-6 col-lg-4 col-xl-3">
        <div class="card border-0 shadow-sm h-100" style="background: #1e2130; border-radius: 10px; overflow: hidden;">
            <!-- Header -->
            <div class="px-3 py-2 d-flex align-items-center gap-2"
                 style="background: linear-gradient(135deg, #23283a 0%, #1a1f30 100%); border-bottom: 1px solid #2d323e;">
                <span style="font-size: 1.2rem;"><?= $isLocked ? '🔒' : '🔐' ?></span>
                <h6 class="mb-0 text-truncate flex-grow-1" style="color: #e4e6eb; font-weight: 600; font-size: 0.9rem;">
                    <?= htmlspecialchars($lvl['level_name']) ?>
                </h6>
                <span class="badge rounded-pill flex-shrink-0"
                      style="background: <?= $lvl['level_type'] == 0 ? 'rgba(248,81,73,0.15)' : 'rgba(88,166,255,0.15)' ?>;
                             color: <?= $lvl['level_type'] == 0 ? '#f85149' : '#58a6ff' ?>; font-size: 0.7rem; padding: 4px 10px;">
                    <?= $lvl['level_type'] == 0 ? 'Admin' : 'Cliente' ?>
                </span>
            </div>
            <!-- Body -->
            <div class="card-body p-3 d-flex flex-column" style="gap: 8px;">
                <div class="small" style="color: #8b949e;">
                    <span>Permisos:</span>
                    <?php if (empty($lvl['perms'])): ?>
                        <span class="fst-italic">Ninguno</span>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <?php foreach ($lvl['perms'] as $pk): ?>
                            <span class="badge" style="background: rgba(139,148,158,0.15); color: #c9d1d9; font-size: 0.65rem; font-weight: normal;">
                                <?= htmlspecialchars($pk) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2 mt-auto">
                    <?php if ($isLocked): ?>
                        <button class="btn btn-sm flex-fill py-1" disabled
                                style="background: rgba(255,255,255,0.03); color: #484f58; border: 1px solid #21262d; font-size: 0.72rem;">🔒 Protegido</button>
                    <?php else: ?>
                        <button class="btn btn-sm flex-fill py-1" data-bs-toggle="modal"
                                style="background: rgba(255,255,255,0.05); color: #8b949e; border: 1px solid #30363d; font-size: 0.72rem;"
                                data-bs-target="#modalEditLevel-<?= htmlspecialchars($lvl['levelsID']) ?>">✏️ Editar</button>
                        <form method="post" class="flex-fill"
                              onsubmit="return confirm('¿Eliminar nivel <?= htmlspecialchars($lvl['level_name']) ?>?')">
                            <input type="hidden" name="action" value="delete_level">
                            <input type="hidden" name="levelID" value="<?= htmlspecialchars($lvl['levelsID']) ?>">
                            <button type="submit" class="btn btn-sm w-100 py-1"
                                    style="background: rgba(248,81,73,0.1); color: #f85149; border: 1px solid rgba(248,81,73,0.2); font-size: 0.72rem;">🗑 Eliminar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!$isLocked): ?>
        <div class="modal fade" id="modalEditLevel-<?= htmlspecialchars($lvl['levelsID']) ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark border-secondary">
                    <form method="post">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title text-light">Editar Nivel</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update_level">
                            <input type="hidden" name="levelID" value="<?= htmlspecialchars($lvl['levelsID']) ?>">
                            <div class="mb-3">
                                <label class="form-label text-light">Nombre del nivel</label>
                                <input type="text" name="level_name" class="form-control bg-dark text-light border-secondary"
                                       value="<?= htmlspecialchars($lvl['level_name']) ?>" required>
                            </div>
                            <label class="form-label text-light">Permisos</label>
                            <div class="mb-3" style="max-height: 250px; overflow-y: auto;">
                                <?php foreach ($permissions as $perm): ?>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="perms[]"
                                           value="<?= $perm['id'] ?>" id="edit-p-<?= $lvl['levelsID'] ?>-<?= $perm['id'] ?>"
                                           <?= in_array($perm['perm_key'], $lvl['perms']) ? 'checked' : '' ?>>
                                    <label class="form-check-label text-light small" for="edit-p-<?= $lvl['levelsID'] ?>-<?= $perm['id'] ?>">
                                        <code><?= htmlspecialchars($perm['perm_key']) ?></code>
                                        <span class="text-secondary">— <?= htmlspecialchars($perm['perm_label']) ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning btn-sm">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="modal fade" id="modalCreateLevel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <form method="post">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-light">Crear Nivel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_level">
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre del nivel</label>
                        <input type="text" name="level_name" class="form-control bg-dark text-light border-secondary"
                               placeholder="ej: editor, moderator" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Tipo</label>
                        <select name="level_type" class="form-select bg-dark text-light border-secondary">
                            <option value="0">0 — Admin (todos los permisos)</option>
                            <option value="1" selected>1 — Cliente (permisos limitados)</option>
                        </select>
                        <small class="text-secondary">Admin (type 0) tiene TODOS los permisos sin necesidad de asignarlos.</small>
                    </div>
                    <label class="form-label text-light">Permisos</label>
                    <div class="mb-3" style="max-height: 250px; overflow-y: auto;">
                        <?php foreach ($permissions as $perm): ?>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="perms[]"
                                   value="<?= $perm['id'] ?>" id="create-p-<?= $perm['id'] ?>">
                            <label class="form-check-label text-light small" for="create-p-<?= $perm['id'] ?>">
                                <code><?= htmlspecialchars($perm['perm_key']) ?></code>
                                <span class="text-secondary">— <?= htmlspecialchars($perm['perm_label']) ?></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm">Crear Nivel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/components/management-footer.php'; ?>
