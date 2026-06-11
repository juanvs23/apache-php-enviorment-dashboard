<!-- ═══════════════════════════════════════════════════════════════ -->
<!-- MODAL: Laravel -->
<!-- ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNewLaravel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">🔺 Crear Proyecto Laravel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/?create_project=laravel">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre del proyecto</label>
                        <input type="text" name="project_name" class="form-control bg-dark text-light border-secondary"
                               placeholder="ej: Mi Aplicación Laravel" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre del directorio</label>
                        <input type="text" name="directory" class="form-control bg-dark text-light border-secondary"
                               placeholder="ej: my-app" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre de la base de datos</label>
                        <input type="text" name="db_name" class="form-control bg-dark text-light border-secondary"
                               placeholder="ej: laravel_db" required>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <strong>Se instalará:</strong> Laravel vía Composer, <code>.env</code> configurado,
                        migraciones iniciales, y <code>user-data.txt</code> para el dashboard.
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Crear Proyecto Laravel</button>
                </div>
            </form>
        </div>
    </div>
</div>
