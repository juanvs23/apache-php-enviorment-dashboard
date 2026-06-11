<?php
/**
 * Tarjeta de proyecto individual.
 * Variables disponibles:
 *   $project  — array con dir, slug, name, badge, has_wp, card_style, user, password
 */
?>
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card h-100 border-0 shadow-sm" style="background: #2d323e; border-radius: 12px; overflow: hidden;">
        <div class="card-header d-flex justify-content-between align-items-center border-bottom border-secondary"
             style="background: #23272f; padding: 0.75rem 1rem;">
            <h5 class="card-title mb-0 fw-bold text-truncate" style="color: #e4e6eb;">
                <?= htmlspecialchars($project['name']) ?>
            </h5>
            <?= $project['badge'] ?>
        </div>
        <div class="card-body" style="padding: 1rem;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="text-muted small">🔗</span>
                <a target="_blank" href="/<?= $project['slug'] ?>"
                   class="text-decoration-none small text-truncate"
                   style="color: #58a6ff;">
                    <?= htmlspecialchars($project['dir']) ?>
                </a>
            </div>

            <?php if ($project['has_wp']): ?>
                <div class="d-flex align-items-center gap-1 mb-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary small px-2 py-1">
                        WordPress detectado
                    </span>
                </div>
                <?php if ($project['acept_login']): ?>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <a target="_blank" href="/dashboard-logic/wp-auto-login.php?project=<?= urlencode($project['dir']) ?>"
                       class="btn btn-sm btn-success px-3">Acceder</a>
                    <a target="_blank" href="/<?= $project['slug'] ?>/wp-admin/"
                       class="btn btn-sm btn-outline-primary px-3">WP Admin</a>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($project['has_node']): ?>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <span class="badge bg-warning bg-opacity-10 text-warning small px-2 py-1">📦 Node.js</span>
                    <?php if ($project['has_pid']): ?>
                    <a target="_blank" href="/<?= urlencode($project['dir']) ?>" class="btn btn-sm btn-info px-2">🔗 Dev</a>
                    <form method="post" action="?npm_action=stop&dir=<?= urlencode($project['dir']) ?>" class="d-inline"
                          onsubmit="return confirm('¿Detener el servidor?');">
                        <button type="submit" class="btn btn-sm btn-danger px-2">⏹ Detener</button>
                    </form>
                    <?php else: ?>
                    <form method="post" action="?npm_action=install&dir=<?= urlencode($project['dir']) ?>" class="d-inline"
                          onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='⏳';">
                        <button type="submit" class="btn btn-sm btn-warning px-2">📦 Instalar</button>
                    </form>
                    <form method="post" action="?npm_action=start&dir=<?= urlencode($project['dir']) ?>" class="d-inline"
                          onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='⏳';">
                        <button type="submit" class="btn btn-sm btn-success px-2">🚀 Iniciar</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($project['user']): ?>
                <div class="d-flex align-items-center gap-2 mt-2 pt-2 border-top border-secondary">
                    <span class="text-muted small">👤</span>
                    <code class="small" style="color: #e4e6eb;"><?= htmlspecialchars($project['user']) ?></code>
                </div>
            <?php endif; ?>

            <?php if ($project['password']): ?>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="text-muted small">🔑</span>
                    <code class="small" style="color: #e4e6eb;"><?= htmlspecialchars($project['password']) ?></code>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
