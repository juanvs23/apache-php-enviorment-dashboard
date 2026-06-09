# Roadmap: Persistencia, Usuarios y Control de Acceso

## Objetivo

Migrar el dashboard de autenticación plana (una sola clave compartida en `.env`) a un sistema con **SQLite**, **usuarios con roles**, **control de acceso por proyecto**, aplicando **Arquitectura Limpia** con patrón **MVC** y **pruebas unitarias**.

---

## Arquitectura

### Stack técnico

| Componente | Elección |
|---|---|
| Base de datos | SQLite vía PDO |
| Tests | PHPUnit + SQLite in-memory para integración |
| Autoloading | Composer o `spl_autoload_register` custom |
| Frontend | PHP vanilla (sin framework) + Bootstrap existente |

### Capas (Clean Architecture)

Tres capas concéntricas. Las dependencias apuntan hacia adentro: **Presentation → Application → Domain**. Infrastructure cruza horizontalmente.

```
┌─────────────────────────────┐
│      Presentation           │  ← MVC: Controllers + Views
│  (HTTP, sesión, HTML)       │
├─────────────────────────────┤
│      Application            │  ← Use Cases + Interfaces de Repositorio
│  (orquestación, reglas      │
│   de aplicación)            │
├─────────────────────────────┤
│      Domain                 │  ← Entities + Value Objects
│  (reglas de negocio puras,  │
│   sin dependencias externas)│
└─────────────────────────────┘
```

- **Domain**: no sabe que existe SQLite, ni HTTP, ni el framework. Solo reglas de negocio.
- **Application**: usa interfaces (ej. `UserRepositoryInterface`). No sabe cómo se implementan.
- **Infrastructure**: implementa esas interfaces (SQLite, sesión, filesystem).
- **Presentation**: Controllers reciben requests, llaman Use Cases, delegan a Views.

### MVC Mapping

| MVC | Clean Architecture |
|---|---|
| **Model** | Domain (Entities) + Application (Use Cases) + Infrastructure (Repositories) |
| **View** | `Presentation/View/` — HTML templates, sin lógica de negocio |
| **Controller** | `Presentation/Controller/` — recibe `$_GET/$_POST`, llama Use Cases, renderiza View |

### Árbol completo (`dashboard-logic/`)

```
dashboard-logic/
├── Database/
│   ├── Connection.php          # PDO wrapper (singleton, migrations automáticas)
│   └── Migration.php           # Schema DDL + seeds
│
├── Domain/
│   ├── Entity/
│   │   ├── User.php
│   │   ├── Project.php
│   │   └── ProjectAccess.php
│   └── ValueObject/
│       ├── Role.php            # enum-like: admin | user
│       └── ProjectType.php     # wordpress | laravel | etc
│
├── Application/
│   ├── UseCase/
│   │   ├── Auth/
│   │   │   ├── LoginUseCase.php
│   │   │   └── LogoutUseCase.php
│   │   ├── User/
│   │   │   ├── CreateUserUseCase.php
│   │   │   ├── ListUsersUseCase.php
│   │   │   └── DeleteUserUseCase.php
│   │   └── Project/
│   │       ├── ListProjectsForUserUseCase.php
│   │       ├── SyncProjectsFromFilesystemUseCase.php
│   │       └── AssignProjectAccessUseCase.php
│   └── Repository/
│       ├── UserRepositoryInterface.php
│       ├── ProjectRepositoryInterface.php
│       └── ProjectAccessRepositoryInterface.php
│
├── Infrastructure/
│   ├── Persistence/
│   │   ├── SQLiteUserRepository.php
│   │   ├── SQLiteProjectRepository.php
│   │   └── SQLiteProjectAccessRepository.php
│   ├── Filesystem/
│   │   └── ProjectScanner.php      # escanea user-data.txt
│   └── Session/
│       └── SessionManager.php
│
├── Presentation/
│   ├── Controller/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   └── AdminController.php
│   ├── View/
│   │   ├── login.php
│   │   ├── dashboard.php
│   │   └── admin/
│   │       ├── users-list.php
│   │       ├── user-form.php
│   │       └── project-access.php
│   └── Router.php                # mapea ?page=x a controladores
│
├── Tests/
│   ├── Unit/
│   │   ├── Domain/
│   │   │   ├── UserTest.php
│   │   │   └── RoleTest.php
│   │   ├── Application/
│   │   │   ├── LoginUseCaseTest.php
│   │   │   ├── CreateUserUseCaseTest.php
│   │   │   └── ListProjectsForUserUseCaseTest.php
│   │   └── Infrastructure/
│   │       ├── SQLiteUserRepositoryTest.php
│   │       └── ProjectScannerTest.php
│   ├── Integration/
│   │   └── FullStackTest.php       # login → ver projects → asignar
│   └── bootstrap.php               # autoload + DB en memoria para tests
│
├── data/
│   └── .gitkeep                    # SQLite file se crea acá en runtime
│
├── vendor/                         # (Composer, si se opta por ese camino)
├── composer.json
├── helpers.php                     # refactorizar: migrar funciones a use cases
├── auth.php                        # refactorizar → AuthController
├── auth-check.php                  # refactorizar → SessionManager
├── projects.php                    # refactorizar → ProjectScanner
├── bootstrap.php                   # existente: cargar entorno, sesión, etc
├── env-loader.php                  # existente, sin cambios
├── rate-limiter.php                # existente, sin cambios
├── wp-auto-login.php               # existente, sin cambios
└── phpinfo.php                     # existente, sin cambios
```

