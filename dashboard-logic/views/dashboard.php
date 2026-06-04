<?php
/**
 * Dashboard autenticado.
 * Variables disponibles:
 *   $projects      — array de proyectos (list_projects)
 *   $has_projects  — bool
 *   $script_name   — string
 */
?>
<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">
        Dev Dashboard <small class="text-muted ms-2"><?= gethostname() ?></small>
    </span>
    <a href="?logout=1" class="btn btn-outline-light btn-sm logout-link">Cerrar sesión</a>
</nav>

<section class="py-4 container-fluid" style="min-height: 100vh; background-color: var(--bs-gray-600);">
    <div class="accordion accordion-flush" id="accordionDashboard">

        <!-- ─── Server Info ──────────────────────────────────────────────── -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button fw-bolder display-6 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#flushServerInfo">
                    Información del servidor
                </button>
            </h2>
            <div id="flushServerInfo" class="accordion-collapse collapse" data-bs-parent="#accordionDashboard">
                <div class="accordion-body">
                    <div class="row g-3 justify-content-center">
                        <?php require __DIR__ . '/components/server-info.php'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Proyectos ────────────────────────────────────────────────── -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button fw-bolder display-6" type="button"
                        data-bs-toggle="collapse" data-bs-target="#flushProyectos">
                    Lista de proyectos
                </button>
            </h2>
            <div id="flushProyectos" class="accordion-collapse collapse show" data-bs-parent="#accordionDashboard">
                <div class="accordion-body">
                    <div class="row g-3 justify-content-center">

                        <?php foreach ($projects as $project): ?>
                            <?php require __DIR__ . '/components/project-card.php'; ?>
                        <?php endforeach; ?>

                        <?php if (!$has_projects): ?>
                            <div class="col-12 text-center text-white-50 py-5">
                                <p class="fs-4">No hay proyectos con <code>user-data.txt</code></p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
