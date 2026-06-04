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
            <p class="card-text mb-0">
                <a target="_blank" href="?phpinfo=1" class="btn btn-sm btn-outline-info me-1">phpinfo()</a>
                <a target="_blank" href="./phpmyadmin/" class="btn btn-sm btn-outline-warning">phpMyAdmin</a>
            </p>
        </div>
    </div>
</div>
