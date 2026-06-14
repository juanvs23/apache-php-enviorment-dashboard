<?php
/**
 * Tarjetas de información del servidor.
 */
?>
<!-- PHP -->
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card border-0 shadow-sm h-100" style="background: #1e2130; border-radius: 10px; overflow: hidden;">
        <div class="px-3 py-2 d-flex align-items-center gap-2"
             style="background: linear-gradient(135deg, #23283a 0%, #1a1f30 100%); border-bottom: 1px solid #2d323e;">
            <span style="font-size: 1rem;">🐘</span>
            <span style="color: #e4e6eb; font-weight: 600; font-size: 0.8rem;">PHP</span>
        </div>
        <div class="card-body p-2" style="font-size: 0.78rem;">
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">Versión</span>
                <code style="color: #58a6ff;"><?= phpversion() ?></code>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">Memoria</span>
                <code style="color: #58a6ff;"><?= ini_get('memory_limit') ?></code>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">Upload</span>
                <code style="color: #58a6ff;"><?= ini_get('upload_max_filesize') ?></code>
            </div>
        </div>
    </div>
</div>

<!-- Servidor -->
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card border-0 shadow-sm h-100" style="background: #1e2130; border-radius: 10px; overflow: hidden;">
        <div class="px-3 py-2 d-flex align-items-center gap-2"
             style="background: linear-gradient(135deg, #23283a 0%, #1a1f30 100%); border-bottom: 1px solid #2d323e;">
            <span style="font-size: 1rem;">🌐</span>
            <span style="color: #e4e6eb; font-weight: 600; font-size: 0.8rem;">Servidor</span>
        </div>
        <div class="card-body p-2" style="font-size: 0.78rem;">
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">Software</span>
                <code style="color: #58a6ff;"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></code>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">Root</span>
                <code class="text-truncate" style="color: #58a6ff; max-width: 140px;"><?= $_SERVER['DOCUMENT_ROOT'] ?></code>
            </div>
            <div class="py-1">
                <a target="_blank" href="./phpmyadmin/" style="color: #d29922; font-size: 0.75rem;">📊 phpMyAdmin</a>
            </div>
        </div>
    </div>
</div>

<!-- Sistema -->
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card border-0 shadow-sm h-100" style="background: #1e2130; border-radius: 10px; overflow: hidden;">
        <div class="px-3 py-2 d-flex align-items-center gap-2"
             style="background: linear-gradient(135deg, #23283a 0%, #1a1f30 100%); border-bottom: 1px solid #2d323e;">
            <span style="font-size: 1rem;">💻</span>
            <span style="color: #e4e6eb; font-weight: 600; font-size: 0.8rem;">Sistema</span>
        </div>
        <div class="card-body p-2" style="font-size: 0.78rem;">
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">SO</span>
                <code style="color: #58a6ff;"><?php
                    $os = php_uname('s');
                    if (str_contains($os, 'Linux'))   echo 'Linux';
                    elseif (str_contains($os, 'Windows')) echo 'Windows';
                    elseif (str_contains($os, 'Darwin'))  echo 'Mac';
                    else echo 'Desconocido';
                ?></code>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">Host</span>
                <code style="color: #58a6ff;"><?= php_uname('n') ?></code>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">Kernel</span>
                <code style="color: #58a6ff;"><?= php_uname('r') ?></code>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">Arq</span>
                <code style="color: #58a6ff;"><?= php_uname('m') ?></code>
            </div>
        </div>
    </div>
</div>

