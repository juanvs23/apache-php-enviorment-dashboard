<?php
/**
 * Header + Sidebar de gestión.
 * Variables: $tab — string 'usuarios' | 'proyectos' | 'levels'
 *            $authUser — array usuario autenticado
 *            $canManage — bool permiso users.manage
 *
 * Incluye verificación de acceso: solo usuarios con permiso 'users.manage'
 * pueden ver esta sección. Si no, redirige al dashboard.
 */
if (!$authUser || !$canManage) {
    header('Location: /');
    exit;
}
?>
<style>
.management-sidebar-link {
    display: block;
    padding: 0.75rem 1rem;
    color: #adb5bd;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: .15s;
}
.management-sidebar-link:hover {
    color: #fff;
    background: rgba(255,255,255,.05);
}
.management-sidebar-link.active {
    color: #fff;
    background: rgba(255,255,255,.1);
    border-left-color: #0d6efd;
}
</style>

<nav class="navbar navbar-dark bg-dark px-3">
    <span class="navbar-brand mb-0 h1">Gestión de Usuarios</span>
    <a href="/" class="btn btn-outline-light btn-sm">Volver al Dashboard</a>
</nav>

<div class="d-flex" style="min-height: calc(100vh - 56px); background-color: var(--bs-gray-600);">

    <div class="bg-dark p-0" style="width: 220px; flex-shrink: 0;">
        <div class="d-flex flex-column py-3">
            <a href="/?users=1&tab=usuarios"
               class="management-sidebar-link <?= $tab === 'usuarios' ? 'active' : '' ?>">
                👥 Usuarios
            </a>
            <a href="/?users=1&tab=proyectos"
               class="management-sidebar-link <?= $tab === 'proyectos' ? 'active' : '' ?>">
                📁 Proyectos
            </a>
            <a href="/?users=1&tab=levels"
               class="management-sidebar-link <?= $tab === 'levels' ? 'active' : '' ?>">
                🔐 Niveles y Permisos
            </a>
            <a href="/?users=1&tab=logs"
               class="management-sidebar-link <?= $tab === 'logs' ? 'active' : '' ?>">
                📋 Registro de accesos
            </a>
            <a href="/?edit_env=1"
               class="management-sidebar-link">
                ⚙️ Editor .env
            </a>
        </div>
    </div>

    <div class="flex-grow-1 p-4">
