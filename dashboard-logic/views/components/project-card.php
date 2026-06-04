<?php
/**
 * Tarjeta de proyecto individual.
 * Variables disponibles:
 *   $project  — array con dir, slug, name, badge, has_wp, card_style, user, password
 */
?>
<div class="col-12 col-md-6 col-lg-4 col-xl-3">
    <div class="card h-100 project-card <?= $project['card_style'] ?>">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bolder"><?= $project['name'] ?></h5>
            <?= $project['badge'] ?>
        </div>
        <div class="card-body">
            <p class="card-text mb-1">
                <b>Enlace: </b>
                <a target="_blank" href="/<?= $project['slug'] ?>" class="text-decoration-none"><?= $project['dir'] ?></a>

                <?php if ($project['has_wp']): ?>
                    <br><small class="text-primary">WordPress detectado</small>
                    <a target="_blank" href="/dashboard-logic/wp-auto-login.php?project=<?= urlencode($project['dir']) ?>"
                       class="btn btn-sm btn-success mt-1">Acceder</a>
                    <a target="_blank" href="/<?= $project['slug'] ?>/wp-admin/"
                       class="btn btn-sm btn-outline-primary mt-1">WP Admin</a>
                <?php endif; ?>
            </p>

            <?php if ($project['user']): ?>
                <p class="mb-1"><b>Usuario: </b><code><?= $project['user'] ?></code></p>
            <?php endif; ?>

            <?php if ($project['password']): ?>
                <p class="mb-0"><b>Contraseña: </b><code><?= $project['password'] ?></code></p>
            <?php endif; ?>
        </div>
    </div>
</div>
