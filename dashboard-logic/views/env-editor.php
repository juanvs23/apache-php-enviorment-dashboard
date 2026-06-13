<?php
/**
 * Editor de .env — solo admin autenticado.
 *
 * Variables disponibles:
 *   $envContent — string contenido actual del .env
 *   $flash      — string tipo de mensaje (success/danger)
 *   $flashMsg   — string mensaje
 */
$flash = $_GET['flash'] ?? '';
$flashMsg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor .env — Dev Dashboard</title>
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <style>
        body {
            background: #1a1d23;
            color: #e4e6eb;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        }
        .env-editor {
            font-family: 'Fira Code', 'Cascadia Code', 'JetBrains Mono', 'Consolas', monospace;
            font-size: 0.85rem;
            line-height: 1.6;
            background: #0d1117;
            color: #c9d1d9;
            border: 1px solid #30363d;
            border-radius: 6px;
            min-height: 60vh;
            tab-size: 4;
        }
        .env-editor:focus {
            border-color: #58a6ff;
            box-shadow: 0 0 0 2px rgba(88, 166, 255, 0.2);
            outline: none;
            color: #e4e6eb;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">⚙️ Editor de .env</span>
    <div class="d-flex gap-2">
        <a href="/" class="btn btn-outline-light btn-sm">Volver al Dashboard</a>
    </div>
</nav>

<div class="container py-4" style="max-width: 900px;">

    <?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flashMsg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="alert alert-warning small mb-3">
        ⚠️ <strong>Cuidado:</strong> Un error de sintaxis puede romper el dashboard.
        Se crea un backup automático antes de cada guardado (<code>.env.bak.AAAAMMDD-HHMMSS</code>).
        Solo se muestran las primeras 200 líneas. Si tu .env es más largo, editalo por SSH.
    </div>

    <form method="post" action="?edit_env=1" onsubmit="return confirm('¿Guardar cambios en .env? Se creará un backup automático.')">
        <textarea name="env_content" class="form-control env-editor p-3" spellcheck="false"
                  style="white-space: pre; overflow-wrap: normal; overflow-x: auto;"><?= htmlspecialchars($envContent) ?></textarea>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-secondary">
                <?= substr_count($envContent, "\n") + 1 ?> líneas —
                Último backup: <?php
                    $backups = glob(__DIR__ . '/../../.env.bak.*');
                    if ($backups) {
                        $latest = end($backups);
                        echo htmlspecialchars(basename($latest));
                    } else {
                        echo 'ninguno';
                    }
                ?>
            </small>
            <div class="d-flex gap-2">
                <a href="/" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">💾 Guardar .env</button>
            </div>
        </div>
    </form>

</div>

<script src="./assets/js/bootstrap.min.js"></script>
</body>
</html>
