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
<?php require __DIR__ . '/components/management-header.php'; ?>

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

        <?php if (empty($users)): ?>
            <p class="text-muted text-center py-4">No hay usuarios registrados</p>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                <?php foreach ($users as $u): ?>
                    <?php require __DIR__ . '/components/_user-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Modal Crear Usuario -->
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
                            <?php $user = null; $prefix = 'create-'; $showPass = true;
                                  require __DIR__ . '/components/_user-form-fields.php'; ?>
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
                <div class="row g-3">
                <?php foreach ($projects as $p): ?>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                        <div class="card border-0 shadow-sm h-100 project-admin-card"
                             style="background: #1e2130; border-radius: 10px; overflow: hidden;">
                            <!-- Header -->
                            <div class="px-3 py-3 d-flex align-items-center gap-2"
                                 style="background: linear-gradient(135deg, #23283a 0%, #1a1f30 100%); border-bottom: 1px solid #2d323e;">
                                <span style="font-size: 1.2rem;">📁</span>
                                <h6 class="mb-0 text-truncate flex-grow-1" style="color: #e4e6eb; font-weight: 600; font-size: 0.9rem;">
                                    <?= htmlspecialchars($p['project_name'] ?? '?') ?>
                                </h6>
                                <span class="badge rounded-pill flex-shrink-0"
                                      style="background: <?= $p['user_count'] ? 'rgba(63,185,80,0.15)' : 'rgba(139,148,158,0.15)' ?>;
                                             color: <?= $p['user_count'] ? '#3fb950' : '#8b949e' ?>; font-size: 0.7rem; padding: 4px 10px;">
                                    <?= $p['user_count'] ? $p['user_count'] . ' 👤' : 'Sin asignar' ?>
                                </span>
                            </div>
                            <!-- Body -->
                            <div class="card-body p-3 d-flex flex-column" style="gap: 10px;">
                                <?php
                                $pid = $p['id'];
                                $assignedUsers = $p['user_own'] ? json_decode($p['user_own'], true) : [];
                                ?>
                                <div class="assigned-users-list" data-project="<?= htmlspecialchars($pid) ?>"
                                     style="min-height: <?= $assignedUsers ? '' : '32px' ?>;">
                                    <?php foreach ($assignedUsers as $u): ?>
                                    <div class="assigned-user-chip"
                                         data-user="<?= htmlspecialchars($u['userID']) ?>"
                                         style="display: inline-flex; align-items: center; gap: 5px;
                                                background: #1a3a5c; border-radius: 5px; padding: 4px 8px;
                                                font-size: 0.72rem; margin: 0 4px 4px 0; transition: all .15s;">
                                        <span style="color: #58a6ff;"><?= htmlspecialchars($u['user_name'] ?? $u['userID']) ?></span>
                                        <span class="toggle-login-chip"
                                              data-user="<?= htmlspecialchars($u['userID']) ?>"
                                              data-project="<?= htmlspecialchars($pid) ?>"
                                              data-logeable="<?= $u['is_logeable'] ? '1' : '0' ?>"
                                              style="cursor:pointer;font-size:0.7rem;"
                                              title="Click para activar/desactivar autologin">
                                            <?= $u['is_logeable'] ? '🔑' : '🔒' ?>
                                        </span>
                                        <span class="remove-assigned-user"
                                              data-user="<?= htmlspecialchars($u['userID']) ?>"
                                              data-project="<?= htmlspecialchars($pid) ?>"
                                              style="cursor:pointer; color:#f85149; font-size:0.85rem; opacity:0.5;
                                                     transition:opacity .15s;"
                                              onmouseover="this.style.opacity='1'"
                                              onmouseout="this.style.opacity='0.5'">&times;</span>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php if (!$assignedUsers): ?>
                                    <small class="text-muted fst-italic">Sin usuarios asignados</small>
                                    <?php endif; ?>
                                </div>
                                <div class="d-grid gap-2 mt-auto">
                                    <button type="button" class="btn btn-sm assign-client-btn py-2"
                                            style="background: rgba(88,166,255,0.1); color: #58a6ff; border: 1px solid rgba(88,166,255,0.2);
                                                   font-size: 0.78rem; font-weight: 500; transition: all .15s;"
                                            onmouseover="this.style.background='rgba(88,166,255,0.2)'"
                                            onmouseout="this.style.background='rgba(88,166,255,0.1)'"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalAssignClient-<?= htmlspecialchars($pid) ?>">
                                        + Asignar cliente
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm flex-fill py-1"
                                                style="background: rgba(255,255,255,0.05); color: #8b949e; border: 1px solid #30363d; font-size: 0.72rem;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditProject-<?= htmlspecialchars($p['id']) ?>">✏️ Editar</button>
                                        <form method="post" class="flex-fill"
                                              onsubmit="return confirm('¿Eliminar proyecto <?= htmlspecialchars($p['project_name']) ?>?')">
                                            <input type="hidden" name="action" value="delete_project">
                                            <input type="hidden" name="projectID" value="<?= htmlspecialchars($p['id']) ?>">
                                            <button type="submit" class="btn btn-sm w-100 py-1"
                                                    style="background: rgba(248,81,73,0.1); color: #f85149; border: 1px solid rgba(248,81,73,0.2); font-size: 0.72rem;">🗑 Eliminar</button>
                                        </form>
                                    </div>
                            </div>
                        </div>
                        </div>

                        <!-- Modal Asignar Cliente -->
                        <div class="modal fade" id="modalAssignClient-<?= htmlspecialchars($pid) ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content bg-dark border-secondary">
                                    <div class="modal-header border-secondary">
                                        <h6 class="modal-title text-light">Asignar cliente a <?= htmlspecialchars($p['project_name']) ?></h6>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" class="selected-client-id" value="">
                                        <input type="text" class="form-control form-control-sm bg-dark text-light border-secondary client-search mb-2"
                                               placeholder="🔍 Buscar por email o nombre..." autocomplete="off">
                                        <div class="client-results border border-secondary rounded"
                                             style="max-height: 180px; overflow-y: auto; background: #1a1d23;"></div>
                                        <div class="form-check mt-2">
                                            <input type="checkbox" class="form-check-input" id="login-<?= htmlspecialchars($pid) ?>" checked>
                                            <label class="form-check-label text-light small" for="login-<?= htmlspecialchars($pid) ?>">🔑 Permitir autologin</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-secondary">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="button" class="btn btn-primary btn-sm save-client-btn">Guardar</button>
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

        <?php if ($tab === 'logs'): ?>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- SECCIÓN LOGS DE ACCESO -->
        <!-- ════════════════════════════════════════════════════════════ -->

        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-light small">Últimos <?= count($logs) ?> registros</span>
        </div>

        <?php if (empty($logs)): ?>
            <div class="text-muted text-center py-5">
                <p class="fs-4 mb-1">📋 Sin registros</p>
                <p class="small">Los accesos aparecerán acá cuando haya actividad de login/logout.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Email</th>
                            <th>Acción</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="small text-nowrap"><?= htmlspecialchars($log['created_at']) ?></td>
                            <td><code><?= htmlspecialchars($log['email']) ?></code></td>
                            <td>
                                <?php if ($log['action'] === 'login_success'): ?>
                                    <span class="badge bg-success">✅ Login</span>
                                <?php elseif ($log['action'] === 'login_failed'): ?>
                                    <span class="badge bg-danger">❌ Fallido</span>
                                <?php elseif ($log['action'] === 'logout'): ?>
                                    <span class="badge bg-secondary">🚪 Logout</span>
                                <?php else: ?>
                                    <span class="badge bg-dark"><?= htmlspecialchars($log['action']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><code class="small"><?= htmlspecialchars($log['ip_address']) ?></code></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php endif; ?>

<?php require __DIR__ . '/components/management-footer.php'; ?>
