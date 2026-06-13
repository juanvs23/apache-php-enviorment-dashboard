<!-- ═══════════════════════════════════════════════════════════════ -->
<!-- MODAL: HTML en blanco -->
<!-- ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNewHtml" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="#E44D26"><path d="M1.5 0h21l-1.91 21.563L11.977 24l-8.564-2.438L1.5 0zm7.031 9.75l-.232-2.718 10.059.003.23-2.622L5.412 4.41l.698 8.01h9.126l-.326 3.426-2.91.804-2.955-.81-.188-2.11H6.248l.33 4.171L12 19.351l5.379-1.443.744-8.157H8.531z"/></svg>
                    Crear Proyecto HTML
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/?create_project=html">
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab"
                                    data-bs-target="#htmlTabScratch" type="button" role="tab">🆕 Desde cero</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#htmlTabGithub" type="button" role="tab">📥 Clonar desde GitHub</button>
                        </li>
                    </ul>
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre del proyecto</label>
                        <input type="text" name="project_name" class="form-control bg-dark text-light border-secondary"
                               placeholder="ej: mi-landing-page" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-light">Nombre del directorio</label>
                        <input type="text" name="directory" class="form-control bg-dark text-light border-secondary"
                               placeholder="ej: landing-page" required>
                        <small class="text-secondary">Se creará en el directorio raíz del proyecto.</small>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="htmlTabScratch" role="tabpanel">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="use_vite" value="1" id="htmlUseVite">
                                    <label class="form-check-label text-light" for="htmlUseVite">⚡ Usar Vite.js</label>
                                </div>
                                <small class="text-secondary d-block mt-1">
                                    Incluye <code>package.json</code>, <code>vite.config.js</code> y estructura <code>src/</code>.
                                </small>
                            </div>
                            <div class="alert alert-info small mb-0">
                                <strong>Se creará:</strong> <code>index.html</code>, <code>assets/css/styles.css</code>,
                                <code>assets/js/main.js</code>, <code>assets/images/</code>, <code>.gitignore</code> y <code>user-data.txt</code>.
                            </div>
                        </div>
                        <div class="tab-pane fade" id="htmlTabGithub" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label text-light">URL del repositorio</label>
                                <input type="text" name="repo_url" class="form-control bg-dark text-light border-secondary"
                                       placeholder="https://github.com/usuario/repo.git">
                                <small class="text-secondary">Solo HTTPS. Repositorios públicos.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-light">Rama (opcional)</label>
                                <input type="text" name="branch" class="form-control bg-dark text-light border-secondary"
                                       placeholder="main">
                            </div>
                            <div class="alert alert-info small mb-0">
                                <strong>Se hará:</strong> <code>git clone</code> del repo, se creará
                                <code>user-data.txt</code> y se ejecutará <code>npm install</code> si hay <code>package.json</code>.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Proyecto</button>
                </div>
            </form>
        </div>
    </div>
</div>