<!-- Disco -->
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card border-0 shadow-sm h-100" style="background: #1e2130; border-radius: 10px; overflow: hidden;">
        <div class="px-3 py-2 d-flex align-items-center gap-2"
             style="background: linear-gradient(135deg, #23283a 0%, #1a1f30 100%); border-bottom: 1px solid #2d323e;">
            <span style="font-size: 1rem;">💾</span>
            <span style="color: #e4e6eb; font-weight: 600; font-size: 0.8rem;">Disco</span>
        </div>
        <div class="card-body p-2" style="font-size: 0.78rem;">
            <?php
            $diskPath = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;
            $free  = disk_free_space($diskPath);
            $total = disk_total_space($diskPath);
            $used  = $total - $free;
            $pct   = $total > 0 ? round($used / $total * 100, 1) : 0;
            $diskLabel = $total > 0 ? round($total / 1024 / 1024 / 1024) . ' GB' : 'N/A';
            ?>
            <div class="progress mb-2" style="height: 15px; background: #16181d; border-radius: 4px;">
                <div class="progress-bar" style="width: <?= $pct ?>%; background: <?= $pct > 80 ? '#f85149' : ($pct > 60 ? '#d29922' : '#3fb950') ?>; border-radius: 4px;"></div>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">Total</span>
                <code style="color: #58a6ff;"><?= $diskLabel ?></code>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">Usado</span>
                <code style="color: #58a6ff;"><?= round($used / 1024 / 1024 / 1024, 1) ?> GB</code>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span style="color: #8b949e;">Libre</span>
                <code style="color: #58a6ff;"><?= round($free / 1024 / 1024 / 1024, 1) ?> GB</code>
            </div>
        </div>
    </div>
</div>

<!-- Usuarios -->
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card border-0 shadow-sm h-100" style="background: #1e2130; border-radius: 10px; overflow: hidden;">
        <div class="px-3 py-2 d-flex align-items-center gap-2"
             style="background: linear-gradient(135deg, #23283a 0%, #1a1f30 100%); border-bottom: 1px solid #2d323e;">
            <span style="font-size: 1rem;">👥</span>
            <span style="color: #e4e6eb; font-weight: 600; font-size: 0.8rem;">Usuarios</span>
        </div>
        <div class="card-body p-3 d-flex align-items-center justify-content-center">
            <a href="/?users=1" class="btn btn-sm" style="background: rgba(63,185,80,0.1); color: #3fb950; border: 1px solid rgba(63,185,80,0.2);">Gestionar Usuarios</a>
        </div>
    </div>
</div>

