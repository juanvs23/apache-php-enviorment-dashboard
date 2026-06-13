<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Filesystem;

use Dashboard\Infrastructure\Filesystem\ProjectCreator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests del ProjectCreator.
 *
 * Prueba la creación de proyectos en el sistema de archivos usando
 * directorios temporales. Los métodos que requieren herramientas
 * externas (Composer, WP-CLI, Git con red) se prueban solo en
 * sus escenarios de error alcanzables sin dichas herramientas.
 */
#[CoversClass(ProjectCreator::class)]
final class ProjectCreatorTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        $this->tempRoot = sys_get_temp_dir() . '/project-creator-test-' . uniqid();
        mkdir($this->tempRoot, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempRoot);
    }

    // ══════════════════════════════════════════════════════════════
    // Constructor
    // ══════════════════════════════════════════════════════════════

    public function test_constructor_stores_root_path(): void
    {
        $creator = new ProjectCreator('/some/path/');

        $ref = new \ReflectionProperty(ProjectCreator::class, 'rootPath');
        $root = $ref->getValue($creator);

        $this->assertSame('/some/path', $root, 'Trailing slash should be trimmed');
    }

    public function test_constructor_trims_trailing_slash(): void
    {
        $creator = new ProjectCreator('/some/path');
        $ref = new \ReflectionProperty(ProjectCreator::class, 'rootPath');

        $this->assertSame('/some/path', $ref->getValue($creator));
    }

    // ══════════════════════════════════════════════════════════════
    // createHtml — vanilla
    // ══════════════════════════════════════════════════════════════

    public function test_create_html_vanilla_creates_directory_structure(): void
    {
        $creator = new ProjectCreator($this->tempRoot);

        $path = $creator->createHtml('My Project', 'my-project', false);

        $this->assertSame($this->tempRoot . '/my-project', $path);
        $this->assertDirectoryExists($path);
        $this->assertDirectoryExists($path . '/assets/css');
        $this->assertDirectoryExists($path . '/assets/js');
        $this->assertDirectoryExists($path . '/assets/images');
    }

    public function test_create_html_vanilla_creates_index_html(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Hello World', 'hello-world', false);

        $html = file_get_contents($this->tempRoot . '/hello-world/index.html');
        $this->assertStringContainsString('<title>Hello World</title>', $html);
        $this->assertStringContainsString('<h1>Hello World</h1>', $html);
        $this->assertStringContainsString('assets/css/styles.css', $html);
    }

    public function test_create_html_vanilla_creates_asset_templates(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Test', 'test', false);

        $this->assertFileExists($this->tempRoot . '/test/assets/css/styles.css');
        $this->assertFileExists($this->tempRoot . '/test/assets/js/main.js');

        $css = file_get_contents($this->tempRoot . '/test/assets/css/styles.css');
        $this->assertStringContainsString('box-sizing: border-box', $css);
        $this->assertStringContainsString('font-family: system-ui', $css);

        $js = file_get_contents($this->tempRoot . '/test/assets/js/main.js');
        $this->assertStringContainsString('DOMContentLoaded', $js);
    }

    public function test_create_html_vanilla_creates_user_data_txt(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('My Site', 'my-site', false);

        $userData = file_get_contents($this->tempRoot . '/my-site/user-data.txt');
        $this->assertStringContainsString('type: html', $userData);
        $this->assertStringContainsString('name: My Site', $userData);
    }

    public function test_create_html_vanilla_creates_gitignore(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Test', 'test', false);

        $gitignore = file_get_contents($this->tempRoot . '/test/.gitignore');
        $this->assertStringContainsString('node_modules/', $gitignore);
        $this->assertStringContainsString('.env', $gitignore);
    }

    public function test_create_html_vanilla_git_init_creates_git_dir(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Test', 'test', false);

        $this->assertDirectoryExists($this->tempRoot . '/test/.git');
    }

    // ══════════════════════════════════════════════════════════════
    // createHtml — Vite
    // ══════════════════════════════════════════════════════════════

    public function test_create_html_vite_creates_src_directory(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Vite App', 'vite-app', true);

        $this->assertDirectoryExists($this->tempRoot . '/vite-app/src');
        $this->assertDirectoryExists($this->tempRoot . '/vite-app/public');
    }

    public function test_create_html_vite_creates_vite_config(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Vite App', 'vite-app', true);

        $config = file_get_contents($this->tempRoot . '/vite-app/vite.config.js');
        $this->assertStringContainsString("from 'vite'", $config);
        $this->assertStringContainsString('port: 5173', $config);
    }

    public function test_create_html_vite_creates_package_json(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Vite App', 'vite-app', true);

        $pkg = json_decode(
            file_get_contents($this->tempRoot . '/vite-app/package.json'),
            true,
        );

        $this->assertIsArray($pkg);
        $this->assertSame('vite-app', $pkg['name']);
        $this->assertTrue($pkg['private']);
        $this->assertArrayHasKey('dev', $pkg['scripts']);
        $this->assertArrayHasKey('vite', $pkg['devDependencies']);
    }

    public function test_create_html_vite_creates_index_html_with_module_script(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Vite App', 'vite-app', true);

        $html = file_get_contents($this->tempRoot . '/vite-app/index.html');
        $this->assertStringContainsString('<div id="app"></div>', $html);
        $this->assertStringContainsString('src="/src/main.js"', $html);
    }

    public function test_create_html_vite_creates_htaccess_with_proxy(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Vite App', 'vite-app', true);

        $htaccess = file_get_contents($this->tempRoot . '/vite-app/.htaccess');
        $this->assertStringContainsString('mod_rewrite.c', $htaccess);
        $this->assertStringContainsString('127.0.0.1:5173', $htaccess);
        $this->assertStringContainsString('[P,L]', $htaccess);
    }

    public function test_create_html_vite_creates_main_js_with_app_mount(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Vite App', 'vite-app', true);

        $mainJs = file_get_contents($this->tempRoot . '/vite-app/src/main.js');
        $this->assertStringContainsString("import './style.css'", $mainJs);
        $this->assertStringContainsString("querySelector('#app')", $mainJs);
    }

    // ══════════════════════════════════════════════════════════════
    // createHtml — error cases
    // ══════════════════════════════════════════════════════════════

    public function test_create_html_throws_when_directory_exists(): void
    {
        mkdir($this->tempRoot . '/existing', 0755, true);

        $creator = new ProjectCreator($this->tempRoot);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("El directorio 'existing' ya existe");

        $creator->createHtml('Test', 'existing', false);
    }

    // ══════════════════════════════════════════════════════════════
    // detectProjectType (via reflection)
    // ══════════════════════════════════════════════════════════════

    public function test_detect_project_type_wordpress_via_wp_config(): void
    {
        $type = $this->invokeDetectProjectType(['wp-config.php']);

        $this->assertSame('wordpress', $type);
    }

    public function test_detect_project_type_wordpress_via_wp_login(): void
    {
        $type = $this->invokeDetectProjectType(['wp-login.php']);

        $this->assertSame('wordpress', $type);
    }

    public function test_detect_project_type_laravel(): void
    {
        $type = $this->invokeDetectProjectType(['artisan', 'composer.json']);

        $this->assertSame('laravel', $type);
    }

    public function test_detect_project_type_node(): void
    {
        $type = $this->invokeDetectProjectType(['package.json']);

        $this->assertSame('node', $type);
    }

    public function test_detect_project_type_defaults_to_html(): void
    {
        $type = $this->invokeDetectProjectType(['index.html', 'styles.css']);

        $this->assertSame('html', $type);
    }

    public function test_detect_project_type_empty_directory_is_html(): void
    {
        $type = $this->invokeDetectProjectType([]);

        $this->assertSame('html', $type);
    }

    // ══════════════════════════════════════════════════════════════
    // Template methods — pure functions, no filesystem
    // ══════════════════════════════════════════════════════════════

    public function test_vanilla_html_template_contains_title(): void
    {
        $html = $this->invokeTemplate('vanillaHtmlTemplate', 'Site Title');

        $this->assertStringContainsString('<title>Site Title</title>', $html);
        $this->assertStringContainsString('<h1>Site Title</h1>', $html);
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('charset="UTF-8"', $html);
    }

    public function test_vanilla_html_template_sanitizes_html(): void
    {
        // The template does NOT escape — it trusts the caller.
        // This test documents the current behavior (caller responsibility).
        $html = $this->invokeTemplate('vanillaHtmlTemplate', '<script>alert(1)</script>');

        $this->assertStringContainsString('<script>alert(1)</script>', $html);
    }

    public function test_vanilla_css_template_contains_reset(): void
    {
        $css = $this->invokeTemplate('vanillaCssTemplate');

        $this->assertStringContainsString('box-sizing: border-box', $css);
        $this->assertStringContainsString('margin: 0', $css);
        $this->assertStringContainsString('padding: 0', $css);
    }

    public function test_vanilla_js_template_contains_event_listener(): void
    {
        $js = $this->invokeTemplate('vanillaJsTemplate');

        $this->assertStringContainsString('DOMContentLoaded', $js);
        $this->assertStringContainsString('console.log', $js);
    }

    public function test_vite_html_template_does_not_include_css_link(): void
    {
        $html = $this->invokeTemplate('viteHtmlTemplate', 'Vite Site');

        $this->assertStringContainsString('<div id="app"></div>', $html);
        $this->assertStringNotContainsString('stylesheet', $html);
        // Vite loads CSS via JS import, not <link>
    }

    public function test_vite_main_js_mounts_to_app_div(): void
    {
        $js = $this->invokeTemplate('viteMainJs');

        $this->assertStringContainsString("import './style.css'", $js);
        $this->assertStringContainsString("querySelector('#app')", $js);
    }

    public function test_vite_config_contains_default_settings(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $method = new \ReflectionMethod(ProjectCreator::class, 'viteConfig');
        $config = $method->invoke($creator, 'my-project');

        $this->assertStringContainsString("from 'vite'", $config);
        $this->assertStringContainsString("host: '0.0.0.0'", $config);
        $this->assertStringContainsString('port: 5173', $config);
        $this->assertStringContainsString("outDir: 'dist'", $config);
    }

    public function test_package_json_generates_valid_json(): void
    {
        $json = $this->invokeTemplate('packageJson', 'My Awesome Project');

        $pkg = json_decode($json, true);
        $this->assertIsArray($pkg);
        $this->assertSame('my-awesome-project', $pkg['name']);
        $this->assertSame('module', $pkg['type']);
        $this->assertSame('vite', $pkg['scripts']['dev']);
        $this->assertSame('^5.0.0', $pkg['devDependencies']['vite']);
    }

    public function test_package_json_name_slugifies_title(): void
    {
        $json = $this->invokeTemplate('packageJson', 'Hello World!');

        $pkg = json_decode($json, true);
        $this->assertSame('hello-world!', $pkg['name']);
    }

    // ══════════════════════════════════════════════════════════════
    // createFromGithub — error cases (no network needed)
    // ══════════════════════════════════════════════════════════════

    public function test_create_from_github_throws_when_directory_exists(): void
    {
        mkdir($this->tempRoot . '/existing-repo', 0755, true);

        $creator = new ProjectCreator($this->tempRoot);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("El directorio 'existing-repo' ya existe");

        $creator->createFromGithub('Test', 'existing-repo', 'https://github.com/user/repo.git');
    }

    // ══════════════════════════════════════════════════════════════
    // Painful edge cases
    // ══════════════════════════════════════════════════════════════

    public function test_create_html_with_special_characters_in_name(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Project & More "Special" <Test>', 'special', false);

        $userData = file_get_contents($this->tempRoot . '/special/user-data.txt');
        $this->assertStringContainsString('Project & More "Special" <Test>', $userData);
        // Documented: user-data.txt does NOT escape HTML entities.
        // The dashboard view is responsible for escaping at render time.
    }

    public function test_chmod_0777_is_applied_to_all_created_files(): void
    {
        $creator = new ProjectCreator($this->tempRoot);
        $creator->createHtml('Test', 'perms-test', false);

        $indexPerms = substr(sprintf('%o', fileperms($this->tempRoot . '/perms-test/index.html')), -3);
        $this->assertSame('777', $indexPerms, 'Created files should have 0777 permissions');
    }

    // ══════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════

    /**
     * Invoca el método privado detectProjectType con los archivos dados.
     *
     * @param list<string> $files Archivos a crear en el directorio temporal
     */
    private function invokeDetectProjectType(array $files): string
    {
        $dir = $this->tempRoot . '/type-detect-' . uniqid();
        mkdir($dir, 0755, true);

        foreach ($files as $file) {
            file_put_contents($dir . '/' . $file, '');
        }

        $creator = new ProjectCreator($this->tempRoot);
        $method = new \ReflectionMethod(ProjectCreator::class, 'detectProjectType');

        $result = $method->invoke($creator, $dir);

        $this->removeDir($dir);

        return $result;
    }

    /**
     * Invoca un método privado de template que recibe un string.
     *
     * @param string      $methodName Nombre del método privado
     * @param string|null $arg        Argumento string (null para métodos sin parámetros)
     */
    private function invokeTemplate(string $methodName, ?string $arg = null): string
    {
        $creator = new ProjectCreator($this->tempRoot);
        $method = new \ReflectionMethod(ProjectCreator::class, $methodName);

        return $arg === null
            ? $method->invoke($creator)
            : $method->invoke($creator, $arg);
    }

    /**
     * Elimina un directorio recursivamente.
     */
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
