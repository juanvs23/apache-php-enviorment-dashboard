<!-- ═══════════════════════════════════════════════════════════════ -->
<!-- MODAL: WordPress -->
<!-- ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNewWordpress" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="#21759B"><path d="M21.469 6.825c.84 1.537 1.318 3.3 1.318 5.175 0 3.979-2.156 7.456-5.363 9.325l3.295-9.527c.615-1.54.82-2.771.82-3.864 0-.405-.026-.78-.07-1.11m-7.981.105c.647-.03 1.232-.105 1.232-.105.582-.075.514-.93-.067-.899 0 0-1.755.135-2.88.135-1.064 0-2.85-.15-2.85-.15-.585-.03-.661.855-.075.885 0 0 .54.061 1.125.09l1.68 4.605-2.37 7.08L5.354 6.9c.649-.03 1.234-.1 1.234-.1.585-.075.516-.93-.065-.896 0 0-1.746.138-2.874.138-.2 0-.438-.008-.69-.015C4.911 3.15 8.235 1.215 12 1.215c2.809 0 5.365 1.072 7.286 2.833-.046-.003-.091-.009-.141-.009-1.06 0-1.812.923-1.812 1.914 0 .89.513 1.643 1.06 2.531.411.72.89 1.643.89 2.977 0 .915-.354 1.994-.821 3.479l-1.075 3.585-3.9-11.61.001.014zM12 22.784c-1.059 0-2.081-.153-3.048-.437l3.237-9.406 3.315 9.087c.024.053.05.101.078.149-1.12.393-2.325.609-3.582.609M1.211 12c0-1.564.336-3.05.935-4.39L7.29 21.709C3.694 19.96 1.212 16.271 1.211 12M12 0C5.385 0 0 5.385 0 12s5.385 12 12 12 12-5.385 12-12S18.615 0 12 0"/></svg>
                    Crear Proyecto WordPress
                </h5>
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
