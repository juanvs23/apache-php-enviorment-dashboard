<?php
/**
 * Tarjetas de información del servidor.
 * Variables disponibles: ninguna (funciones inline).
 */
?>
<div class="col-12 col-md-6 col-lg-4 col-xl-3">
    <div class="card h-100 server-card">
        <div class="card-body">
            <h6 class="card-title">PHP</h6>
            <p class="card-text mb-1">Versión: <code><?= phpversion() ?></code></p>
            <p class="card-text mb-1">Memoria: <code><?= ini_get('memory_limit') ?></code></p>
            <p class="card-text mb-0">Upload max: <code><?= ini_get('upload_max_filesize') ?></code></p>
        </div>
    </div>
</div>
<div class="col-12 col-md-6 col-lg-4 col-xl-3">
    <div class="card h-100 server-card">
        <div class="card-body">
            <h6 class="card-title">Servidor</h6>
            <p class="card-text mb-1"><code><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></code></p>
            <p class="card-text mb-1">Document Root: <code><?= $_SERVER['DOCUMENT_ROOT'] ?></code></p>
            <p class="card-text mb-0"><a target="_blank" href="./phpmyadmin/">phpMyAdmin</a></p>
        </div>
    </div>
</div>
<div class="col-12 col-md-6 col-lg-4 col-xl-3">
    <div class="card h-100 server-card">
        <div class="card-body">
            <h6 class="card-title">Sistema operativo</h6>
            <p class="card-text mb-0">
                <b>SO:</b> <?= get_os() ?><br>
                <b>Host:</b> <?= php_uname('n') ?><br>
                <b>Kernel:</b> <?= php_uname('r') ?><br>
                <b>Arquitectura:</b> <?= php_uname('m') ?>
            </p>
        </div>
    </div>
</div>
<div class="col-12 col-md-6 col-lg-4 col-xl-3">
    <div class="card h-100 server-card">
        <div class="card-body">
            <h6 class="card-title">Disco</h6>
            <p class="card-text mb-0">
                Libre: <code><?= round(disk_free_space('/') / 1024 / 1024 / 1024, 2) ?> GB</code><br>
                Total: <code><?= round(disk_total_space('/') / 1024 / 1024 / 1024, 2) ?> GB</code>
            </p>
        </div>
    </div>
