<!-- ═══════════════════════════════════════════════════════════════ -->
<!-- MODAL: WordPress -->
<!-- ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNewWordpress" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">📝 Crear Proyecto WordPress</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/?create_project=wordpress">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Nombre del proyecto</label>
                            <input type="text" name="project_name" class="form-control bg-dark text-light border-secondary"
                                   placeholder="ej: Mi Blog" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Nombre del directorio</label>
                            <input type="text" name="directory" class="form-control bg-dark text-light border-secondary"
                                   placeholder="ej: mi-blog" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Nombre de la base de datos</label>
                            <input type="text" name="db_name" class="form-control bg-dark text-light border-secondary"
                                   placeholder="ej: wp_blog" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Título del sitio</label>
                            <input type="text" name="site_title" class="form-control bg-dark text-light border-secondary"
                                   placeholder="ej: Mi Blog Personal" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Email del administrador</label>
                            <input type="email" name="admin_email" class="form-control bg-dark text-light border-secondary"
                                   placeholder="admin@admin.com" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Contraseña del admin</label>
                            <div class="position-relative">
                                <input type="password" name="admin_password" id="wpAdminPass"
                                       class="form-control bg-dark text-light border-secondary pe-5"
                                       placeholder="Mínimo 8 caracteres" required>
                                <button type="button"
                                        class="btn btn-link btn-sm position-absolute end-0 top-50 translate-middle-y me-1 text-decoration-none"
                                        onclick="togglePassword('wpAdminPass', this)" tabindex="-1">Mostrar</button>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info small mt-3 mb-0">
                        <strong>Se instalará:</strong> WordPress vía WP-CLI, base de datos configurada,
                        plugins iniciales, y <code>user-data.txt</code> para el dashboard.
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Proyecto WordPress</button>
                </div>
            </form>
        </div>
    </div>
</div>
