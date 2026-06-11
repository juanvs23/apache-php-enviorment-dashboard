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
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabProyectos"
                    type="button">📁 Proyectos</button>
        </li>
        <?php if ($isAdmin): ?>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabNewProject"
                    type="button">🆕 Nuevo Proyecto</button>
        </li>
        <?php endif; ?>
        <?php if ($canViewServer): ?>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabServer"
                    type="button">🖥️ Servidor</button>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content">

        <!-- ─── Proyectos ─────────────────────────────────────────── -->
        <div class="tab-pane fade show active"
             id="tabProyectos">
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

        <!-- ─── Nuevo Proyecto (solo admin) ───────────────────────── -->
        <?php if ($isAdmin): ?>
        <div class="tab-pane fade" id="tabNewProject">
            <div class="row g-3">
                <!-- HTML en blanco -->
                <div class="col-12 col-md-4">
                    <div class="card bg-dark border-secondary h-100 shadow-sm"
                         role="button" data-bs-toggle="modal" data-bs-target="#modalNewHtml"
                         style="cursor: pointer; transition: transform .15s, box-shadow .15s;"
                         onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,.4)';"
                         onmouseout="this.style.transform=''; this.style.boxShadow='';">
                        <div class="card-body text-center py-4">
                            <div class="mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="48" height="48">
                                    <path fill="#E44D26" d="M9.032 2l10.005 112.093 44.896 12.401 45.02-12.387L118.968 2H9.032z"/>
                                    <path fill="#F16529" d="M64 118.662l36.274-10.055 8.526-95.6H64v105.655z"/>
                                    <path fill="#EBEBEB" d="M64 52.455H45.788L44.53 38.36H64V24.6H29.488l.33 3.692 3.383 37.923H64zm0 35.056l-.06.017-15.327-4.14-.98-10.965H33.97l1.928 21.61 28.042 7.78.06-.017z"/>
                                    <path fill="#FFF" d="M63.952 52.455v13.76h16.948l-1.597 17.85-15.35 4.143v14.32l28.056-7.777.315-3.515 3.214-35.82.335-3.692h-3.64l-28.28-.01zm0-27.857v13.76h33.244l.276-3.092.628-6.978.33-3.69z"/>
                                </svg>
                            </div>
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
                            <div class="mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="48" height="48">
                                    <path fill="#F05340" d="M27.1.7C19 .7 12.3 7.1 12 15.2L3.2 81.8c-.4 3.8.7 7.5 3.2 10.3l46.4 33c3.4 2.4 8 2.4 11.4 0l46.4-33c2.5-1.8 3.7-4.6 3.5-7.5-.1-1-.3-2-.6-2.9L103 21.4c-.7-3.1-3.5-5.4-6.7-5.4l-61.5-.1c-2.5 0-4.7 1.4-5.8 3.5L11.6 52.3c-.9 1.9-.1 4.1 1.8 5L38 72.5c1.3.6 2.8.3 3.8-.8l18-19.6c1-1.1 2.8-.6 3.1.9l5 26.1c.2 1.1.8 2.1 1.8 2.7l12.3 7.9c1.7 1.1 3.9.6 5-.9l18.4-24.7c1.1-1.5.8-3.6-.7-4.7l-9.7-7.5c-1.3-1-3.2-.8-4.2.5l-8.9 12c-.9 1.2-2.7 1.3-3.7.2L70.3 52.8c-.7-.8-.8-1.9-.2-2.8l11.2-15.5c1.3-1.8 4-1.3 4.6.8l4.4 16.7c.3 1.3 1.5 2.2 2.9 2l15-2.5c1.5-.2 2.5-1.6 2.2-3.1l-5.1-26.4c-.3-1.7-1.8-2.9-3.5-2.9l-63.1.1c-2.1.1-3.9 1.5-4.3 3.5L20 73.2c-.5 2.4 1.5 4.6 3.9 4.3l13.3-1.5c1.5-.2 2.8-1.1 3.5-2.5l6.2-13.1c.9-1.9 3.4-1.9 4.3 0l6.2 13.1c.7 1.4 2 2.3 3.5 2.5l26.1 3c1.7.2 3.3-.8 3.8-2.4.3-.9.1-1.8-.4-2.6L74.1 57.2c-.5-.9-1.6-1.4-2.7-1.1l-7.3 1.9c-1.1.3-2.3-.2-2.7-1.3l-3.9-11.7c-.5-1.4-.8-2.9-.8-4.4.1-1.8 1.5-3.3 3.3-3.3l56.8.5c.9 0 1.7-.6 1.9-1.5l2.4-10.3c.5-2.1-1-4.2-3.2-4.2L31.1.5c-.8-.1-1.6.1-2.3.3-.5.1-1.1-.1-1.7-.1z"/>
                                </svg>
                            </div>
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
                            <div class="mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="48" height="48">
                                    <circle cx="64" cy="64" r="60" fill="#21759B"/>
                                    <path fill="#FFF" d="M64 12c-2.5 0-4.9.2-7.3.5l12.5 34.2 3.5 10c.1.3.4.5.7.3.5-.3.7-.9.6-1.5L61.2 24.3C62.1 24.1 63 24 64 24c9.1 0 17.4 3.3 23.8 8.8l-2.1-.1c-3.4 0-5.8 2.9-5.8 6 0 2.8 1.6 5.1 3.3 7.9 1.3 2.3 2.8 5.1 2.8 9.3 0 2.9-.9 6.5-2.6 10.9l-3.4 11.5L64 46.2l-13.1 36.1c.7.3 1.4.5 2.1.7l7.6-21.6 2.7-7.8c.5-1.3-.2-2.7-1.5-3.2s-2.7.2-3.2 1.5L64 59.1l-5.3 15.5c2.1 4.8 5.9 8.7 10.5 11L54.3 37.5l-6.8-19.8c-3.1 2-5.9 4.6-8.2 7.5l7 20.1 4.3 11.7-6 17.5c-7.9-4.1-14-12-16.4-21.4l4.1-11.5c1.1-2.8-.4-5.9-3.2-7-2.6-1-5.4.1-6.7 2.6l-.4.9c-2.7 6.8-4.1 14.3-4.1 22.1 0 0 0 .1 0 .1 0 17.5 8.7 33.1 22 42.2l-6.2-17c-1.2-3.9-2.4-8.2-1.5-11.4.4-1.3.3-2.7-.4-3.9l-3.8-3.1c-.5-.4-1.2-.3-1.6.2-.4.5-.3 1.2.2 1.6l3.8 3.1c.2.2.2 1.1-.1 1.9-.9 2.9-.8 5.5.1 8.5l6.6 18.9c.2.6.8.9 1.4.8.6-.1 1-.6.9-1.2l-6-17.2c-.9-2.8-1.2-5.8-.3-8.6l3.8-3.2c.3-.3.3-.8 0-1.1-.3-.3-.8-.3-1.1 0l-3.8 3.2c-5 5-12.6 13.2-15.4 14.8-1 .6-2.3.2-2.9-.8-.6-1-.2-2.3.8-2.9 5.1-3 14.8-13.1 14.9-13.1 1.3-1.4 2.7-2.6 4.1-3.6l1.1-3.2c.2-.7.8-1.2 1.5-1.2h.1c.8 0 1.5.6 1.6 1.4l1 5.1 2.3-6.6c.6-1.6.4-3.4-.6-4.7l-5.7-7.5c-.7-.9-.5-2.2.4-2.9.9-.7 2.2-.5 2.9.4l5.6 7.4c.4.5.6 1.4.4 2.1l-2.3 6.5c-.2.6.1 1.3.7 1.5.6.2 1.3-.1 1.5-.7l3.1-9c.3-1-.1-2.1-1-2.6l-1.4-.8c-.6-.4-.8-1.2-.4-1.8.4-.6 1.2-.8 1.8-.4l1.3.8c.6.3 1.3.1 1.7-.5.4-.6.2-1.3-.4-1.7l-1.2-.7c-.4-.3-.5-.9-.2-1.3.3-.4.9-.5 1.3-.2l.3.2c1.7.9 3.2 2.1 4.3 3.6l18.4 11.4c1.9 1.2 3.1 3.3 3.1 5.5 0 4.5-3.6 8.1-8.1 8.1H61c-1.8 0-3.5-.6-4.9-1.7l-2.9.3 1.6 4.6c.2.6.8 1 1.5 1H64c10.8 0 19.6-8.8 19.6-19.6 0-2.2-.6-4.2-1.7-6h.1c3.5 0 6.3-2.8 6.3-6.3 0-3.9-3.8-5.2-7.9-5.2-2.6 0-4.7 1-6.4 1-1.5 0-4.7-1.1-7.3-1.1-5.9 0-10.7 3.5-10.7 9.1 0 4.3 3.2 8.2 8.1 8.2h.5l-2.6 7.4c-.7 2.2-2.6 3.9-5 4.6-6.4 2-11-5.2-17.2-22.9l-3-9.2c-.9-2.7-1.6-5.8-.9-7.5l3.8-3c.3-.3.4-.8.1-1.2-.3-.4-.8-.4-1.2-.1l-3.8 3.1z"/>
                                </svg>
                            </div>
                            <h6 class="card-title text-light mb-2">WordPress</h6>
                            <p class="card-text text-secondary small mb-0">
                                WordPress completo con base de datos, temas y plugins preconfigurados.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
