/**
 * Project Creation UX — validation, auto-slug, loading spinner, async DB check.
 *
 * Se aplica automáticamente a los 3 modales de creación (HTML, Laravel, WordPress).
 * Reutilizable: solo requiere que el form tenga action con "create_project"
 * y los campos sigan la convención de nombres (project_name, directory, db_name, etc.).
 *
 * También incluye el botón "Copiar credenciales" en las tarjetas de proyecto.
 */
(function () {
    'use strict';

    var DIR_REGEX = /^[a-zA-Z0-9_-]+$/;
    var EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var SPINNER = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Creando...';

    // ── Auto-slug: project_name → directory ──────────────────────
    document.querySelectorAll('[name="project_name"]').forEach(function (nameInput) {
        var form = nameInput.closest('form');
        var dirInput = form.querySelector('[name="directory"]');
        if (!dirInput) return;

        var lastAutoSlug = '';

        nameInput.addEventListener('input', function () {
            var slug = nameInput.value
                .toLowerCase()
                .replace(/[^a-z0-9\s_-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');

            if (dirInput.value === '' || dirInput.value === lastAutoSlug) {
                dirInput.value = slug;
                lastAutoSlug = slug;
            }
        });
    });

    // ── Setup all create-project forms ──────────────────────────
    document.querySelectorAll('form[action*="create_project"]').forEach(function (form) {
        form.setAttribute('novalidate', '');

        var fields = collectFields(form);

        // ── Blur: validate single field on exit ────────────────
        fields.forEach(function (f) {
            if (!f.el) return;
            f.el.addEventListener('blur', function () {
                if (f.rule === 'dbname') {
                    validateDbNameAsync(f, form);
                } else {
                    validateField(f, form);
                }
            });
            // Clear error as user types
            f.el.addEventListener('input', function () {
                clearError(f.el);
            });
        });

        // ── Submit: validate all + spinner ─────────────────────
        form.addEventListener('submit', function (e) {
            // Clear all previous errors
            fields.forEach(function (f) { if (f.el) clearError(f.el); });

            var errors = [];
            fields.forEach(function (f) {
                if (!validateField(f, form)) {
                    errors.push(f.name);
                }
            });

            if (errors.length > 0) {
                e.preventDefault();
                var first = form.querySelector('.is-invalid');
                if (first) first.focus();
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = SPINNER;
            }
        });
    });

    // ── Collect all validatable fields from a form ──────────────
    function collectFields(form) {
        var isWP = !!form.querySelector('[name="site_title"]');
        var hasDB = !!form.querySelector('[name="db_name"]');
        var hasRepo = !!form.querySelector('[name="repo_url"]');

        var all = [
            { el: form.querySelector('[name="project_name"]'), name: 'project_name', rule: 'required' },
            { el: form.querySelector('[name="directory"]'),     name: 'directory',    rule: 'directory' },
        ];

        if (hasDB) {
            all.push({ el: form.querySelector('[name="db_name"]'), name: 'db_name', rule: 'dbname' });
        }
        if (hasRepo) {
            all.push({ el: form.querySelector('[name="repo_url"]'), name: 'repo_url', rule: 'repo' });
        }
        if (isWP) {
            all.push({ el: form.querySelector('[name="site_title"]'),    name: 'site_title',    rule: 'required' });
            all.push({ el: form.querySelector('[name="admin_email"]'),   name: 'admin_email',   rule: 'email' });
            all.push({ el: form.querySelector('[name="admin_password"]'), name: 'admin_password', rule: 'password' });
        }

        return all;
    }

    // ── Validate a single field — returns true if valid ─────────
    function validateField(field, form) {
        if (!field.el) return true;

        var val = field.el.value.trim();
        var el = field.el;

        // Repo is only required when GitHub tab is active
        var githubTab = form.querySelector('.tab-pane.active[id$="Github"]');
        var isGithubMode = githubTab && githubTab.classList.contains('active');

        if (field.rule === 'repo') {
            if (isGithubMode && !val) {
                return markInvalid(el, 'La URL del repositorio es requerida');
            }
            return true;
        }

        if (field.rule === 'required') {
            if (!val) {
                return markInvalid(el, 'Este campo es requerido');
            }
            return true;
        }

        if (field.rule === 'directory') {
            if (!val) {
                return markInvalid(el, 'El directorio es requerido');
            }
            if (!DIR_REGEX.test(val)) {
                return markInvalid(el, 'Solo letras, números, guiones y guiones bajos');
            }
            if (val.includes('..') || val.startsWith('/')) {
                return markInvalid(el, 'Directorio no válido');
            }
            return true;
        }

        if (field.rule === 'dbname') {
            if (!val) {
                return markInvalid(el, 'El nombre de la base de datos es requerido');
            }
            if (!/^[a-zA-Z_]/.test(val)) {
                return markInvalid(el, 'Debe empezar con letra o guion bajo');
            }
            if (!/^[a-zA-Z_]\w*$/.test(val)) {
                return markInvalid(el, 'Solo letras, números y guiones bajos');
            }
            return true;
        }

        if (field.rule === 'email') {
            if (!val) {
                return markInvalid(el, 'El email es requerido');
            }
            if (!EMAIL_REGEX.test(val)) {
                return markInvalid(el, 'Email no válido');
            }
            return true;
        }

        if (field.rule === 'password') {
            if (!field.el.value) {
                return markInvalid(el, 'La contraseña es requerida');
            }
            if (field.el.value.length < 8) {
                return markInvalid(el, 'Mínimo 8 caracteres');
            }
            return true;
        }

        return true;
    }

    // ── Async DB name check (format + existence via AJAX) ────────
    function validateDbNameAsync(field, form) {
        if (!validateField(field, form)) return;

        var el = field.el;
        var val = el.value.trim();
        if (!val) return;

        var prevPlaceholder = el.placeholder;
        el.placeholder = 'Verificando...';
        el.style.opacity = '0.6';

        fetch('/?check_db=' + encodeURIComponent(val), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                el.placeholder = prevPlaceholder;
                el.style.opacity = '1';
                if (data.exists) {
                    markInvalid(el, 'Esta base de datos ya existe. Elegí otro nombre.');
                }
            })
            .catch(function () {
                el.placeholder = prevPlaceholder;
                el.style.opacity = '1';
            });
    }

    // ── Mark field invalid, show feedback — returns false ───────
    function markInvalid(el, message) {
        clearError(el);
        el.classList.add('is-invalid');
        var feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.style.display = 'block';
        feedback.textContent = message;
        el.parentNode.insertBefore(feedback, el.nextSibling);
        return false;
    }

    // ── Remove error state from a field ─────────────────────────
    function clearError(el) {
        el.classList.remove('is-invalid');
        var fb = el.parentNode.querySelector('.invalid-feedback');
        if (fb) fb.remove();
    }

    // ── Copy credentials buttons ──────────────────────────────
    document.querySelectorAll('.copy-creds-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url   = btn.getAttribute('data-cred-url')   || '';
            var user  = btn.getAttribute('data-cred-user')  || '';
            var pass  = btn.getAttribute('data-cred-pass')  || '';
            var name  = btn.getAttribute('data-cred-name')  || '';

            var lines = [];
            lines.push('Proyecto: ' + name);
            lines.push('URL: ' + window.location.origin + '/' + url);
            if (user) lines.push('Usuario: ' + user);
            if (pass) lines.push('Contraseña: ' + pass);

            var text = lines.join('\n');

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    flashButton(btn);
                });
            } else {
                // Fallback for older browsers or non-HTTPS
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                flashButton(btn);
            }
        });
    });

    function flashButton(btn) {
        var original = btn.textContent;
        btn.textContent = '✅ Copiado!';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-secondary');
        setTimeout(function () {
            btn.textContent = original;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 1500);
    }

    // ── Project search / filter ───────────────────────────────
    var searchInput = document.getElementById('projectSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var query = searchInput.value.toLowerCase().trim();
            var cards = document.querySelectorAll('#projectGrid .col-12');
            var visible = 0;

            cards.forEach(function (card) {
                var text = card.textContent.toLowerCase();
                if (!query || text.indexOf(query) !== -1) {
                    card.style.display = '';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });

            var countEl = document.getElementById('projectCount');
            if (countEl) {
                countEl.textContent = visible + ' proyecto(s)';
            }

            var noMsg = document.getElementById('noFilterResults');
            if (visible === 0 && query) {
                if (!noMsg) {
                    noMsg = document.createElement('div');
                    noMsg.id = 'noFilterResults';
                    noMsg.className = 'col-12 text-center text-white-50 py-5';
                    noMsg.innerHTML = '<p class="fs-4 mb-1">🔍 Sin resultados</p><p class="small">Ningún proyecto coincide con <code>' + query + '</code></p>';
                    document.getElementById('projectGrid').appendChild(noMsg);
                }
            } else if (noMsg) {
                noMsg.remove();
            }
        });
    }

    // ── Live Apache error log (tabLogs) ──────────────────────
    var logContent = document.getElementById('logContent');
    var logStatus = document.getElementById('logStatus');
    var logRefresh = document.getElementById('logRefreshBtn');
    if (logContent) {
        var logTimer = null;

        function fetchLogs() {
            if (logStatus) logStatus.textContent = 'Cargando...';
            fetch('/?tail_log=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.lines) {
                        logContent.textContent = data.lines.join('\n');
                        if (logStatus) {
                            logStatus.textContent = '✓ ' + (data.file || 'logs');
                            logStatus.style.color = '#3fb950';
                        }
                        logContent.scrollTop = logContent.scrollHeight;
                    } else {
                        logContent.textContent = data.error || 'Error al cargar logs';
                        if (logStatus) logStatus.textContent = 'Error';
                        if (logStatus) logStatus.style.color = '#f85149';
                    }
                })
                .catch(function () {
                    logContent.textContent = 'Error de conexión al cargar logs';
                    if (logStatus) logStatus.textContent = 'Sin conexión';
                    if (logStatus) logStatus.style.color = '#f85149';
                });
        }

        function startPolling() {
            fetchLogs();
            logTimer = setInterval(fetchLogs, 5000);
        }

        function stopPolling() {
            if (logTimer) clearInterval(logTimer);
        }

        if (logRefresh) {
            logRefresh.addEventListener('click', fetchLogs);
        }

        var logsTab = document.querySelector('[data-bs-target="#tabLogs"]');
        if (logsTab) {
            logsTab.addEventListener('shown.bs.tab', startPolling);
            logsTab.addEventListener('hidden.bs.tab', stopPolling);
        }

        if (document.getElementById('tabLogs').classList.contains('active')) {
            startPolling();
        }
    }

    // ── Service status polling + notifications ──────────────
    var prevState = {};
    var serviceTimer = null;

    function checkServices() {
        fetch('/?service_status=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) return;

                var nameMap = {
                    postgresql: 'PostgreSQL',
                    mysql: 'MySQL',
                    pgadmin4: 'pgAdmin4',
                    phpmyadmin: 'phpMyAdmin',
                    apache: 'Apache'
                };

                Object.keys(data).forEach(function (key) {
                    var isUp = data[key];
                    var prev = prevState[key];

                    // Update badge in Server tab if present
                    var badge = document.querySelector('[data-service="' + key + '"]');
                    if (badge) {
                        badge.className = 'badge bg-' + (isUp ? 'success' : 'secondary') + ' bg-opacity-25';
                        badge.style.color = isUp ? '#3fb950' : '#8b949e';
                    }

                    // Show toast on state change (only DOWN transitions)
                    if (prev === true && isUp === false) {
                        showToast('⚠️ ' + (nameMap[key] || key) + ' dejó de responder', 'danger');
                    } else if (prev === false && isUp === true && prevState.hasOwnProperty(key)) {
                        showToast('✅ ' + (nameMap[key] || key) + ' volvió', 'success');
                    }

                    prevState[key] = isUp;
                });
            });
    }

    function showToast(message, type) {
        var container = document.getElementById('serviceToasts');
        if (!container) return;

        var toast = document.createElement('div');
        toast.className = 'alert alert-' + type + ' alert-dismissible fade show py-2 px-3 small mb-2';
        toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.4)';
        toast.innerHTML = message +
            '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" style="font-size:0.6rem;"></button>';
        container.appendChild(toast);

        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 8000);
    }

    // Start polling immediately, every 30 seconds
    checkServices();
    serviceTimer = setInterval(checkServices, 30000);

    // ── Assign client modal: search + select + save ─────────
    document.querySelectorAll('.assign-client-btn').forEach(function (btn) {
        var modal = document.querySelector(btn.getAttribute('data-bs-target'));
        if (!modal) return;
        var searchInput = modal.querySelector('.client-search');
        var resultsDiv = modal.querySelector('.client-results');
        var loginChk = modal.querySelector('input[type="checkbox"]');
        var selectedId = modal.querySelector('.selected-client-id');
        var saveBtn = modal.querySelector('.save-client-btn');
        var projectId = modal.id.replace('modalAssignClient-', '');
        var timer = null;

        // Reset modal on open
        modal.addEventListener('show.bs.modal', function () {
            searchInput.value = '';
            resultsDiv.innerHTML = '';
            selectedId.value = '';
            saveBtn.disabled = true;
        });

        // Search
        searchInput.addEventListener('input', function () {
            clearTimeout(timer);
            var q = searchInput.value.trim();
            if (!q) { resultsDiv.innerHTML = ''; return; }
            timer = setTimeout(function () {
                resultsDiv.innerHTML = '<div class="text-muted small p-2">Buscando...</div>';
                fetch('/api.php?action=users_search&q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        resultsDiv.innerHTML = '';
                        if (!data.results || !data.results.length) {
                            resultsDiv.innerHTML = '<div class="text-muted small p-2">Sin resultados</div>';
                            return;
                        }
                        data.results.forEach(function (user) {
                            var div = document.createElement('div');
                            div.className = 'd-flex align-items-center gap-2 px-2 py-1 client-result-item';
                            div.style.cssText = 'cursor:pointer;border-bottom:1px solid #2d323e;';
                            div.setAttribute('data-id', user.id);
                            div.innerHTML = '<span class="text-light small flex-grow-1">' + user.text + '</span><span class="check-mark" style="color:#3fb950;display:none;">✓</span>';
                            div.addEventListener('click', function () {
                                // Deselect all
                                resultsDiv.querySelectorAll('.client-result-item').forEach(function (el) {
                                    el.style.background = '';
                                    el.querySelector('.check-mark').style.display = 'none';
                                });
                                // Select this one
                                div.style.background = '#1a3a5c';
                                div.querySelector('.check-mark').style.display = '';
                                selectedId.value = user.id;
                                saveBtn.disabled = false;
                            });
                            resultsDiv.appendChild(div);
                        });
                    });
            }, 300);
        });

        // Save
        saveBtn.addEventListener('click', function () {
            var uid = selectedId.value;
            if (!uid) return;
            saveBtn.disabled = true;
            saveBtn.textContent = 'Guardando...';
            assignUser(projectId, uid, loginChk.checked);
        });
    });

    function assignUser(projectId, userId, isLogeable) {
        fetch('/api.php?action=project_users&id=' + encodeURIComponent(projectId))
            .then(function (r) { return r.json(); })
            .then(function (users) {
                if (!Array.isArray(users)) users = [];
                var existing = users.find(function (u) { return u.id === userId; });
                if (!existing) users.push({ id: userId, logeable: isLogeable });
                else existing.logeable = isLogeable;
                return fetch('/api.php?action=project_users_update&id=' + encodeURIComponent(projectId), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_ids: users.map(function (u) { return u.id; }),
                        logeable: users.map(function (u) { return u.logeable; })
                    })
                });
            }).then(function () { location.reload(); });
    }

    // Remove assigned user
    document.querySelectorAll('.remove-assigned-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var userId = btn.getAttribute('data-user');
            var projectId = btn.getAttribute('data-project');
            fetch('/api.php?action=project_users&id=' + encodeURIComponent(projectId))
                .then(function (r) { return r.json(); })
                .then(function (users) {
                    if (!Array.isArray(users)) return;
                    users = users.filter(function (u) { return u.id !== userId; });
                    return fetch('/api.php?action=project_users_update&id=' + encodeURIComponent(projectId), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_ids: users.map(function (u) { return u.id; }),
                            logeable: users.map(function (u) { return u.logeable; })
                        })
                    });
                }).then(function () { location.reload(); });
        });
    });

    // Toggle login on chip
    document.querySelectorAll('.toggle-login-chip').forEach(function (el) {
        el.addEventListener('click', function () {
            var userId = el.getAttribute('data-user');
            var projectId = el.getAttribute('data-project');
            var current = el.getAttribute('data-logeable') === '1';
            var newVal = !current;

            // Update via API
            fetch('/api.php?action=project_users&id=' + encodeURIComponent(projectId))
                .then(function (r) { return r.json(); })
                .then(function (users) {
                    if (!Array.isArray(users)) return;
                    users.forEach(function (u) {
                        if (u.id === userId) u.logeable = newVal;
                    });
                    return fetch('/api.php?action=project_users_update&id=' + encodeURIComponent(projectId), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_ids: users.map(function (u) { return u.id; }),
                            logeable: users.map(function (u) { return u.logeable; })
                        })
                    });
                }).then(function () { location.reload(); });
        });
    });
})();