---

## Fases de Implementación

### Fase 0 — Base arquitectónica

Preparar el andamiaje antes de escribir cualquier lógica de negocio.

- Crear estructura de directorios dentro de `dashboard-logic/`
- `composer.json` con autoload PSR-4 y dependencia PHPUnit
- `Database/Connection.php` — conexión PDO a SQLite (DB en `dashboard-logic/data/dashboard.sqlite`)
- `Database/Migration.php` — `CREATE TABLE IF NOT EXISTS` para el schema
- `Tests/bootstrap.php` — autoload + SQLite in-memory para tests

### Fase 1 — Domain (Entidades y Value Objects)

Puras, sin dependencias externas. 100% testeables.

- `Domain/Entity/User.php` — id, username, passwordHash, role, createdAt, updatedAt
- `Domain/Entity/Project.php` — id, directory, displayName, type, dbUser, dbPassword
- `Domain/Entity/ProjectAccess.php` — userId, projectId
- `Domain/ValueObject/Role.php` — con validación: solo `admin` o `user`

### Fase 2 — Application (Use Cases + Interfaces)

Orquestación de reglas de aplicación. Depende de interfaces, no de implementaciones concretas.

- `Application/Repository/*Interface.php` — contratos
- `Application/UseCase/Auth/LoginUseCase.php` — autenticar contra repositorio
- `Application/UseCase/Auth/LogoutUseCase.php` — destruir sesión
- `Application/UseCase/User/CreateUserUseCase.php` — crear con hash + validación
- `Application/UseCase/User/ListUsersUseCase.php` — listar todos
- `Application/UseCase/User/DeleteUserUseCase.php` — borrar con protección último admin
- `Application/UseCase/Project/ListProjectsForUserUseCase.php` — filtrar por acceso
- `Application/UseCase/Project/SyncProjectsFromFilesystemUseCase.php` — escanear directorios
- `Application/UseCase/Project/AssignProjectAccessUseCase.php` — asignar/revocar

### Fase 3 — Infrastructure (Implementaciones Concretas)

Implementa las interfaces con SQLite, filesystem y sesión.

- `Infrastructure/Persistence/SQLiteUserRepository.php`
- `Infrastructure/Persistence/SQLiteProjectRepository.php`
- `Infrastructure/Persistence/SQLiteProjectAccessRepository.php`
- `Infrastructure/Filesystem/ProjectScanner.php` — escanea `user-data.txt`
- `Infrastructure/Session/SessionManager.php` — wrapper de `$_SESSION`

### Fase 4 — Presentation (MVC: Controllers + Views)

Capa HTTP. Los controllers son delgados: reciben input, llaman un Use Case, pasan resultado a una View.

- `Presentation/Router.php` — mapea `?page=login|dashboard|admin|admin.users|...`
- `Presentation/Controller/AuthController.php` — login/logout
- `Presentation/Controller/DashboardController.php` — listar proyectos
- `Presentation/Controller/AdminController.php` — CRUD usuarios + asignación
- `Presentation/View/login.php` — form (modificar el existente)
- `Presentation/View/dashboard.php` — grilla de proyectos (modificar)
- `Presentation/View/admin/users-list.php` — tabla de usuarios
- `Presentation/View/admin/user-form.php` — crear/editar usuario
- `Presentation/View/admin/project-access.php` — checkboxes de proyectos

