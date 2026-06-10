<?php
/**
 * Vista de gestión de niveles y permisos (solo admin).
 * Variables:
 *   $levels      — array de niveles con sus permisos
 *   $permissions — catálogo de permisos disponibles
 *   $msg / $msg_type — mensaje de feedback
 *   $tab         — string 'levels'
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
    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateLevel">
        + Crear Nivel
    </button>
</div>

<?php if (empty($levels)): ?>
    <p class="text-muted text-center py-4">No hay niveles creados</p>
<?php else: ?>
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
<?php foreach ($levels as $lvl): ?>
    <?php $isLocked = $lvl['level_name'] === 'admin'; ?>
    <div class="col">
        <div class="card bg-dark border-secondary h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="card-title text-light mb-0 text-truncate me-2">
                        <?= htmlspecialchars($lvl['level_name']) ?>
                    </h6>
                    <span class="badge bg-<?= $lvl['level_type'] == 0 ? 'danger' : 'primary' ?> flex-shrink-0">
                        type <?= $lvl['level_type'] ?>
                    </span>
                </div>

                <div class="mb-2 small">
                    <span class="text-secondary">Permisos:</span>
                    <?php if (empty($lvl['perms'])): ?>
                        <span class="text-warning-emphasis">Ninguno</span>
                    <?php else: ?>
                        <?php foreach ($lvl['perms'] as $i => $pk): ?>
                            <span class="badge bg-secondary bg-opacity-25 text-light me-1 mb-1"><?= htmlspecialchars($pk) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mt-auto pt-2 border-top border-secondary d-flex gap-1">
                    <?php if ($isLocked): ?>
                        <button class="btn btn-sm btn-outline-secondary flex-fill" disabled title="Nivel protegido">
                            🔒 Protegido
                        </button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-warning flex-fill" data-bs-toggle="modal"
                                data-bs-target="#modalEditLevel-<?= htmlspecialchars($lvl['levelsID']) ?>">
                            ✏️ Editar
                        </button>
                        <form method="post" class="flex-fill"
                              onsubmit="return confirm('¿Eliminar nivel <?= htmlspecialchars($lvl['level_name']) ?>?')">
                            <input type="hidden" name="action" value="delete_level">
                            <input type="hidden" name="levelID" value="<?= htmlspecialchars($lvl['levelsID']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">🗑 Eliminar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!$isLocked): ?>
        <!-- Modal Editar Nivel -->
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
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning">Guardar Cambios</button>
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

<!-- Modal Crear Nivel -->
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear Nivel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/components/management-footer.php'; ?>
