<?php
/**
 * Dashboard autenticado.
 * Variables disponibles:
 *   $projects       — array de proyectos
 *   $has_projects   — bool
 *   $script_name    — string
 *   $authUser       — array usuario autenticado
 *   $isAdmin        — bool si es admin (level_type=0)
 *   $canManageUsers — bool permiso users.manage
 *   $canViewServer  — bool permiso server.view
 */
?>
<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1 d-flex align-items-center gap-2">
        <span class="fs-5">⚡</span>
        Dev Dashboard
        <small class="text-muted fw-normal fs-6"><?= gethostname() ?></small>
    </span>
    <div class="d-flex align-items-center gap-2">
        <?php if ($authUser): ?>
            <span class="text-light small me-2">
                <?= htmlspecialchars($authUser['name'] ?? $authUser['email']) ?>
                <span class="badge bg-<?= $isAdmin ? 'danger' : 'primary' ?> ms-1">
                    <?= htmlspecialchars($authUser['level_name']) ?>
                </span>
            </span>
        <?php endif; ?>
        <a href="/?profile=1" class="btn btn-outline-info btn-sm">👤 Perfil</a>
        <?php if ($canManageUsers): ?>
            <a href="/?users=1" class="btn btn-outline-success btn-sm">👥 Usuarios</a>
        <?php endif; ?>
        <a href="?logout=1" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
    </div>
</nav>

<section class="py-4 container-fluid" style="min-height: calc(100vh - 56px); background: linear-gradient(135deg, #1a1d23 0%, #2d323e 100%);">

    <!-- ─── Tabs ──────────────────────────────────────────────────── -->
    <?php if ($canViewServer): ?>
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabProyectos"
                    type="button">📁 Proyectos</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabServer"
                    type="button">🖥️ Servidor</button>
        </li>
    </ul>
    <?php endif; ?>

    <div class="tab-content">

        <!-- ─── Proyectos ─────────────────────────────────────────── -->
        <div class="tab-pane fade show active"
             id="tabProyectos">

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- CREAR PROYECTO -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <div class="mb-4">
                <h5 class="text-light mb-3 d-flex align-items-center gap-2">
                    <span>🆕</span> Crear Proyecto
                </h5>
                <div class="row g-3">
                    <!-- HTML en blanco -->
                    <div class="col-12 col-md-4">
                        <div class="card bg-dark border-secondary h-100 shadow-sm"
                             role="button" data-bs-toggle="modal" data-bs-target="#modalNewHtml"
                             style="cursor: pointer; transition: transform .15s, box-shadow .15s;"
                             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,.4)';"
                             onmouseout="this.style.transform=''; this.style.boxShadow='';">
                            <div class="card-body text-center py-4">
                                <div class="mb-3" style="font-size: 3rem;">🌐</div>
                                <h6 class="card-title text-light mb-2">HTML en blanco</h6>
                                <p class="card-text text-secondary small mb-0">
                                    Proyecto estático con index.html, CSS y JS. Ideal para landing pages o sitios simples.
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Laravel -->
                    <div class="col-12 col-md-4">
                        <div class="card bg-dark border-secondary h-100 shadow-sm"
                             role="button" data-bs-toggle="modal" data-bs-target="#modalNewLaravel"
                             style="cursor: pointer; transition: transform .15s, box-shadow .15s;"
                             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,.4)';"
                             onmouseout="this.style.transform=''; this.style.boxShadow='';">
                            <div class="card-body text-center py-4">
                                <div class="mb-3" style="font-size: 3rem;">🔺</div>
                                <h6 class="card-title text-light mb-2">Laravel</h6>
                                <p class="card-text text-secondary small mb-0">
                                    Proyecto Laravel con PHP, Composer y base de datos MySQL/MariaDB.
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- WordPress -->
                    <div class="col-12 col-md-4">
                        <div class="card bg-dark border-secondary h-100 shadow-sm"
                             role="button" data-bs-toggle="modal" data-bs-target="#modalNewWordpress"
                             style="cursor: pointer; transition: transform .15s, box-shadow .15s;"
                             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,.4)';"
                             onmouseout="this.style.transform=''; this.style.boxShadow='';">
                            <div class="card-body text-center py-4">
                                <div class="mb-3" style="font-size: 3rem;">📝</div>
                                <h6 class="card-title text-light mb-2">WordPress</h6>
                                <p class="card-text text-secondary small mb-0">
                                    WordPress completo con base de datos, temas y plugins preconfigurados.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════ -->
            <!-- LISTA DE PROYECTOS -->
            <!-- ═══════════════════════════════════════════════════════ -->
            <h5 class="text-light mb-3 d-flex align-items-center gap-2">
                <span>📁</span> Mis Proyectos
            </h5>
            <div class="row g-3">
                <?php foreach ($projects as $project): ?>
                    <?php require __DIR__ . '/components/project-card.php'; ?>
                <?php endforeach; ?>

                <?php if (!$has_projects): ?>
                    <div class="col-12 text-center text-white-50 py-5">
                        <p class="fs-4 mb-1">📂 No hay proyectos</p>
                        <p class="small">Ningún directorio contiene <code>user-data.txt</code></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── Server Info ───────────────────────────────────────── -->
        <?php if ($canViewServer): ?>
        <div class="tab-pane fade" id="tabServer">
            <div class="row g-3 justify-content-center">
                <?php require __DIR__ . '/components/server-info.php'; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════ -->
