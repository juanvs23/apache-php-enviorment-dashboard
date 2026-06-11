<?php
/**
 * Dashboard autenticado.
 *
 * Variables disponibles:
 *   $projects       — array de proyectos
 *   $has_projects   — bool
 *   $script_name    — string
 *   $authUser       — array usuario autenticado
 *   $isAdmin        — bool si es admin (level_type=0)
 *   $canManageUsers — bool permiso users.manage
 *   $canViewServer  — bool permiso server.view
 */

// ─── Tipos de proyecto disponibles para crear ────────────────────────
$newProjectTypes = [
    [
        'title' => 'HTML en blanco',
        'desc'  => 'Proyecto estático con index.html, CSS y JS. Ideal para landing pages o sitios simples.',
        'modal' => 'modalNewHtml',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" fill="#E44D26"><path d="M1.5 0h21l-1.91 21.563L11.977 24l-8.564-2.438L1.5 0zm7.031 9.75l-.232-2.718 10.059.003.23-2.622L5.412 4.41l.698 8.01h9.126l-.326 3.426-2.91.804-2.955-.81-.188-2.11H6.248l.33 4.171L12 19.351l5.379-1.443.744-8.157H8.531z"/></svg>',
    ],
    [
        'title' => 'WordPress',
        'desc'  => 'WordPress completo con base de datos, temas y plugins preconfigurados.',
        'modal' => 'modalNewWordpress',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="48" height="48" fill="#21759B"><path d="M21.469 6.825c.84 1.537 1.318 3.3 1.318 5.175 0 3.979-2.156 7.456-5.363 9.325l3.295-9.527c.615-1.54.82-2.771.82-3.864 0-.405-.026-.78-.07-1.11m-7.981.105c.647-.03 1.232-.105 1.232-.105.582-.075.514-.93-.067-.899 0 0-1.755.135-2.88.135-1.064 0-2.85-.15-2.85-.15-.585-.03-.661.855-.075.885 0 0 .54.061 1.125.09l1.68 4.605-2.37 7.08L5.354 6.9c.649-.03 1.234-.1 1.234-.1.585-.075.516-.93-.065-.896 0 0-1.746.138-2.874.138-.2 0-.438-.008-.69-.015C4.911 3.15 8.235 1.215 12 1.215c2.809 0 5.365 1.072 7.286 2.833-.046-.003-.091-.009-.141-.009-1.06 0-1.812.923-1.812 1.914 0 .89.513 1.643 1.06 2.531.411.72.89 1.643.89 2.977 0 .915-.354 1.994-.821 3.479l-1.075 3.585-3.9-11.61.001.014zM12 22.784c-1.059 0-2.081-.153-3.048-.437l3.237-9.406 3.315 9.087c.024.053.05.101.078.149-1.12.393-2.325.609-3.582.609M1.211 12c0-1.564.336-3.05.935-4.39L7.29 21.709C3.694 19.96 1.212 16.271 1.211 12M12 0C5.385 0 0 5.385 0 12s5.385 12 12 12 12-5.385 12-12S18.615 0 12 0"/></svg>',
    ],
];
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

    <?php $flash = $_GET['flash'] ?? ''; $flashMsg = $_GET['msg'] ?? ''; if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flashMsg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabProyectos" type="button">📁 Proyectos</button>
        </li>
        <?php if ($isAdmin): ?>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabNewProject" type="button">🆕 Nuevo Proyecto</button>
        </li>
        <?php endif; ?>
        <?php if ($canViewServer): ?>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabServer" type="button">🖥️ Servidor</button>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content">

        <!-- ─── Proyectos ─────────────────────────────────────────── -->
        <div class="tab-pane fade show active" id="tabProyectos">
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
                <?php foreach ($newProjectTypes as $type): ?>
                    <?php require __DIR__ . '/components/_new-project-card.php'; ?>
                <?php endforeach; ?>
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

<?php require __DIR__ . '/components/modal-create-html.php'; ?>
<?php require __DIR__ . '/components/modal-create-laravel.php'; ?>
<?php require __DIR__ . '/components/modal-create-wordpress.php'; ?>
