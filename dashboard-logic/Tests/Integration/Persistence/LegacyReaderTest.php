<?php

declare(strict_types=1);

namespace Tests\Integration\Persistence;

use Dashboard\Database\Connection;
use Dashboard\Infrastructure\Persistence\LegacyReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LegacyReader::class)]
final class LegacyReaderTest extends TestCase
{
    private LegacyReader $reader;
    private bool $dbAvailable = false;

    protected function setUp(): void
    {
        try {
            Connection::reset();
            Connection::get();
            $this->dbAvailable = true;
        } catch (\Throwable) {
            $this->dbAvailable = false;
        }

        $this->reader = new LegacyReader();
    }

    protected function tearDown(): void
    {
        Connection::reset();
    }

    // ══════════════════════════════════════════════════════════════

    public function test_get_all_users_returns_array(): void
    {
        if (!$this->dbAvailable) {
            self::markTestSkipped('Database not available');
        }

        $users = $this->reader->getAllUsers();

        self::assertIsArray($users);
        self::assertNotEmpty($users, 'Seed should have created at least admin user');
        self::assertArrayHasKey('email', $users[0]);
        self::assertArrayHasKey('name', $users[0]);
        self::assertArrayHasKey('level_name', $users[0]);
        self::assertArrayHasKey('level_type', $users[0]);
    }

    public function test_get_all_users_contains_admin(): void
    {
        if (!$this->dbAvailable) {
            self::markTestSkipped('Database not available');
        }

        $users = $this->reader->getAllUsers();
        $admin = array_filter($users, fn(array $u) => ($u['level_type'] ?? 1) === 0);

        self::assertNotEmpty($admin, 'At least one admin user must exist');
    }

    public function test_get_all_levels_returns_array(): void
    {
        if (!$this->dbAvailable) {
            self::markTestSkipped('Database not available');
        }

        $levels = $this->reader->getAllLevels();

        self::assertIsArray($levels);
        self::assertNotEmpty($levels, 'Seed should have created levels');
        self::assertArrayHasKey('levelsID', $levels[0]);
        self::assertArrayHasKey('level_name', $levels[0]);
        self::assertArrayHasKey('level_type', $levels[0]);
    }

    public function test_get_all_levels_has_admin(): void
    {
        if (!$this->dbAvailable) {
            self::markTestSkipped('Database not available');
        }

        $levels = $this->reader->getAllLevels();
        $admin  = array_filter($levels, fn(array $l) => $l['level_name'] === 'admin');

        self::assertCount(1, $admin, 'Exactly one admin level must exist');
        self::assertSame(0, (int) $admin[array_key_first($admin)]['level_type']);
    }

    public function test_get_all_projects_returns_array(): void
    {
        if (!$this->dbAvailable) {
            self::markTestSkipped('Database not available');
        }

        $projects = $this->reader->getAllProjects();

        self::assertIsArray($projects);
        // Projects might be empty — that's valid
        if (!empty($projects)) {
            self::assertArrayHasKey('id', $projects[0]);
            self::assertArrayHasKey('project_name', $projects[0]);
            self::assertArrayHasKey('acept_login', $projects[0]);
        }
    }

    public function test_get_client_users_returns_array(): void
    {
        if (!$this->dbAvailable) {
            self::markTestSkipped('Database not available');
        }

        $clients = $this->reader->getClientUsers();

        self::assertIsArray($clients);
        if (!empty($clients)) {
            self::assertArrayHasKey('userID', $clients[0]);
            self::assertArrayHasKey('email', $clients[0]);
        }
    }

    public function test_get_all_levels_with_perms_returns_levels_with_perms_key(): void
    {
        if (!$this->dbAvailable) {
            self::markTestSkipped('Database not available');
        }

        $levels = $this->reader->getAllLevelsWithPerms();

        self::assertIsArray($levels);
        self::assertNotEmpty($levels);
        self::assertArrayHasKey('perms', $levels[0], 'Each level must have a perms key');
        self::assertIsArray($levels[0]['perms']);

        // Admin level should have ALL permissions
        $admin = array_filter($levels, fn(array $l) => $l['level_name'] === 'admin');
        if (!empty($admin)) {
            $adminPerms = $admin[array_key_first($admin)]['perms'];
            self::assertNotEmpty($adminPerms, 'Admin level should have permissions');
        }
    }

    public function test_get_all_permissions_returns_catalog(): void
    {
        if (!$this->dbAvailable) {
            self::markTestSkipped('Database not available');
        }

        $perms = $this->reader->getAllPermissions();

        self::assertIsArray($perms);
        self::assertNotEmpty($perms, 'Seed should have created permissions');
        self::assertArrayHasKey('id', $perms[0]);
        self::assertArrayHasKey('perm_key', $perms[0]);
        self::assertArrayHasKey('perm_label', $perms[0]);

        // Known permissions should exist
        $keys = array_column($perms, 'perm_key');
        self::assertContains('users.manage', $keys);
        self::assertContains('profile.edit', $keys);
    }

    public function test_get_all_permissions_has_eight_permissions(): void
    {
        if (!$this->dbAvailable) {
            self::markTestSkipped('Database not available');
        }

        $perms = $this->reader->getAllPermissions();
        self::assertCount(8, $perms, 'Seed should create exactly 8 permissions');
    }
}
