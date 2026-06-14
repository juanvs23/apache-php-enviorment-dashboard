<?php
/**
 * Campos de formulario de usuario (create + edit).
 *
 * Variables esperadas:
 *   $user     — array|null  Datos del usuario (null = crear nuevo)
 *   $levels   — array       Lista de niveles
 *   $prefix   — string      Prefijo para IDs (ej: 'edit-', 'create-', 'tab-')
 *   $showPass — bool        Mostrar campo de contraseña
 */
$user = $user ?? null;
$prefix = $prefix ?? '';
$showPass = $showPass ?? true;
$isEdit = $user !== null;
?>
<div class="mb-3">
    <label class="form-label text-light">Email</label>
    <input type="email" name="email" class="form-control" required
           value="<?= htmlspecialchars($user['email'] ?? '') ?>">
</div>
<div class="mb-3">
    <label class="form-label text-light">Nombre</label>
    <input type="text" name="name" class="form-control" placeholder="Opcional"
           value="<?= htmlspecialchars($user['name'] ?? '') ?>">
</div>
<?php if ($showPass): ?>
<div class="mb-3">
    <label class="form-label text-light"><?= $isEdit ? 'Nueva contraseña' : 'Contraseña' ?></label>
    <div class="position-relative">
        <input type="password" name="password" id="<?= $prefix ?>password"
               class="form-control pe-5" <?= $isEdit ? 'placeholder="Vacío = sin cambios"' : 'required' ?>>
        <button type="button"
                class="btn btn-link btn-sm position-absolute end-0 top-50 translate-middle-y me-1 text-decoration-none"
                onclick="togglePassword('<?= $prefix ?>password', this)" tabindex="-1"
                aria-label="Mostrar u ocultar contraseña">Mostrar</button>
    </div>
</div>
<?php endif; ?>
<div class="mb-3">
    <label class="form-label text-light">Nivel</label>
    <select name="level" class="form-select" required>
        <?php foreach ($levels as $l): ?>
        <option value="<?= htmlspecialchars($l['levelsID']) ?>"
            <?= $isEdit && $l['levelsID'] === ($user['level'] ?? '') ? 'selected' : '' ?>>
            <?= htmlspecialchars($l['level_name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>
