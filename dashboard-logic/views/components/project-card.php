<?php
/**
 * Tarjeta de proyecto individual (dashboard).
 * Variables disponibles:
 *   $project  — array con dir, slug, name, badge, has_wp, has_node, has_pid, user, password, acept_login
 */
?>
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card border-0 shadow-sm h-100" style="background: #1e2130; border-radius: 10px; overflow: hidden;">
        <!-- Header -->
        <div class="px-3 py-2 d-flex align-items-center gap-2"
             style="background: linear-gradient(135deg, #23283a 0%, #1a1f30 100%); border-bottom: 1px solid #2d323e;">
            <span style="font-size: 1.2rem;">📁</span>
            <h6 class="mb-0 text-truncate flex-grow-1" style="color: #e4e6eb; font-weight: 600; font-size: 0.9rem;">
                <?= htmlspecialchars($project['name']) ?>
            </h6>
            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                <?= $project['badge'] ?>
                <form method="post" action="?delete_project=<?= urlencode($project['dir']) ?>" class="d-inline"
                      onsubmit="return confirm('¿Eliminar <?= htmlspecialchars($project['name']) ?>?')">
                    <button type="submit" class="btn btn-sm p-0"
                            style="color: rgba(248,81,73,0.5); font-size: 0.9rem; line-height: 1;"
                            onmouseover="this.style.color='#f85149'" onmouseout="this.style.color='rgba(248,81,73,0.5)'"
                            title="Eliminar proyecto">🗑</button>
                </form>
            </div>
        </div>
        <!-- Body -->
        <div class="card-body p-3 d-flex flex-column" style="gap: 8px;">
            <div class="d-flex align-items-center gap-2">
                <span style="color: #8b949e; font-size: 0.8rem;">🔗</span>
                <a target="_blank" href="/<?= $project['slug'] ?>"
                   class="text-decoration-none small text-truncate"
                   style="color: #58a6ff;"><?= htmlspecialchars($project['dir']) ?></a>
            </div>

            <?php if ($project['has_wp']): ?>
                <span class="badge align-self-start" style="background: rgba(33,117,155,0.15); color: #58a6ff; font-size: 0.65rem; font-weight: normal;">
                    WordPress
                </span>
                <?php if ($project['acept_login']): ?>
                <div class="d-flex flex-wrap gap-1">
                    <a target="_blank" href="/dashboard-logic/wp-auto-login.php?project=<?= urlencode($project['dir']) ?>"
                       class="btn btn-sm px-2" style="background: rgba(63,185,80,0.15); color: #3fb950; border: 1px solid rgba(63,185,80,0.2); font-size: 0.72rem;">Acceder</a>
                    <a target="_blank" href="/<?= $project['slug'] ?>/wp-admin/"
                       class="btn btn-sm px-2" style="background: rgba(88,166,255,0.1); color: #58a6ff; border: 1px solid rgba(88,166,255,0.2); font-size: 0.72rem;">WP Admin</a>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($project['has_node']): ?>
                <div class="d-flex flex-wrap gap-1">
                    <?php if ($project['has_pid']): ?>
                    <a target="_blank" href="/<?= urlencode($project['dir']) ?>" class="btn btn-sm px-2"
                       style="background: rgba(88,166,255,0.1); color: #58a6ff; border: 1px solid rgba(88,166,255,0.2); font-size: 0.72rem;">🔗 Dev</a>
                    <form method="post" action="?npm_action=stop&dir=<?= urlencode($project['dir']) ?>" class="d-inline"
                          onsubmit="return confirm('¿Detener el servidor?');">
                        <button type="submit" class="btn btn-sm px-2"
                                style="background: rgba(248,81,73,0.1); color: #f85149; border: 1px solid rgba(248,81,73,0.2); font-size: 0.72rem;">⏹ Detener</button>
                    </form>
                    <?php else: ?>
                    <form method="post" action="?npm_action=install&dir=<?= urlencode($project['dir']) ?>" class="d-inline"
                          onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='⏳';">
                        <button type="submit" class="btn btn-sm px-2"
                                style="background: rgba(210,153,34,0.1); color: #d29922; border: 1px solid rgba(210,153,34,0.2); font-size: 0.72rem;">📦 Instalar</button>
                    </form>
                    <form method="post" action="?npm_action=start&dir=<?= urlencode($project['dir']) ?>" class="d-inline"
                          onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='⏳';">
                        <button type="submit" class="btn btn-sm px-2"
                                style="background: rgba(63,185,80,0.1); color: #3fb950; border: 1px solid rgba(63,185,80,0.2); font-size: 0.72rem;">🚀 Iniciar</button>
                    </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($project['user'] || $project['password']): ?>
                <div class="mt-auto pt-2 border-top" style="border-color: #2d323e !important;">
                    <?php if ($project['user']): ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span style="color: #8b949e; font-size: 0.75rem;">👤</span>
                        <code class="small" style="color: #c9d1d9;"><?= htmlspecialchars($project['user']) ?></code>
                    </div>
                    <?php endif; ?>
                    <?php if ($project['password']): ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span style="color: #8b949e; font-size: 0.75rem;">🔑</span>
                        <code class="small" style="color: #c9d1d9;"><?= htmlspecialchars($project['password']) ?></code>
                    </div>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm w-100 copy-creds-btn py-1"
                            style="background: rgba(255,255,255,0.05); color: #8b949e; border: 1px solid #30363d; font-size: 0.72rem;"
                            data-cred-url="<?= htmlspecialchars($project['dir']) ?>"
                            data-cred-user="<?= htmlspecialchars($project['user'] ?? '') ?>"
                            data-cred-pass="<?= htmlspecialchars($project['password'] ?? '') ?>"
                            data-cred-name="<?= htmlspecialchars($project['name']) ?>">
                        📋 Copiar credenciales
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
