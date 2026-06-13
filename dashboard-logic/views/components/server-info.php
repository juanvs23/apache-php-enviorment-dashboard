<?php
/**
 * Tarjetas de información del servidor.
 * Variables disponibles: ninguna (funciones inline).
 */
?>
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card h-100 border-0 shadow-sm" style="background: #2d323e; border-radius: 12px;">
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="font-size: 1.2rem;">🐘</span>
                <h6 class="mb-0 fw-bold" style="color: #e4e6eb;">PHP</h6>
            </div>
            <div class="small" style="color: #8b949e;">
                <div class="d-flex justify-content-between py-1">
                    <span>Versión</span>
                    <code style="color: #58a6ff;"><?= phpversion() ?></code>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Memoria</span>
                    <code style="color: #58a6ff;"><?= ini_get('memory_limit') ?></code>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Upload</span>
                    <code style="color: #58a6ff;"><?= ini_get('upload_max_filesize') ?></code>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card h-100 border-0 shadow-sm" style="background: #2d323e; border-radius: 12px;">
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="font-size: 1.2rem;">🌐</span>
                <h6 class="mb-0 fw-bold" style="color: #e4e6eb;">Servidor</h6>
            </div>
            <div class="small" style="color: #8b949e;">
                <div class="d-flex justify-content-between py-1">
                    <span>Software</span>
                    <code style="color: #58a6ff;"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></code>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Root</span>
                    <code class="text-truncate" style="color: #58a6ff; max-width: 140px;"><?= $_SERVER['DOCUMENT_ROOT'] ?></code>
                </div>
                <div class="py-1">
                    <a target="_blank" href="./phpmyadmin/" style="color: #d29922;">📊 phpMyAdmin</a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card h-100 border-0 shadow-sm" style="background: #2d323e; border-radius: 12px;">
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="font-size: 1.2rem;">💻</span>
                <h6 class="mb-0 fw-bold" style="color: #e4e6eb;">Sistema</h6>
            </div>
            <div class="small" style="color: #8b949e;">
                <div class="d-flex justify-content-between py-1">
                    <span>SO</span>
                    <code style="color: #58a6ff;"><?php
                        $os = php_uname('s');
                        if (str_contains($os, 'Linux'))   echo 'Linux';
                        elseif (str_contains($os, 'Windows')) echo 'Windows';
                        elseif (str_contains($os, 'Darwin'))  echo 'Mac';
                        else echo 'Desconocido';
                    ?></code>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Host</span>
                    <code style="color: #58a6ff;"><?= php_uname('n') ?></code>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Kernel</span>
                    <code style="color: #58a6ff;"><?= php_uname('r') ?></code>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Arq</span>
                    <code style="color: #58a6ff;"><?= php_uname('m') ?></code>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card h-100 border-0 shadow-sm" style="background: #2d323e; border-radius: 12px;">
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="font-size: 1.2rem;">💾</span>
                <h6 class="mb-0 fw-bold" style="color: #e4e6eb;">Disco</h6>
            </div>
            <div class="small" style="color: #8b949e;">
                <?php
                $diskPath = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;
                $free  = disk_free_space($diskPath);
                $total = disk_total_space($diskPath);
                $used  = $total - $free;
                $pct   = $total > 0 ? round($used / $total * 100, 1) : 0;
                $diskLabel = $total > 0 ? round($total / 1024 / 1024 / 1024) . ' GB' : 'N/A';
                ?><div class="progress mb-2" style="height: 8px; background: #1a1d23;">
                    <div class="progress-bar bg-<?= $pct > 80 ? 'danger' : ($pct > 60 ? 'warning' : 'success') ?>"
                         style="width: <?= $pct ?>%; border-radius: 4px;"></div>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Total</span>
                    <code style="color: #58a6ff;"><?= $diskLabel ?></code>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Usado</span>
                    <code style="color: #58a6ff;"><?= round($used / 1024 / 1024 / 1024, 1) ?> GB</code>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span>Libre</span>
                    <code style="color: #58a6ff;"><?= round($free / 1024 / 1024 / 1024, 1) ?> GB</code>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card h-100 border-0 shadow-sm" style="background: #2d323e; border-radius: 12px;">
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="font-size: 1.2rem;">👥</span>
                <h6 class="mb-0 fw-bold" style="color: #e4e6eb;">Usuarios</h6>
            </div>
            <p class="card-text mb-0">
                <a href="/?users=1" class="btn btn-sm btn-outline-success w-100">Gestionar Usuarios</a>
            </p>
        </div>
    </div>
