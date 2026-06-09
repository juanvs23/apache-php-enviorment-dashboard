<?php
/**
 * Vista de gestión de usuarios y proyectos.
 * Variables disponibles:
 *   $msg          — string mensaje de resultado
 *   $msg_type     — string 'success' | 'danger'
 *   $users        — array de usuarios (get_all_users)
 *   $levels       — array de niveles (get_all_levels)
 *   $projects     — array de proyectos (get_all_projects)
 *   $client_users — array de usuarios cliente (get_client_users)
 *   $tab          — string 'usuarios' | 'proyectos'
 */
?>
<style>
.sidebar-link {
    display: block;
    padding: 0.75rem 1rem;
    color: #adb5bd;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: .15s;
}
.sidebar-link:hover {
    color: #fff;
    background: rgba(255,255,255,.05);
}
.sidebar-link.active {
    color: #fff;
    background: rgba(255,255,255,.1);
    border-left-color: #0d6efd;
}
</style>

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">
        Gestión de Usuarios
    </span>
    <a href="/" class="btn btn-outline-light btn-sm">Volver al Dashboard</a>
</nav>

<div class="d-flex" style="min-height: calc(100vh - 56px); background-color: var(--bs-gray-600);">

    <!-- ─── Sidebar ─────────────────────────────────────────────────── -->
    <div class="bg-dark p-0" style="width: 220px; flex-shrink: 0;">
        <div class="d-flex flex-column py-3">
            <a href="/?users=1&tab=usuarios"
               class="sidebar-link <?= $tab === 'usuarios' ? 'active' : '' ?>">
                👥 Usuarios
            </a>
            <a href="/?users=1&tab=proyectos"
               class="sidebar-link <?= $tab === 'proyectos' ? 'active' : '' ?>">
                📁 Proyectos
            </a>
        </div>
    </div>

    <!-- ─── Contenido ────────────────────────────────────────────────── -->
    <div class="flex-grow-1 p-4">

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($tab === 'usuarios'): ?>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- SECCIÓN USUARIOS -->
        <!-- ════════════════════════════════════════════════════════════ -->

        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-light small"><?= count($users) ?> usuario(s)</span>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
                + Crear Usuario
            </button>
        </div>

        <div class="tab-content container px-0">

            <!-- ─── Lista de Usuarios ─────────────────────────────── -->
            <div class="tab-pane fade show active" id="tabListaUsuarios">
                <?php if (empty($users)): ?>
                    <p class="text-muted text-center py-4">No hay usuarios registrados</p>
                <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                <?php foreach ($users as $u): ?>
                    <div class="col">
                        <div class="card bg-dark border-secondary h-100">
                            <div class="card-body d-flex flex-column text-light">
                                <div class="mb-1">
                                    <small class="text-secondary">Email</small>
                                    <div><code class="fs-6"><?= htmlspecialchars($u['email']) ?></code></div>
                                </div>
                                <div class="mb-1">
                                    <small class="text-secondary">Nombre</small>
                                    <div class="text-light"><?= htmlspecialchars($u['name'] ?? '—') ?></div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-secondary">Nivel</small>
                                    <div>
                                        <span class="badge bg-<?= $u['level_type'] == 0 ? 'danger' : 'primary' ?> fs-6">
                                            <?= htmlspecialchars($u['level_name']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-auto d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-warning flex-fill" data-bs-toggle="modal"
                                            data-bs-target="#modalEditUser-<?= htmlspecialchars($u['userID']) ?>">
                                        ✏️ Editar
                                    </button>
                                    <form method="post" class="d-inline flex-fill"
                                          onsubmit="return confirm('¿Eliminar usuario <?= htmlspecialchars($u['email']) ?>?')">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="userID" value="<?= htmlspecialchars($u['userID']) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">🗑 Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Editar -->
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
                                            <div class="mb-3">
                                                <label class="form-label text-light">Email</label>
                                                <input type="email" name="email" class="form-control"
                                                       value="<?= htmlspecialchars($u['email']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-light">Nombre</label>
                                                <input type="text" name="name" class="form-control"
                                                       value="<?= htmlspecialchars($u['name'] ?? '') ?>" placeholder="Nombre">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-light">Nueva contraseña</label>
                                                <input type="password" name="password" class="form-control"
                                                       placeholder="Vacío = sin cambios">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-light">Nivel</label>
                                                <select name="level" class="form-select" required>
                                                    <?php foreach ($levels as $l): ?>
                                                    <option value="<?= htmlspecialchars($l['levelsID']) ?>"
                                                        <?= $l['levelsID'] === $u['level'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($l['level_name']) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
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
                    </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ─── Crear Usuario ──────────────────────────────────── -->
            <div class="tab-pane fade" id="tabCrearUsuario">
                <div class="card bg-dark border-secondary">
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="action" value="create_user">

                            <div class="col-12 col-md-4">
                                <label class="form-label text-light">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label text-light">Nombre</label>
                                <input type="text" name="name" class="form-control" placeholder="Opcional">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label text-light">Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label text-light">Nivel</label>
                                <select name="level" class="form-select" required>
                                    <?php foreach ($levels as $l): ?>
                                    <option value="<?= htmlspecialchars($l['levelsID']) ?>">
                                        <?= htmlspecialchars($l['level_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">Crear Usuario</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <!-- ─── Modal Crear Usuario ────────────────────────────────── -->
        <div class="modal fade" id="modalCrearUsuario" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark border-secondary">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title text-light">Crear Usuario</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="create_user">
                            <div class="mb-3">
                                <label class="form-label text-light">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-light">Nombre</label>
                                <input type="text" name="name" class="form-control" placeholder="Opcional">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-light">Contraseña</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-light">Nivel</label>
                                <select name="level" class="form-select" required>
                                    <?php foreach ($levels as $l): ?>
                                    <option value="<?= htmlspecialchars($l['levelsID']) ?>">
                                        <?= htmlspecialchars($l['level_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Crear Usuario</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'proyectos'): ?>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- SECCIÓN PROYECTOS -->
        <!-- ════════════════════════════════════════════════════════════ -->

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabCrearProyecto"
                        type="button">Lista de Proyectos</button>
            </li>
        </ul>

        <div class="tab-content container px-0">

            <!-- ─── Lista de Proyectos ─────────────────────────────── -->
            <div class="tab-pane fade show active" id="tabCrearProyecto">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-light small"><?= count($projects) ?> proyecto(s)</span>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalCrearProyecto">
                        + Crear Proyecto
                    </button>
                </div>

                <?php if (empty($projects)): ?>
                    <p class="text-muted text-center py-4">No hay proyectos creados</p>
                <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                <?php foreach ($projects as $p): ?>
                    <div class="col">
                        <div class="card bg-dark border-secondary h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title text-light mb-0 text-truncate">
                                        <?= htmlspecialchars($p['project_name'] ?? '?') ?>
                                    </h6>
                                    <span class="badge bg-<?= $p['acept_login'] ? 'success' : 'secondary' ?> flex-shrink-0 ms-2">
                                        Login <?= $p['acept_login'] ? '✅' : '❌' ?>
                                    </span>
                                </div>
                                <div class="mb-2 small">
                                    <?php if ($p['user_email']): ?>
                                        <span class="text-muted">👤</span>
                                        <code><?= htmlspecialchars($p['user_email']) ?></code>
                                        <?php if ($p['user_name']): ?>
                                            <br><span class="text-info-emphasis ms-3"><?= htmlspecialchars($p['user_name']) ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-warning-emphasis">Sin asignar</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-auto pt-2 border-top border-secondary">
                                    <form method="post" class="row g-1">
                                        <input type="hidden" name="action" value="assign_project">
                                        <input type="hidden" name="projectID" value="<?= htmlspecialchars($p['id']) ?>">
                                        <div class="col-6">
                                            <select name="userID" class="form-select form-select-sm">
                                                <option value="">— Sin asignar —</option>
                                                <?php foreach ($client_users as $cu): ?>
                                                <option value="<?= htmlspecialchars($cu['userID']) ?>"
                                                    <?= $cu['userID'] === $p['user_own'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cu['email']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-3 d-flex align-items-center">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="acept_login" value="1"
                                                       id="acept-<?= htmlspecialchars($p['id']) ?>"
                                                       <?= $p['acept_login'] ? 'checked' : '' ?>>
                                                <label class="form-check-label text-light small" for="acept-<?= htmlspecialchars($p['id']) ?>">
                                                    Login
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">Asignar</button>
                                        </div>
                                    </form>
                                    <div class="d-flex gap-1 mt-1">
                                        <button class="btn btn-sm btn-outline-warning flex-fill" data-bs-toggle="modal"
                                                data-bs-target="#modalEditProject-<?= htmlspecialchars($p['id']) ?>">
                                            ✏️ Editar
                                        </button>
                                        <form method="post" class="flex-fill"
                                              onsubmit="return confirm('¿Eliminar proyecto <?= htmlspecialchars($p['project_name']) ?>?')">
                                            <input type="hidden" name="action" value="delete_project">
                                            <input type="hidden" name="projectID" value="<?= htmlspecialchars($p['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">🗑 Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Editar Proyecto -->
                        <div class="modal fade" id="modalEditProject-<?= htmlspecialchars($p['id']) ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content bg-dark border-secondary">
                                    <div class="modal-header border-secondary">
                                        <h5 class="modal-title text-light">Editar Proyecto</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="update_project">
                                            <input type="hidden" name="projectID" value="<?= htmlspecialchars($p['id']) ?>">
                                            <div class="mb-3">
                                                <label class="form-label text-light">Nombre del proyecto</label>
                                                <input type="text" name="project_name" class="form-control" required
                                                       value="<?= htmlspecialchars($p['project_name'] ?? '') ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-light">Asignar a usuario cliente</label>
                                                <select name="userID" class="form-select">
                                                    <option value="">— Sin asignar —</option>
                                                    <?php foreach ($client_users as $cu): ?>
                                                    <option value="<?= htmlspecialchars($cu['userID']) ?>"
                                                        <?= $cu['userID'] === $p['user_own'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($cu['email']) ?>
                                                        <?php if ($cu['name']): ?>(<?= htmlspecialchars($cu['name']) ?>)<?php endif; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" name="acept_login" value="1"
                                                           id="acept-modal-<?= htmlspecialchars($p['id']) ?>"
                                                           <?= $p['acept_login'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label text-light" for="acept-modal-<?= htmlspecialchars($p['id']) ?>">
                                                        Acepta Login
                                                    </label>
                                                </div>
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
                    </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- ─── Modal Crear Proyecto ───────────────────────────────── -->
        <div class="modal fade" id="modalCrearProyecto" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content bg-dark border-secondary">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title text-light">Crear Proyecto</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="create_project">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label text-light">Nombre del proyecto</label>
                                    <input type="text" name="project_name" class="form-control" required
                                           placeholder="ej: twilight, liberty">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-light">Asignar a usuario cliente</label>
                                    <select name="userID" class="form-select">
                                        <option value="">— Sin asignar —</option>
                                        <?php foreach ($client_users as $cu): ?>
                                        <option value="<?= htmlspecialchars($cu['userID']) ?>">
                                            <?= htmlspecialchars($cu['email']) ?>
                                            <?php if ($cu['name']): ?>(<?= htmlspecialchars($cu['name']) ?>)<?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6 d-flex align-items-end pb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="acept_login" value="1" id="acept-modal">
                                        <label class="form-check-label text-light" for="acept-modal">Acepta Login</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Crear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </div>
</div>
