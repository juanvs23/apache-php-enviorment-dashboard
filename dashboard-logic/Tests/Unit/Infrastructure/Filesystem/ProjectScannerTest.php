<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Filesystem;

use Dashboard\Infrastructure\Filesystem\ProjectScanner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests del ProjectScanner.
 *
 * Crea directorios temporales con archivos user-data.txt
 * para verificar el escaneo de proyectos en el sistema de archivos.
 */
#[CoversClass(ProjectScanner::class)]
final class ProjectScannerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/project-scanner-test-' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function createProjectDir(string $name, array $data = []): void
    {
        $path = $this->tempDir . '/' . $name;
        mkdir($path, 0755, true);

        $lines = '';
        foreach ($data as $key => $val) {
            $lines .= "{$key}:{$val}\n";
        }
        file_put_contents($path . '/user-data.txt', $lines);
    }

    public function test_scan_returns_projects_found(): void
    {
        $this->createProjectDir('mi-sitio', ['user' => 'db_user', 'password' => 'secret', 'type' => 'wordpress']);

        $scanner = new ProjectScanner($this->tempDir);
        $projects = $scanner->scan();

        self::assertCount(1, $projects);
        self::assertSame('mi-sitio', $projects[0]['dir']);
        self::assertSame('mi-sitio', $projects[0]['slug']);
        self::assertSame('Mi sitio', $projects[0]['name']);
        self::assertSame('wordpress', $projects[0]['type']);
    }

    public function test_scan_ignores_directories_without_user_data(): void
    {
        $this->createProjectDir('project-a', ['user' => 'u1', 'password' => 'p1', 'type' => 'laravel']);
        mkdir($this->tempDir . '/project-b', 0755, true); // Sin user-data.txt

        $scanner = new ProjectScanner($this->tempDir);
        $projects = $scanner->scan();

        self::assertCount(1, $projects);
        self::assertSame('project-a', $projects[0]['dir']);
    }

    public function test_scan_returns_multiple_projects(): void
    {
        $this->createProjectDir('alpha', ['user' => 'u1', 'password' => 'p1']);
        $this->createProjectDir('beta', ['user' => 'u2', 'password' => 'p2']);

        $scanner = new ProjectScanner($this->tempDir);
        $projects = $scanner->scan();

        self::assertCount(2, $projects);
    }

    public function test_scan_returns_empty_array_when_no_projects(): void
    {
        $scanner = new ProjectScanner($this->tempDir);
        $projects = $scanner->scan();

        self::assertIsArray($projects);
        self::assertEmpty($projects);
    }

    public function test_scan_filters_out_assets_and_dashboard_logic(): void
    {
        $this->createProjectDir('project-a', ['user' => 'u1', 'password' => 'p1']);
        $this->createProjectDir('assets', ['user' => 'u2']); // Debe ser ignorado
        $this->createProjectDir('dashboard-logic', ['user' => 'u3']); // Debe ser ignorado

        $scanner = new ProjectScanner($this->tempDir);
        $projects = $scanner->scan();

        $dirs = array_column($projects, 'dir');
        self::assertNotContains('assets', $dirs);
        self::assertNotContains('dashboard-logic', $dirs);
        self::assertContains('project-a', $dirs);
    }

    public function test_isProject_returns_true_for_valid_project(): void
    {
        $this->createProjectDir('valid-project', ['user' => 'u', 'password' => 'p']);
        $scanner = new ProjectScanner($this->tempDir);

        self::assertTrue($scanner->isProject('valid-project'));
    }

    public function test_isProject_returns_false_for_invalid(): void
    {
        $scanner = new ProjectScanner($this->tempDir);

        self::assertFalse($scanner->isProject('non-existent'));
    }

    public function test_parse_project_with_empty_user_data(): void
    {
        $this->createProjectDir('empty', []); // user-data.txt vacío

        $scanner = new ProjectScanner($this->tempDir);
        $projects = $scanner->scan();

        self::assertCount(1, $projects);
        self::assertSame('empty', $projects[0]['dir']);
        self::assertSame('', $projects[0]['user']);
        self::assertSame('', $projects[0]['password']);
    }
}
