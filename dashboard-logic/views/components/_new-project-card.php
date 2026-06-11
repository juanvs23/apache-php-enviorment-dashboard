<?php
/**
 * Card de tipo de proyecto para la pestaña "Nuevo Proyecto".
 *
 * Variables esperadas:
 *   $type = [
 *       'icon'   => string SVG inline,
 *       'title'  => string nombre del tipo,
 *       'desc'   => string descripción corta,
 *       'modal'  => string ID del modal target (sin #),
 *   ]
 */
?>
<div class="col-12 col-md-5 col-lg-4 col-xl-3">
    <div class="card bg-dark border-secondary h-100 shadow-sm new-project-card"
         role="button" data-bs-toggle="modal" data-bs-target="#<?= $type['modal'] ?>">
        <div class="card-body text-center py-4">
            <div class="mb-3"><?= $type['icon'] ?></div>
            <h6 class="card-title text-light mb-2"><?= htmlspecialchars($type['title']) ?></h6>
            <p class="card-text text-secondary small mb-0"><?= htmlspecialchars($type['desc']) ?></p>
        </div>
    </div>
</div>