<!-- MODAL: HTML en blanco -->
<!-- ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNewHtml" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">🌐 Crear Proyecto HTML</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/?create_project=html">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre del proyecto</label>
                        <input type="text" name="project_name" class="form-control bg-dark text-light border-secondary"
                               placeholder="ej: mi-landing-page" required>
                        <small class="text-secondary">Nombre descriptivo para el dashboard.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre del directorio</label>
                        <input type="text" name="directory" class="form-control bg-dark text-light border-secondary"
                               placeholder="ej: landing-page" required>
                        <small class="text-secondary">Se creará en <code>/mnt/vol/projects/apache/</code>.</small>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <strong>Se instalará:</strong> <code>index.html</code>, <code>css/style.css</code>,
                        <code>js/app.js</code> y <code>user-data.txt</code> con datos para el dashboard.
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Proyecto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════ -->
<!-- MODAL: Laravel -->
<!-- ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNewLaravel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">🔺 Crear Proyecto Laravel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/?create_project=laravel">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre del proyecto</label>
                        <input type="text" name="project_name" class="form-control bg-dark text-light border-secondary"
                               placeholder="ej: Mi Aplicación Laravel" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre del directorio</label>
                        <input type="text" name="directory" class="form-control bg-dark text-light border-secondary"
                               placeholder="ej: my-app" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre de la base de datos</label>
                        <input type="text" name="db_name" class="form-control bg-dark text-light border-secondary"
                               placeholder="ej: laravel_db" required>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <strong>Se instalará:</strong> Laravel vía Composer, <code>.env</code> configurado,
                        migraciones iniciales, y <code>user-data.txt</code> para el dashboard.
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Crear Proyecto Laravel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════ -->
<!-- MODAL: WordPress -->
<!-- ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNewWordpress" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">📝 Crear Proyecto WordPress</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/?create_project=wordpress">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Nombre del proyecto</label>
                            <input type="text" name="project_name" class="form-control bg-dark text-light border-secondary"
                                   placeholder="ej: Mi Blog" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Nombre del directorio</label>
                            <input type="text" name="directory" class="form-control bg-dark text-light border-secondary"
                                   placeholder="ej: mi-blog" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Nombre de la base de datos</label>
                            <input type="text" name="db_name" class="form-control bg-dark text-light border-secondary"
                                   placeholder="ej: wp_blog" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Título del sitio</label>
                            <input type="text" name="site_title" class="form-control bg-dark text-light border-secondary"
                                   placeholder="ej: Mi Blog Personal" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Email del administrador</label>
                            <input type="email" name="admin_email" class="form-control bg-dark text-light border-secondary"
                                   placeholder="admin@admin.com" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Contraseña del admin</label>
                            <div class="position-relative">
                                <input type="password" name="admin_password" id="wpAdminPass"
                                       class="form-control bg-dark text-light border-secondary pe-5"
                                       placeholder="Mínimo 8 caracteres" required>
                                <button type="button"
                                        class="btn btn-link btn-sm position-absolute end-0 top-50 translate-middle-y me-1 text-decoration-none"
                                        onclick="togglePassword('wpAdminPass', this)" tabindex="-1">
                                    Mostrar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info small mt-3 mb-0">
                        <strong>Se instalará:</strong> WordPress vía WP-CLI, base de datos configurada,
                        plugins iniciales, y <code>user-data.txt</code> para el dashboard.
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Proyecto WordPress</button>
                </div>
            </form>
        </div>
    </div>
</div>