### Fase 5 — Tests

PHPUnit con SQLite in-memory para no depender del archivo físico.

- Tests unitarios de cada entidad (domain)
- Tests unitarios de cada use case con repositorios mockeados
- Tests de integración con SQLite en memoria
- Comando: `vendor/bin/phpunit --bootstrap dashboard-logic/Tests/bootstrap.php dashboard-logic/Tests/`

### Fase 6 — Migración + Integración con `index.php`

Conectar el nuevo sistema al orquestador raíz.

- Modificar `index.php` para usar el Router nuevo
- Script de migración: leer `user-data.txt` actual → insertar en `projects`
- Seed de admin inicial con contraseña del `.env`
- Prueba manual: login → ver proyectos → admin → crear usuario → asignar proyectos

---

## Principios de Diseño

1. **Inyección de dependencias**: los Use Cases reciben repositorios por constructor, nunca los instancian.
2. **Inmutabilidad domain**: las entidades no mutan estado interno después de construirse (salvo casos justificados).
3. **Single Responsibility**: cada archivo hace una cosa. Sin archivos de 200 líneas con 5 responsabilidades.
4. **Testeable por diseño**: si algo es difícil de testear, la arquitectura está mal.
5. **Sin dependencias ocultas**: nada usa `new SQLiteConnection()` adentro de un controller. Las dependencias se inyectan.

---

## Resumen de Archivos

| Archivo | Acción |
|---|---|
| `dashboard-logic/composer.json` | **Nuevo** — autoload PSR-4 + phpunit |
| `dashboard-logic/Database/Connection.php` | **Nuevo** — PDO wrapper |
| `dashboard-logic/Database/Migration.php` | **Nuevo** — schema DDL |
| `dashboard-logic/Domain/Entity/User.php` | **Nuevo** |
| `dashboard-logic/Domain/Entity/Project.php` | **Nuevo** |
| `dashboard-logic/Domain/Entity/ProjectAccess.php` | **Nuevo** |
| `dashboard-logic/Domain/ValueObject/Role.php` | **Nuevo** |
| `dashboard-logic/Domain/ValueObject/ProjectType.php` | **Nuevo** |
| `dashboard-logic/Application/Repository/*Interface.php` | **Nuevo** (3 interfaces) |
| `dashboard-logic/Application/UseCase/Auth/*.php` | **Nuevo** (2 use cases) |
| `dashboard-logic/Application/UseCase/User/*.php` | **Nuevo** (3 use cases) |
| `dashboard-logic/Application/UseCase/Project/*.php` | **Nuevo** (3 use cases) |
| `dashboard-logic/Infrastructure/Persistence/*.php` | **Nuevo** (3 repos) |
| `dashboard-logic/Infrastructure/Filesystem/ProjectScanner.php` | **Nuevo** |
| `dashboard-logic/Infrastructure/Session/SessionManager.php` | **Nuevo** |
| `dashboard-logic/Presentation/Router.php` | **Nuevo** |
| `dashboard-logic/Presentation/Controller/*.php` | **Nuevo** (3 controllers) |
| `dashboard-logic/Presentation/View/*.php` | **Modificar** (login, dashboard) |
| `dashboard-logic/Presentation/View/admin/*.php` | **Nuevo** (3 views) |
| `dashboard-logic/Tests/**/*.php` | **Nuevo** (tests unitarios + integración) |
| `dashboard-logic/data/.gitkeep` | **Nuevo** — directorio para SQLite |
| `index.php` | **Modificar** — usar Router nuevo |
| `.context.md` | **Modificar** — registrar arquitectura |

---

## Prioridad de Implementación

```
Fase 0 (Base arquitectónica)
  └─► Fase 1 (Domain)
       └─► Fase 2 (Application)
            └─► Fase 3 (Infrastructure)
                 └─► Fase 4 (Presentation)
                      └─► Fase 5 (Tests)
                           └─► Fase 6 (Migración + integración)
```

Cada fase se apoya en la anterior. No se puede empezar la Fase 2 sin la 1, ni la 4 sin la 3. Las fases 5 (tests) corre en paralelo con cada fase anterior — cada entidad, use case y repositorio se testea **en el momento en que se escribe**, no al final.