</div>
<div class="col-12 col-md-6 col-lg-4 col-xl-3">
    <div class="card h-100 server-card">
        <div class="card-body">
            <h6 class="card-title">Acciones</h6>

            <?php
            // ── Detección de servicios ────────────────────────────────

            // pgAdmin4
            $pgadmin_url  = '/pgadmin4/';
            $pgadmin_ok   = false;
            $pgadmin_conf = file_exists('/etc/apache2/conf-enabled/pgadmin4.conf')
                         || file_exists('/etc/apache2/conf-available/pgadmin4.conf');
            if ($pgadmin_conf) {
                $ctx = stream_context_create(['http' => ['timeout' => 2, 'method' => 'HEAD']]);
                $headers = @get_headers('http://localhost' . $pgadmin_url, 1, $ctx);
                $pgadmin_ok = $headers && isset($headers[0]) && str_contains($headers[0], '302');
            }

            // PostgreSQL
            exec('pg_isready -q 2>/dev/null', $_, $exit_pg);
            $pg_alive = $exit_pg === 0;

            // phpMyAdmin
            $pma_url  = '/phpmyadmin/';
            $pma_ok   = false;
            $_pma_env = $_ENV['PMA_URL'] ?? '';
            if ($_pma_env !== '') {
                // Si el usuario la definió en .env, activamos el botón directo
                $pma_url = $_pma_env;
                $pma_ok  = true;
            } else {
                // Sino, detección automática
                $_pma_conf = file_exists('/etc/phpmyadmin/apache.conf')
                          || file_exists('/etc/apache2/conf-enabled/phpmyadmin.conf')
                          || file_exists('/etc/apache2/conf-available/phpmyadmin.conf');
                if ($_pma_conf) {
                    $ctx = stream_context_create(['http' => ['timeout' => 2, 'method' => 'HEAD']]);
                    $headers = @get_headers('http://localhost' . $pma_url, 1, $ctx);
                    $pma_ok = $headers && isset($headers[0])
                           && (str_contains($headers[0], '200') || str_contains($headers[0], '302'));
                }
            }

            // MySQL / MariaDB
            exec('pgrep mysqld 2>/dev/null', $_, $exit_mysql);
            $mysql_alive = $exit_mysql === 0;

            // ── Claves desde .env ────────────────────────────────────
            $_usr_pg  = $_ENV['DB_USER'] ?? null;
            $_pwd_pg  = $_ENV['DB_PASS'] ?? null;
            $_usr_my  = $_ENV['MYSQL_USER'] ?? null;
            $_pwd_my  = $_ENV['MYSQL_PASS'] ?? null;
            $_usr_pma = $_ENV['PMA_USER'] ?? null;
            $_pwd_pma = $_ENV['PMA_PASS'] ?? null;

            $_claves = [
                'pgAdmin4'   => $_ENV['PGA_EMAIL'] ? $_ENV['PGA_EMAIL'] . ($_ENV['PGA_PASS'] ? ' / ' . $_ENV['PGA_PASS'] : '') : null,
                'PostgreSQL' => $_usr_pg ? $_usr_pg . ' / ' . ($_pwd_pg ?? '') : null,
                'MySQL'      => $_usr_my ? $_usr_my . ' / ' . ($_pwd_my ?? '') : null,
                'phpMyAdmin' => $_usr_pma ? $_usr_pma . ' / ' . ($_pwd_pma ?? '') : null,
            ];
            ?>

            <!-- ── Estado de servicios ── -->
            <p class="card-text mb-2 small">
                <span class="fw-semibold">Estado</span><br>
                <?php if ($pg_alive): ?>
                    <span class="badge bg-success me-1">PostgreSQL</span>
                <?php else: ?>
                    <span class="badge bg-secondary me-1">PostgreSQL</span>
                <?php endif; ?>
                <?php if ($mysql_alive): ?>
                    <span class="badge bg-success me-1">MySQL</span>
                <?php else: ?>
                    <span class="badge bg-secondary me-1">MySQL</span>
                <?php endif; ?>
            </p>

            <hr class="my-2">

            <!-- ── Acceso rápido ── -->
            <p class="card-text mb-2">
                <span class="fw-semibold small">Acceso rápido</span><br>
                <a target="_blank" href="?phpinfo=1" class="btn btn-sm btn-outline-info mt-1">phpinfo()</a>

                <?php if ($pma_ok): ?>
                    <a target="_blank" href="<?= $pma_url ?>"
                       class="btn btn-sm btn-outline-warning mt-1">phpMyAdmin</a>
                <?php else: ?>
                    <span class="btn btn-sm btn-outline-secondary mt-1 disabled">phpMyAdmin</span>
                <?php endif; ?>

                <a target="_blank" href="<?= $pgadmin_url ?>"
                   class="btn btn-sm mt-1 <?= $pgadmin_ok ? 'btn-outline-success' : 'btn-outline-secondary disabled' ?>">
                    pgAdmin4
                </a>
            </p>

            <hr class="my-2">
            <!-- ── Claves de acceso ── -->
            <div class="card-text small">
                <span class="fw-semibold">Claves de acceso</span>
                <dl class="row small text-muted mb-0 mt-1" style="column-gap: 0.25rem;">
                    <?php foreach ($_claves as $_servicio => $_credencial): ?>
                        <dt class="col-4 text-truncate"><?= $_servicio ?></dt>
                        <dd class="col-8 mb-0 text-truncate">
                            <?php if ($_credencial): ?>
                                <code><?= htmlspecialchars($_credencial) ?></code>
                            <?php else: ?>
                                <span class="fst-italic text-warning-emphasis">Falta</span>
                            <?php endif; ?>
                        </dd>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>
    </div>
</div>
