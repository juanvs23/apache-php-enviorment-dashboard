<?php
/**
 * Dashboard autenticado.
 * Variables disponibles:
 *   $projects      — array de proyectos (list_projects)
 *   $has_projects  — bool
 *   $script_name   — string
 *   get_auth_user() — usuario autenticado de auth.php
 */
$__user = get_auth_user();
?>
<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1 d-flex align-items-center gap-2">
        <span class="fs-5">⚡</span>
        Dev Dashboard
        <small class="text-muted fw-normal fs-6"><?= gethostname() ?></small>
    </span>
    <div class="d-flex align-items-center gap-2">
        <?php if ($__user): ?>
            <span class="text-light small me-2">
                <?= htmlspecialchars($__user['name'] ?? $__user['email']) ?>
                <span class="badge bg-<?= $__user['level_type'] == 0 ? 'danger' : 'primary' ?> ms-1">
                    <?= htmlspecialchars($__user['level_name']) ?>
                </span>
            </span>
        <?php endif; ?>
        <a href="/?profile=1" class="btn btn-outline-info btn-sm">👤 Perfil</a>
        <?php if ($__user && $__user['level_type'] === 0): ?>
            <a href="/?users=1" class="btn btn-outline-success btn-sm">👥 Usuarios</a>
        <?php endif; ?>
        <a href="?logout=1" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
    </div>
</nav>

<section class="py-4 container-fluid" style="min-height: calc(100vh - 56px); background: linear-gradient(135deg, #1a1d23 0%, #2d323e 100%);">

    <!-- ─── Tabs ──────────────────────────────────────────────────── -->
    <?php $__auth_user = get_auth_user(); ?>
    <?php if ($__auth_user && $__auth_user['level_type'] === 0): ?>
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
        <?php if ($__auth_user && $__auth_user['level_type'] === 0): ?>
        <div class="tab-pane fade" id="tabServer">
            <div class="row g-3 justify-content-center">
                <?php require __DIR__ . '/components/server-info.php'; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>