</div>
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card h-100 border-0 shadow-sm" style="background: #2d323e; border-radius: 12px;">
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span style="font-size: 1.2rem;">⚡</span>
                <h6 class="mb-0 fw-bold" style="color: #e4e6eb;">Acciones</h6>
            </div>

            <?php
            // ── Detección de servicios vía ServiceDetector ────────────
            $detector = \Dashboard\Presentation\ServiceContainer::get(\Dashboard\Infrastructure\System\ServiceDetector::class);
            $pg_alive   = $detector->isPostgreSQLAlive();
            $mysql_alive = $detector->isMySQLAlive();
            $pgadmin_ok  = $detector->isPgAdmin4Available();
            $pma_ok      = $detector->isPhpMyAdminAvailable();
            $pgadmin_url = '/pgadmin4/';
            $pma_url     = $_ENV['PMA_URL'] ?: '/phpmyadmin/';

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
            <div class="d-flex flex-wrap gap-1 mb-2">
                <span class="badge bg-<?= $pg_alive ? 'success' : 'secondary' ?> bg-opacity-25"
                      style="color: <?= $pg_alive ? '#3fb950' : '#8b949e' ?>;" data-service="postgresql">
                    PostgreSQL
                </span>
                <span class="badge bg-<?= $mysql_alive ? 'success' : 'secondary' ?> bg-opacity-25"
                      style="color: <?= $mysql_alive ? '#3fb950' : '#8b949e' ?>;" data-service="mysql">
                    MySQL
                </span>
                <span class="badge bg-<?= $pgadmin_ok ? 'success' : 'secondary' ?> bg-opacity-25"
                      style="color: <?= $pgadmin_ok ? '#3fb950' : '#8b949e' ?>;" data-service="pgadmin4">
                    pgAdmin4
                </span>
                <span class="badge bg-<?= $pma_ok ? 'success' : 'secondary' ?> bg-opacity-25"
                      style="color: <?= $pma_ok ? '#3fb950' : '#8b949e' ?>;" data-service="phpmyadmin">
                    phpMyAdmin
                </span>
            </div>

            <hr class="my-2 border-secondary">

            <!-- ── Acceso rápido ── -->
            <div class="d-flex flex-wrap gap-1 mb-2">
                <a href="?phpinfo=1" class="btn btn-sm btn-outline-info">phpinfo()</a>
                <?php if ($pma_ok): ?>
                    <a target="_blank" href="<?= $pma_url ?>" class="btn btn-sm btn-outline-warning">phpMyAdmin</a>
                <?php else: ?>
                    <span class="btn btn-sm btn-outline-secondary disabled">phpMyAdmin</span>
                <?php endif; ?>
                <a target="_blank" href="<?= $pgadmin_url ?>"
                   class="btn btn-sm <?= $pgadmin_ok ? 'btn-outline-success' : 'btn-outline-secondary disabled' ?>">
                    pgAdmin4
                </a>
            </div>

            <hr class="my-2 border-secondary">

            <!-- ── Claves de acceso ── -->
            <div class="small" style="color: #8b949e;">
                <span class="fw-semibold small" style="color: #e4e6eb;">Claves de acceso</span>
                <?php foreach ($_claves as $_servicio => $_credencial): ?>
                    <div class="d-flex justify-content-between py-1">
                        <span><?= $_servicio ?></span>
                        <?php if ($_credencial): ?>
                            <code class="small" style="color: #58a6ff;"><?= htmlspecialchars($_credencial) ?></code>
                        <?php else: ?>
                            <span class="fst-italic" style="color: #d29922;">Falta</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
