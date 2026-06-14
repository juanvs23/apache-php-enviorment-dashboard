<?php
/**
 * Card de tipo de proyecto para la pestaña "Nuevo Proyecto".
 * Variables: $type = ['icon' => SVG, 'title' => string, 'desc' => string, 'modal' => string]
 */
?>
<div class="col-12 col-md-6 col-lg-4 col-xl-3">
    <div class="card border-0 shadow-sm h-100"
         style="background: #1e2130; border-radius: 10px; cursor: pointer; transition: transform .15s, box-shadow .15s;"
         onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 16px rgba(0,0,0,0.4)'"
         onmouseout="this.style.transform='';this.style.boxShadow=''"
         role="button" data-bs-toggle="modal" data-bs-target="#<?= $type['modal'] ?>">
        <div class="card-body text-center py-4 d-flex flex-column align-items-center" style="gap: 12px;">
            <div><?= $type['icon'] ?></div>
            <h6 class="mb-0" style="color: #e4e6eb; font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($type['title']) ?></h6>
            <p class="mb-0 small" style="color: #8b949e; line-height: 1.4;"><?= htmlspecialchars($type['desc']) ?></p>
        </div>
    </div>
</div>