<!-- Acciones -->
<div class="col-12 col-md-6 col-lg-4 col-xl-3 mb-3">
    <div class="card border-0 shadow-sm h-100" style="background: #1e2130; border-radius: 10px; overflow: hidden;">
        <div class="px-3 py-2 d-flex align-items-center gap-2"
             style="background: linear-gradient(135deg, #23283a 0%, #1a1f30 100%); border-bottom: 1px solid #2d323e;">
            <span style="font-size: 1rem;">⚡</span>
            <span style="color: #e4e6eb; font-weight: 600; font-size: 0.8rem;">Acciones</span>
        </div>
        <div class="card-body p-2" style="font-size: 0.78rem;">
            <?php
            $detector = \Dashboard\Presentation\ServiceContainer::get(\Dashboard\Infrastructure\System\ServiceDetector::class);
            $pg_alive   = $detector->isPostgreSQLAlive();
            $mysql_alive = $detector->isMySQLAlive();
            $pgadmin_ok  = $detector->isPgAdmin4Available();
            $pma_ok      = $detector->isPhpMyAdminAvailable();
            $pgadmin_url = '/pgadmin4/';
            $pma_url     = $_ENV['PMA_URL'] ?: '/phpmyadmin/';

            $_usr_pg  = $_ENV['DB_USER'] ?? null;
            $_pwd_pg  = $_ENV['DB_PASS'] ?? null;
            $_usr_my  = $_ENV['MYSQL_USER'] ?? null;
            $_pwd_my  = $_ENV['MYSQL_PASS'] ?? null;
            $_usr_pma = $_ENV['PMA_USER'] ?? null;
            $_pwd_pma = $_ENV['PMA_PASS'] ?? null;
            ?>
            <div class="d-flex flex-wrap gap-1 mb-2">
                <span class="badge" data-service="postgresql"
                      style="background: <?= $pg_alive ? 'rgba(63,185,80,0.15)' : 'rgba(139,148,158,0.15)' ?>; color: <?= $pg_alive ? '#3fb950' : '#8b949e' ?>; font-size: 0.65rem;">PostgreSQL</span>
                <span class="badge" data-service="mysql"
                      style="background: <?= $mysql_alive ? 'rgba(63,185,80,0.15)' : 'rgba(139,148,158,0.15)' ?>; color: <?= $mysql_alive ? '#3fb950' : '#8b949e' ?>; font-size: 0.65rem;">MySQL</span>
                <span class="badge" data-service="pgadmin4"
                      style="background: <?= $pgadmin_ok ? 'rgba(63,185,80,0.15)' : 'rgba(139,148,158,0.15)' ?>; color: <?= $pgadmin_ok ? '#3fb950' : '#8b949e' ?>; font-size: 0.65rem;">pgAdmin4</span>
                <span class="badge" data-service="phpmyadmin"
                      style="background: <?= $pma_ok ? 'rgba(63,185,80,0.15)' : 'rgba(139,148,158,0.15)' ?>; color: <?= $pma_ok ? '#3fb950' : '#8b949e' ?>; font-size: 0.65rem;">phpMyAdmin</span>
            </div>
            <hr style="border-color: #2d323e; margin: 6px 0;">
            <div class="d-flex flex-wrap gap-1 mb-2">
                <a href="?phpinfo=1" class="btn btn-sm px-2" style="background: rgba(88,166,255,0.1); color: #58a6ff; border: 1px solid rgba(88,166,255,0.2); font-size: 0.65rem;">phpinfo()</a>
                <?php if ($pma_ok): ?>
                    <a target="_blank" href="<?= $pma_url ?>" class="btn btn-sm px-2" style="background: rgba(210,153,34,0.1); color: #d29922; border: 1px solid rgba(210,153,34,0.2); font-size: 0.65rem;">phpMyAdmin</a>
                <?php endif; ?>
                <a target="_blank" href="<?= $pgadmin_url ?>" class="btn btn-sm px-2"
                   style="background: <?= $pgadmin_ok ? 'rgba(63,185,80,0.1)' : 'rgba(139,148,158,0.05)' ?>; color: <?= $pgadmin_ok ? '#3fb950' : '#8b949e' ?>; border: 1px solid <?= $pgadmin_ok ? 'rgba(63,185,80,0.2)' : 'rgba(139,148,158,0.1)' ?>; font-size: 0.65rem;">pgAdmin4</a>
            </div>
            <hr style="border-color: #2d323e; margin: 6px 0;">
            <div style="font-size: 0.7rem;">
                <?php
                $_claves = [
                    'pgAdmin4'   => $_ENV['PGA_EMAIL'] ? $_ENV['PGA_EMAIL'] . ($_ENV['PGA_PASS'] ? ' / ' . $_ENV['PGA_PASS'] : '') : null,
                    'PostgreSQL' => $_usr_pg ? $_usr_pg . ' / ' . ($_pwd_pg ?? '') : null,
                    'MySQL'      => $_usr_my ? $_usr_my . ' / ' . ($_pwd_my ?? '') : null,
                    'phpMyAdmin' => $_usr_pma ? $_usr_pma . ' / ' . ($_pwd_pma ?? '') : null,
                ];
                ?>
                <div style="color: #e4e6eb; font-weight: 600; margin-bottom: 2px;">Claves de acceso</div>
                <?php foreach ($_claves as $_servicio => $_credencial): ?>
                    <div class="d-flex justify-content-between py-1">
                        <span style="color: #8b949e;"><?= $_servicio ?></span>
                        <?php if ($_credencial): ?>
                            <code style="color: #58a6ff; font-size: 0.65rem;"><?= htmlspecialchars($_credencial) ?></code>
                        <?php else: ?>
                            <span style="color: #d29922; font-style: italic;">Falta</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
