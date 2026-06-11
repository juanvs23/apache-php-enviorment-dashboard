<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use Dashboard\Domain\Entity\Permission;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios de la entidad Permission.
 *
 * Verifica que Permission sea completamente inmutable
 * y que todos sus getters retornen los valores correctos.
 */
#[CoversClass(Permission::class)]
final class PermissionTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $perm = new Permission(1, 'users.manage', 'Gestionar usuarios');

        self::assertSame(1, $perm->id());
        self::assertSame('users.manage', $perm->key());
        self::assertSame('Gestionar usuarios', $perm->label());
    }

    public function test_all_getters_return_expected_types(): void
    {
        $perm = new Permission(42, 'server.view', 'Ver servidor');

        self::assertIsInt($perm->id());
        self::assertIsString($perm->key());
        self::assertIsString($perm->label());
    }

    public function test_inmutable_id(): void
    {
        $perm = new Permission(5, 'x', 'y');
        self::assertSame(5, $perm->id());
    }

    public function test_inmutable_key(): void
    {
        $perm = new Permission(5, 'test.key', 'Test');
        self::assertSame('test.key', $perm->key());
    }

    public function test_inmutable_label(): void
    {
        $perm = new Permission(5, 'test.key', 'Test Label');
        self::assertSame('Test Label', $perm->label());
    }
}
