<!-- ═══════════════════════════════════════════════════════════════ -->
<!-- MODAL: HTML en blanco -->
<!-- ═══════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalNewHtml" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">🌐 Crear Proyecto HTML</h5>
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
                        <small class="text-secondary">Se creará en <code>/mnt/vol/projects/apache/</code>.</small>
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
                                <strong>Se creará:</strong> <code>index.html</code>, <code>assets/css/style.css</code>,
                                y <code>user-data.txt</code>.
                            </div>
                        </div>
                        <div class="tab-pane fade" id="htmlTabGithub" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label text-light">URL del repositorio</label>
                                <input type="url" name="repo_url" class="form-control bg-dark text-light border-secondary"
                                       placeholder="https://github.com/usuario/repo.git">
                                <small class="text-secondary">HTTPS o SSH. Se hará <code>git clone</code>.</small>
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
