<?php

declare(strict_types=1);

namespace Dashboard\Infrastructure\Filesystem;

/**
 * Creador de proyectos en el sistema de archivos.
 *
 * Crea la estructura de directorios y archivos base para diferentes
 * tipos de proyectos. Cada proyecto incluye user-data.txt para que
 * el dashboard lo detecte automáticamente.
 */
final class ProjectCreator
{
    /**
     * Ruta raíz donde crear proyectos.
     */
    private string $rootPath;

    /**
     * @param string $rootPath Ruta absoluta al directorio raíz
     */
    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, '/');
    }

    /**
     * Crea un proyecto HTML desde cero.
     *
     * @param string $projectName Nombre descriptivo del proyecto
     * @param string $directory   Nombre del directorio (slug, sin espacios)
     * @param bool   $useVite     Si es true, configura Vite.js
     * @return string Ruta absoluta del directorio creado
     * @throws \RuntimeException Si el directorio ya existe
     */
    public function createHtml(string $projectName, string $directory, bool $useVite = false): string
    {
        $targetDir = $this->rootPath . '/' . $directory;

        if (is_dir($targetDir)) {
            throw new \RuntimeException("El directorio '{$directory}' ya existe");
        }

        if (!mkdir($targetDir, 0755, true)) {
            throw new \RuntimeException("No se pudo crear el directorio '{$directory}'");
        }

        if ($useVite) {
            $this->createViteProject($targetDir, $projectName);
        } else {
            $this->createVanillaProject($targetDir, $projectName);
        }

        // user-data.txt para el dashboard
        file_put_contents($targetDir . '/user-data.txt', "type: html\nname: {$projectName}\n");

        // Inicializar git y .gitignore
        $gitOut = []; $gitCode = 0;
        exec(sprintf('cd %s && git init 2>&1', escapeshellarg($targetDir)), $gitOut, $gitCode);
        if (!file_exists($targetDir . '/.gitignore')) {
            file_put_contents($targetDir . '/.gitignore', "node_modules/\ndist/\n.env\n.DS_Store\n");
        }
        // .htaccess con proxy reverso para Vite
        if ($useVite) {
            file_put_contents($targetDir . '/.htaccess', implode("\n", [
                '<IfModule mod_rewrite.c>',
                'RewriteEngine On',
                'RewriteCond %{REQUEST_FILENAME} !-f',
                'RewriteCond %{REQUEST_FILENAME} !-d',
                'RewriteRule ^(.*)$ http://127.0.0.1:5173/$1 [P,L]',
                '</IfModule>',
                '',
            ]));
        }

        // Asegurar permisos para que el usuario y www-data puedan escribir
        exec(sprintf('chmod -R 0777 %s', escapeshellarg($targetDir)));

        return $targetDir;
    }

    /**
     * Crea un proyecto Laravel desde cero.
     *
     * @param string $projectName Nombre descriptivo
     * @param string $directory   Nombre del directorio
     * @param string $dbName      Nombre de la base de datos MySQL
     * @return string Ruta absoluta del directorio creado
     * @throws \RuntimeException Si falla composer o la DB
     */
    public function createLaravel(string $projectName, string $directory, string $dbName): string
    {
        $targetDir = $this->rootPath . '/' . $directory;

        if (is_dir($targetDir)) {
            exec(sprintf('rm -rf %s', escapeshellarg($targetDir)));
        }

        // Crear proyecto con Composer (en background, puede tardar ~60s)
        @mkdir('/tmp/composer', 0777, true);
        $cmd = sprintf('cd %s && COMPOSER_HOME=/tmp/composer /usr/bin/composer create-project --prefer-dist --no-interaction laravel/laravel %s 2>&1',
            escapeshellarg($this->rootPath), escapeshellarg($directory));
        set_time_limit(0); // sin límite de tiempo
        $output = []; $exitCode = 0;
        exec($cmd, $output, $exitCode);
        // No esperamos — el dashboard mostrará el proyecto cuando Composer termine
        $output = []; $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !is_dir($targetDir)) {
            throw new \RuntimeException('Error al crear Laravel: ' . implode("\n", array_slice($output, -5)));
        }

        // Crear base de datos
        $this->createDatabase($dbName);

        // Configurar .env
        $env = file_get_contents($targetDir . '/.env');
        $env = preg_replace('/DB_DATABASE=.*/', "DB_DATABASE={$dbName}", $env);
        $env = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME=' . ($_ENV['DB_USER'] ?? ''), $env);
        $env = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=' . ($_ENV['DB_PASS'] ?? ''), $env);
        file_put_contents($targetDir . '/.env', $env);

        // .gitignore + git init
        exec(sprintf('cd %s && git init 2>&1', escapeshellarg($targetDir)));
        file_put_contents($targetDir . '/user-data.txt', "type: laravel\nname: {$projectName}\n");
        exec(sprintf('chmod -R 0777 %s', escapeshellarg($targetDir)));

        return $targetDir;
    }

    /**
     * Crea un proyecto WordPress desde cero.
     *
     * @param string $projectName   Nombre descriptivo
     * @param string $directory     Nombre del directorio
     * @param string $dbName        Nombre de la base de datos
     * @param string $siteTitle     Título del sitio
     * @param string $adminEmail    Email del admin
     * @param string $adminPassword Contraseña del admin
     * @return string Ruta absoluta del directorio creado
     * @throws \RuntimeException Si falla WP-CLI o la DB
     */
    public function createWordpress(
        string $projectName,
        string $directory,
        string $dbName,
        string $siteTitle,
        string $adminEmail,
        string $adminPassword,
    ): string {
        $targetDir = $this->rootPath . '/' . $directory;

        if (is_dir($targetDir)) {
            exec(sprintf('rm -rf %s', escapeshellarg($targetDir)));
        }

        $dbUser = $_ENV['DB_USER'] ?? '';
        $dbPass = $_ENV['DB_PASS'] ?? '';

        // Crear base de datos
        $this->createDatabase($dbName);

        // Descargar WordPress
        $cmd = sprintf('cd %s && wp core download --path=%s 2>&1',
            escapeshellarg($this->rootPath), escapeshellarg($directory));
        $output = []; $exitCode = 0;
        exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new \RuntimeException('Error al descargar WordPress: ' . implode("\n", $output));
        }

        // Crear wp-config.php
        $cmd = sprintf('cd %s && wp config create --dbname=%s --dbuser=%s --dbpass=%s --path=%s 2>&1',
            escapeshellarg($targetDir), escapeshellarg($dbName), escapeshellarg($dbUser), escapeshellarg($dbPass), escapeshellarg($targetDir));
        exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new \RuntimeException('Error al crear wp-config.php: ' . implode("\n", $output));
        }

        // Añadir FS_METHOD direct para evitar FTP en updates
        file_put_contents($targetDir . '/wp-config.php',
            "\n/** Permitir escritura directa sin FTP */\ndefine('FS_METHOD', 'direct');\n",
            FILE_APPEND);

        // Instalar WordPress
        $cmd = sprintf('cd %s && wp core install --url=http://localhost/%s --title=%s --admin_user=admin --admin_email=%s --admin_password=%s --path=%s 2>&1',
            escapeshellarg($targetDir), escapeshellarg($directory), escapeshellarg($siteTitle),
            escapeshellarg($adminEmail), escapeshellarg($adminPassword), escapeshellarg($targetDir));
        exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new \RuntimeException('Error al instalar WordPress: ' . implode("\n", $output));
        }

        // Metadatos + git
        file_put_contents($targetDir . '/user-data.txt', "type: wordpress\nname: {$projectName}\nuser: admin\npassword: {$adminPassword}\n");
        exec(sprintf('cd %s && git init 2>&1', escapeshellarg($targetDir)));
        exec(sprintf('chmod -R 0777 %s', escapeshellarg($targetDir)));

        return $targetDir;
    }

    /**
     * Verifica si una base de datos MySQL ya existe.
     *
     * @param string $dbName Nombre de la base de datos
     * @return bool true si la base de datos existe
     */
    public function databaseExists(string $dbName): bool
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $user = $_ENV['DB_USER'] ?? '';
        $pass = $_ENV['DB_PASS'] ?? '';
        try {
            $pdo = new \PDO("mysql:host={$host};port={$port}", $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $stmt = $pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
            $stmt->execute([$dbName]);
            return $stmt->fetchColumn() !== false;
        } catch (\Throwable) {
            return false; // Si no podemos conectar, asumimos que no existe
        }
    }

    /**
     * Crea una base de datos MySQL si no existe.
     */
    private function createDatabase(string $dbName): void
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $user = $_ENV['DB_USER'] ?? '';
        $pass = $_ENV['DB_PASS'] ?? '';
        try {
            $pdo = new \PDO("mysql:host={$host};port={$port}", $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            throw new \RuntimeException("Error al crear la base de datos '{$dbName}': " . $e->getMessage());
        }
    }

    /**
     * Crea un proyecto clonando un repositorio desde GitHub/Git.
     *
     * @param string      $projectName Nombre descriptivo del proyecto
     * @param string      $directory   Nombre del directorio destino
     * @param string      $repoUrl     URL del repo (HTTPS o SSH)
     * @param string|null $branch      Rama a clonar (null = default)
     * @return string Ruta absoluta del directorio creado
     * @throws \RuntimeException Si el directorio ya existe o git falla
     */
    public function createFromGithub(
        string $projectName,
        string $directory,
        string $repoUrl,
        ?string $branch = null,
    ): string {
        $targetDir = $this->rootPath . '/' . $directory;

        if (is_dir($targetDir)) {
            throw new \RuntimeException("El directorio '{$directory}' ya existe");
        }

        $cmd = sprintf('git clone %s %s', escapeshellarg($repoUrl), escapeshellarg($targetDir));
        if ($branch !== null && $branch !== '') {
            $cmd .= sprintf(' --branch %s', escapeshellarg($branch));
        }
        $cmd .= ' 2>&1';

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !is_dir($targetDir)) {
            $error = implode("\n", $output);
            throw new \RuntimeException("Error al clonar el repositorio: {$error}");
        }

        // Detectar tipo de proyecto
        $type = $this->detectProjectType($targetDir);
        file_put_contents($targetDir . '/user-data.txt', "type: {$type}\nname: {$projectName}\n");

        // npm install automático si tiene package.json
        if (file_exists($targetDir . '/package.json')) {
            $installCmd = sprintf('cd %s && npm install 2>&1', escapeshellarg($targetDir));
            exec($installCmd, $installOutput, $installExitCode);
        }

        exec(sprintf('chmod -R 0777 %s', escapeshellarg($targetDir)));

        return $targetDir;
    }

    /**
     * Intenta detectar el tipo de proyecto por sus archivos.
     */
    private function detectProjectType(string $dir): string
    {
        if (file_exists($dir . '/wp-config.php') || file_exists($dir . '/wp-login.php')) {
            return 'wordpress';
        }
        if (file_exists($dir . '/artisan') && file_exists($dir . '/composer.json')) {
            return 'laravel';
        }
        if (file_exists($dir . '/package.json')) {
            return 'node';
        }
        return 'html';
    }

    /**
     * Crea un proyecto HTML vanilla (sin bundler).
     */
    private function createVanillaProject(string $targetDir, string $projectName): void
    {
        // Estructura de directorios
        mkdir($targetDir . '/assets/css', 0755, true);
        mkdir($targetDir . '/assets/js', 0755, true);
        mkdir($targetDir . '/assets/images', 0755, true);

        // Archivos
        file_put_contents($targetDir . '/index.html', $this->vanillaHtmlTemplate($projectName));
        file_put_contents($targetDir . '/assets/css/styles.css', $this->vanillaCssTemplate());
        file_put_contents($targetDir . '/assets/js/main.js', $this->vanillaJsTemplate());
    }

    /**
     * Crea un proyecto HTML con Vite.js.
     */
    private function createViteProject(string $targetDir, string $projectName): void
    {
        // src/
        mkdir($targetDir . '/src', 0755, true);

        // public/ — opcional, assets estáticos
        mkdir($targetDir . '/public', 0755, true);

        // index.html (en raíz para Vite)
        file_put_contents($targetDir . '/index.html', $this->viteHtmlTemplate($projectName));

        // src/main.js
        file_put_contents($targetDir . '/src/main.js', $this->viteMainJs());

        // src/style.css
        file_put_contents($targetDir . '/src/style.css', $this->vanillaCssTemplate());

        // vite.config.js
        file_put_contents($targetDir . '/vite.config.js', $this->viteConfig($directory = basename($targetDir)));

        // package.json
        file_put_contents($targetDir . '/package.json', $this->packageJson($projectName));

        // .gitignore
        file_put_contents($targetDir . '/.gitignore', "node_modules/\ndist/\n.vite/\n");
    }

    // ══════════════════════════════════════════════════════════════
    // Templates
    // ══════════════════════════════════════════════════════════════

    private function vanillaHtmlTemplate(string $title): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            <link rel="stylesheet" href="assets/css/styles.css">
        </head>
        <body>
            <h1>{$title}</h1>
            <p>Proyecto creado con Dev Dashboard.</p>
            <script src="assets/js/main.js"></script>
        </body>
        </html>
        HTML;
    }

    private function vanillaCssTemplate(): string
    {
        return <<<CSS
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        h1 {
            font-size: 2.5rem;
            color: #1a1d23;
        }

        p {
            color: #666;
            margin-top: 0.5rem;
        }
        CSS;
    }

    private function vanillaJsTemplate(): string
    {
        return <<<'JS'
        document.addEventListener('DOMContentLoaded', () => {
            console.log('🚀 Proyecto listo');
        });
        JS;
    }

    private function viteHtmlTemplate(string $title): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
        </head>
        <body>
            <div id="app"></div>
            <script type="module" src="/src/main.js"></script>
        </body>
        </html>
        HTML;
    }

    private function viteMainJs(): string
    {
        return <<<JS
        import './style.css';

        document.querySelector('#app').innerHTML = `
            <h1>🚀 Proyecto Vite</h1>
            <p>Listo para desarrollar. Ejecutá <code>npm run dev</code>.</p>
        `;
        JS;
    }

    private function viteConfig(string $projectDir): string
    {
        return <<<JS
        import { defineConfig } from 'vite';

        export default defineConfig({
            root: '.',
            publicDir: 'public',
            server: {
                host: '0.0.0.0',
                port: 5173,
            },
            build: {
                outDir: 'dist',
            },
        });
        JS;
    }

    private function packageJson(string $projectName): string
    {
        $name = strtolower(str_replace(' ', '-', $projectName));
        return json_encode([
            'name' => $name,
            'private' => true,
            'version' => '1.0.0',
            'type' => 'module',
            'scripts' => [
                'dev' => 'vite',
                'build' => 'vite build',
                'preview' => 'vite preview',
            ],
            'devDependencies' => [
                'vite' => '^5.0.0',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}
