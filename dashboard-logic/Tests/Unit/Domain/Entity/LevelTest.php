<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use Dashboard\Domain\Entity\Level;
use Dashboard\Domain\ValueObject\LevelType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios de la entidad Level.
 *
 * Verifica la creación de niveles, la detección de tipo admin/client,
 * y el renombre de niveles.
 */
#[CoversClass(Level::class)]
final class LevelTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $type  = new LevelType(LevelType::ADMIN);
        $level = new Level('level-1', 'admin', $type);

        self::assertSame('level-1', $level->levelId());
        self::assertSame('admin', $level->levelName());
        self::assertTrue($level->type()->isAdmin());
        self::assertSame(0, $level->type()->value());
    }

    public function test_admin_level_returns_isAdmin_true(): void
    {
        $level = new Level('lvl-1', 'admin', new LevelType(LevelType::ADMIN));
        self::assertTrue($level->isAdmin());
    }

    public function test_client_level_returns_isAdmin_false(): void
    {
        $level = new Level('lvl-2', 'client', new LevelType(LevelType::CLIENT));
        self::assertFalse($level->isAdmin());
    }

    public function test_rename_updates_levelName(): void
    {
        $level = new Level('lvl-1', 'old-name', new LevelType(LevelType::CLIENT));
        $level->rename('new-name');

        self::assertSame('new-name', $level->levelName());
    }

    public function test_type_isAdmin_delegates_to_levelType(): void
    {
        $admin  = new Level('a', 'x', new LevelType(LevelType::ADMIN));
        $client = new Level('b', 'y', new LevelType(LevelType::CLIENT));

        self::assertTrue($admin->type()->isAdmin());
        self::assertTrue($client->type()->isClient());
    }
}
