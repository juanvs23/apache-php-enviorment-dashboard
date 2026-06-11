# Roadmap: Crear Proyectos desde el Dashboard

## Objetivo

Permitir a los administradores crear nuevos proyectos directamente desde la interfaz web del dashboard, sin necesidad de acceder al servidor por SSH. Tres tipos de proyectos con templates preconfigurados y opción de clonar desde GitHub.

---

## Arquitectura

### Flujo completo

```
Dashboard (UI)                     Backend
─────────────                      ───────
Pestaña "Nuevo Proyecto"
  ├─ Card HTML ──→ Modal ──→ POST /?create_project=html ──→ ProjectCreator
  ├─ Card Laravel ─→ Modal ──→ POST /?create_project=laravel ─→ (pendiente)
  └─ Card WordPress → Modal ──→ POST /?create_project=wordpress → (pendiente)
```

### Stack

| Capa | Qué usa |
|---|---|
| Frontend | Bootstrap 5 tabs + modales, `views/components/` |
| Backend | `Infrastructure/Filesystem/ProjectCreator.php` |
| Dependencias | Git (clone), Node.js + npm (Vite), Composer (Laravel), WP-CLI (WordPress) |

---

## Fases

### ✅ Fase 1 — UI y estructura base

- [x] Cards con SVG oficiales (HTML5, Laravel, WordPress)
- [x] Pestaña "Nuevo Proyecto" solo visible para admin (`type=0`)
- [x] Modal HTML: tabs "Desde cero" / "Clonar desde GitHub"
- [x] Modal HTML: checkbox "Usar Vite.js"
- [x] Modal Laravel: nombre, directorio, base de datos
- [x] Modal WordPress: nombre, directorio, DB, título, admin email/pass
- [x] SVG extraídos a `$newProjectTypes` array (DRY) + partial `_new-project-card.php`
- [x] Modales extraídos a partials separados

### ✅ Fase 2 — Backend: HTML

- [x] `ProjectCreator::createHtml()` — vanilla (index.html + assets/css/style.css)
- [x] `ProjectCreator::createHtml()` — Vite (package.json + vite.config.js + src/)
- [x] `ProjectCreator::createFromGithub()` — git clone + detección tipo + npm install
- [x] `ProjectCreator::detectProjectType()` — wordpress / laravel / node / html
- [x] `user-data.txt` generado automáticamente para el dashboard

### ✅ Fase 3 — Infraestructura

- [x] `setup.sh`: paso 3 instala Git + Node.js (condicional, solo si no existen)
- [x] `SETUP.md`: Git + Node.js como componentes opcionales con guía de instalación
- [x] `auth-check.php`: bypass para PageSpeed/Lighthouse/GTmetrix, bloqueo de crawlers

### ✅ Fase 4 — Backend handler (HTML)

- [x] `ProjectCreator` registrado en `ServiceContainer`
- [x] Handler en `index.php` para `POST /?create_project=html`
- [x] Detectar origen: `repo_url` presente → `createFromGithub()`, sino → `createHtml()`
- [x] Validaciones: nombre no vacío, directorio sin `..` ni `/`, solo `[a-zA-Z0-9_-]`
- [x] Flash messages en `dashboard.php` (genérico, reutilizado por npm también)
- [x] Redirect con mensaje de éxito/error

### ✅ Fase 4.5 — Botón 📦 Instalar dependencias

- [x] `ProjectScanner`: detecta `package.json` → flag `has_node`
- [x] `ProjectScanner`: detecta `.pid` → flag `has_pid` (dev server corriendo)
- [x] Botón 📦 Instalar en project-card (visible si `has_node`)
- [x] Handler `?npm_action=install&dir=X` en `index.php`
- [x] Detección inteligente: system npm → fnm → `which` via sudo
- [x] `sudoers.d/dashboard-npm` para ejecutar como dueño del proyecto
- [x] Cache en `/tmp/npm-cache` para evitar errores de permisos

### ✅ Fase 4.6 — Botón 🚀 Iniciar dev server

- [x] Botón 🚀 Iniciar en project-card (visible si `has_node` y NO `has_pid`)
- [x] Handler `?npm_action=start&dir=X` en `index.php`
- [x] `nohup npm run dev > /dev/null 2>&1 & echo $! > .pid`
- [x] `sudo -u {owner}` para ejecutar como dueño del proyecto
- [x] Feedback con link clickeable a `localhost:5173`

### ✅ Fase 4.7 — Botón ⏹ Detener dev server

- [x] Botón ⏹ Detener en project-card (visible si `has_pid`)
- [x] Handler `?npm_action=stop&dir=X` en `index.php`
- [x] `kill` + `rm .pid`
- [x] Confirmación antes de detener (`onsubmit="return confirm(...)"`)
- [x] Limpiar PID file si el proceso ya murió

### 🔲 Fase 5 — Backend: Laravel

- [ ] `ProjectCreator::createLaravel()` — `composer create-project laravel/laravel`
- [ ] Crear base de datos MySQL (`CREATE DATABASE`)
- [ ] Configurar `.env` con credenciales
- [ ] Crear `user-data.txt` con `type: laravel`
- [ ] Handler en `index.php` para `POST /?create_project=laravel`

### 🔲 Fase 6 — Backend: WordPress

- [ ] `ProjectCreator::createWordpress()` — WP-CLI `wp core download`
- [ ] Crear base de datos MySQL
- [ ] `wp config create` + `wp core install`
- [ ] Crear `user-data.txt` con `type: wordpress`
- [ ] Handler en `index.php` para `POST /?create_project=wordpress`

### 🔲 Fase 7 — Testing

- [ ] Tests unitarios para `ProjectCreator` (mocked filesystem)
- [ ] Tests de integración para `createHtml()` (directorio temporal real)
- [ ] Tests de integración para `createFromGithub()` (repo público de prueba)

### 🔲 Fase 8 — UX polish

- [ ] Validación client-side en modales (JS)
- [ ] Spinner/loading durante la creación (puede tardar con composer/npm)
- [ ] Sugerir slug automáticamente desde el nombre del proyecto
- [ ] Confirmación antes de crear (¿estás seguro?)

---

## Dependencias por tipo de proyecto

| Tipo | Requiere | Instalado por |
|---|---|---|
| HTML vanilla | Ninguna | — |
| HTML + Vite | Node.js + npm | `setup.sh` paso 3 |
| Clonar GitHub | Git | `setup.sh` paso 3 |
| Laravel | PHP + Composer + MySQL + unzip | `setup.sh` pasos 1, 4 |
| WordPress | PHP + MySQL + WP-CLI + unzip + curl | `setup.sh` pasos 1, 4 |

---

## Rutas del handler (plan)

```
POST /?create_project=html       → ProjectCreator::createHtml() o createFromGithub()
POST /?create_project=laravel    → ProjectCreator::createLaravel()   (pendiente)
POST /?create_project=wordpress  → ProjectCreator::createWordpress() (pendiente)
```

## Notas

- El `ProjectCreator` ya está implementado pero **no está registrado en ServiceContainer** ni tiene handler en `index.php`. La creación de proyectos HTML desde la UI no funciona end-to-end todavía.
- Las dependencias (Git, Node, Composer, WP-CLI) son opcionales. Si no están instaladas, el handler debe devolver un error claro.
- El directorio de proyectos está hardcodeado a `/mnt/vol/projects/apache/`. Debería venir de configuración (`.env`) para ser portable.
